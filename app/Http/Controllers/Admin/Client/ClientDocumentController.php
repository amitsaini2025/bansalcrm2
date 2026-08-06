<?php

namespace App\Http\Controllers\Admin\Client;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Filesystem\AwsS3V3Adapter;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Barryvdh\DomPDF\Facade\Pdf as PDF;

use App\Models\Admin;
use App\Models\ActivitiesLog;
use App\Models\Document;
use App\Models\DocumentChecklist;
use App\Support\ClientDocumentStaffResolver;

use App\Traits\ClientQueries;
use App\Traits\ClientAuthorization;
use App\Traits\ClientHelpers;

/**
 * Client document and checklist management
 * 
 * Methods moved from ClientsController:
 * - uploaddocument
 * - downloadpdf
 * - deletedocs
 * - renamedoc
 * - uploadalldocument
 * - addalldocchecklist
 * - deletealldocs
 * - renamealldoc
 * - renamechecklistdoc
 * - verifydoc
 * - notuseddoc
 * - backtodoc
 * - download_document
 * - preview_document
 * - bulkUploadDocuments
 * - getAutoChecklistMatches
 * 
 * Private helpers:
 * - findBestChecklistMatch
 * - cleanFileName
 * - extractKeywords
 * - calculateSimilarity
 * - checkPatternMatch
 * - checkAbbreviationMatch
 * - checkPartialMatch
 */
class ClientDocumentController extends Controller
{
    use ClientQueries, ClientAuthorization, ClientHelpers;

    public function __construct()
    {
        $this->middleware('auth:admin');
    }

    /**
     * Get auto-checklist matches for bulk upload
     */
    public function getAutoChecklistMatches(Request $request) {
        $response = ['status' => false, 'matches' => []];
        
        try {
            $files = $request->input('files', []);
            $clientid = $request->input('clientid');
            
            // Get all active checklists from DocumentChecklist table
            $checklists = DocumentChecklist::where('status', 1)
                ->pluck('name')
                ->toArray();
            
            // If no files provided, just return checklists (for frontend to get checklist list)
            if (empty($files)) {
                $response['status'] = true;
                $response['checklists'] = $checklists;
                return response()->json($response);
            }
            
            if (empty($checklists)) {
                $response['status'] = true;
                return response()->json($response);
            }
            
            $matches = [];
            
            foreach ($files as $file) {
                $fileName = $file['name'] ?? '';
                $match = $this->findBestChecklistMatch($fileName, $checklists);
                if ($match) {
                    $matches[$fileName] = $match;
                }
            }
            
            $response['status'] = true;
            $response['matches'] = $matches;
            $response['checklists'] = $checklists;
            
        } catch (\Exception $e) {
            Log::error('Error getting auto-checklist matches', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
        
        return response()->json($response);
    }
    
    /**
     * Find best checklist match for a filename
     */
    private function findBestChecklistMatch($fileName, $checklists) {
        if (empty($fileName) || empty($checklists)) {
            return null;
        }
        
        // Clean filename
        $cleanFileName = $this->cleanFileName($fileName);
        $fileNameLower = strtolower($cleanFileName);
        $fileNameWords = $this->extractKeywords($cleanFileName);
        
        $bestMatch = null;
        $bestScore = 0;
        $bestConfidence = 'low';
        
        foreach ($checklists as $checklist) {
            $checklistLower = strtolower($checklist);
            $checklistWords = $this->extractKeywords($checklist);
            
            // Strategy 1: Exact match (after cleaning)
            if ($fileNameLower === $checklistLower) {
                return [
                    'checklist' => $checklist,
                    'confidence' => 'high',
                    'score' => 100,
                    'method' => 'exact'
                ];
            }
            
            // Strategy 2: Fuzzy matching
            $similarity = $this->calculateSimilarity($fileNameLower, $checklistLower);
            if ($similarity > 85) {
                return [
                    'checklist' => $checklist,
                    'confidence' => 'high',
                    'score' => $similarity,
                    'method' => 'fuzzy'
                ];
            } elseif ($similarity > 70 && $similarity > $bestScore) {
                $bestMatch = $checklist;
                $bestScore = $similarity;
                $bestConfidence = 'medium';
            }
            
            // Strategy 3: Pattern matching
            $patternMatch = $this->checkPatternMatch($fileNameWords, $checklistWords);
            if ($patternMatch['matched'] && $patternMatch['score'] > $bestScore) {
                $bestMatch = $checklist;
                $bestScore = $patternMatch['score'];
                $bestConfidence = $patternMatch['score'] > 80 ? 'high' : 'medium';
            }
            
            // Strategy 4: Abbreviation matching
            $abbrevMatch = $this->checkAbbreviationMatch($cleanFileName, $checklist);
            if ($abbrevMatch && $abbrevMatch > $bestScore) {
                $bestMatch = $checklist;
                $bestScore = $abbrevMatch;
                $bestConfidence = 'high';
            }
            
            // Strategy 5: Partial word matching
            $partialMatch = $this->checkPartialMatch($fileNameWords, $checklistWords);
            if ($partialMatch && $partialMatch > $bestScore) {
                $bestMatch = $checklist;
                $bestScore = $partialMatch;
                $bestConfidence = 'low';
            }
        }
        
        if ($bestMatch && $bestScore > 50) {
            return [
                'checklist' => $bestMatch,
                'confidence' => $bestConfidence,
                'score' => $bestScore,
                'method' => 'combined'
            ];
        }
        
        return null;
    }
    
    /**
     * Clean filename for matching
     */
    private function cleanFileName($fileName) {
        // Remove extension
        $name = pathinfo($fileName, PATHINFO_FILENAME);
        // Remove common prefixes (client name, timestamps)
        $name = preg_replace('/^[^_]+_/', '', $name); // Remove prefix before first underscore
        $name = preg_replace('/_\d{10,}$/', '', $name); // Remove timestamps
        $name = preg_replace('/[^a-zA-Z0-9\s]/', ' ', $name); // Replace special chars with spaces
        return trim($name);
    }
    
    /**
     * Extract keywords from text
     */
    private function extractKeywords($text) {
        $text = strtolower($text);
        $words = preg_split('/[\s_\-]+/', $text);
        $stopWords = ['the', 'of', 'and', 'a', 'an', 'in', 'on', 'at', 'to', 'for', 'is', 'are', 'was', 'were'];
        return array_filter($words, function($word) use ($stopWords) {
            return strlen($word) > 2 && !in_array($word, $stopWords);
        });
    }
    
    /**
     * Calculate similarity between two strings (Levenshtein-based)
     */
    private function calculateSimilarity($str1, $str2) {
        $len1 = strlen($str1);
        $len2 = strlen($str2);
        
        if ($len1 === 0 || $len2 === 0) {
            return 0;
        }
        
        $maxLen = max($len1, $len2);
        $distance = levenshtein($str1, $str2);
        
        return (1 - ($distance / $maxLen)) * 100;
    }
    
    /**
     * Check pattern match
     */
    private function checkPatternMatch($fileNameWords, $checklistWords) {
        $patterns = [
            'passport' => ['passport', 'pass', 'pp'],
            'visa' => ['visa', 'grant', 'vg'],
            'identity' => ['id', 'identity', 'aadhar', 'aadhaar', 'national'],
            'birth' => ['birth', 'certificate', 'bc'],
            'marriage' => ['marriage', 'certificate', 'mc'],
            'education' => ['education', 'degree', 'diploma', 'certificate'],
            'employment' => ['employment', 'experience', 'work', 'job']
        ];
        
        $matched = false;
        $score = 0;
        
        foreach ($patterns as $key => $keywords) {
            $fileHasKeyword = false;
            $checklistHasKeyword = false;
            
            foreach ($keywords as $keyword) {
                if (in_array($keyword, $fileNameWords)) {
                    $fileHasKeyword = true;
                }
                if (in_array($keyword, $checklistWords)) {
                    $checklistHasKeyword = true;
                }
            }
            
            if ($fileHasKeyword && $checklistHasKeyword) {
                $matched = true;
                $score = 90; // High score for pattern match
                break;
            }
        }
        
        return ['matched' => $matched, 'score' => $score];
    }
    
    /**
     * Check abbreviation match
     */
    private function checkAbbreviationMatch($fileName, $checklist) {
        $abbreviations = [
            'pp' => 'passport',
            'vg' => 'visa grant',
            'nic' => 'national identity',
            'dob' => 'birth',
            'bc' => 'birth certificate',
            'mc' => 'marriage certificate'
        ];
        
        $fileNameLower = strtolower($fileName);
        $checklistLower = strtolower($checklist);
        
        foreach ($abbreviations as $abbrev => $full) {
            if (strpos($fileNameLower, $abbrev) !== false && strpos($checklistLower, $full) !== false) {
                return 85;
            }
        }
        
        return 0;
    }
    
    /**
     * Check partial word match
     */
    private function checkPartialMatch($fileNameWords, $checklistWords) {
        $matches = 0;
        $total = count($checklistWords);
        
        if ($total === 0) {
            return 0;
        }
        
        foreach ($checklistWords as $checklistWord) {
            foreach ($fileNameWords as $fileNameWord) {
                if (strpos($fileNameWord, $checklistWord) !== false || strpos($checklistWord, $fileNameWord) !== false) {
                    $matches++;
                    break;
                }
            }
        }
        
        return ($matches / $total) * 100;
    }
    
    /**
     * Bulk upload documents
     */
    public function bulkUploadDocuments(Request $request) {
        $response = ['status' => false, 'message' => 'Please try again'];
        
        try {
            $clientid = $request->clientid;
            $doctype = $request->doctype ?? 'documents';
            $type = $request->type ?? 'client';
            
            $adminInfo = Admin::select('client_id')->where('id', $clientid)->first();
            $client_unique_id = $adminInfo ? $adminInfo->client_id : '';
            
            if (!$request->hasFile('files')) {
                $response['message'] = 'No files uploaded';
                return response()->json($response);
            }
            
            $files = $request->file('files');
            $mappingsInput = $request->input('mappings', []);
            
            if (!is_array($files)) {
                $files = [$files];
            }
            
            // Parse mappings JSON strings
            $mappings = [];
            foreach ($mappingsInput as $mappingStr) {
                $mapping = json_decode($mappingStr, true);
                if ($mapping) {
                    $mappings[] = $mapping;
                }
            }
            
            $uploadedCount = 0;
            $errors = [];
            
            foreach ($files as $index => $file) {
                try {
                    $fileName = $file->getClientOriginalName();
                    $size = $file->getSize();
                    
                    // Validate filename (allow letters, digits, _ - . space $ ( ) , + #; block path/shell-unsafe chars)
                    if (!preg_match('/^[a-zA-Z0-9_\-\.\s\$(),+#]+$/', $fileName)) {
                        $errors[] = "File '{$fileName}' has invalid characters in name";
                        continue;
                    }
                    
                    // Get mapping for this file
                    $mapping = isset($mappings[$index]) ? $mappings[$index] : null;
                    if (!$mapping || !isset($mapping['name'])) {
                        $errors[] = "No mapping found for file '{$fileName}'";
                        continue;
                    }
                    
                    $checklistName = $mapping['name'] ?? null;
                    if (!$checklistName) {
                        $errors[] = "No checklist name specified for file '{$fileName}'";
                        continue;
                    }
                    
                    // Check if checklist exists in DocumentChecklist table
                    $checklistExists = DocumentChecklist::where('name', $checklistName)
                        ->where('status', 1)
                        ->exists();
                    
                    // If mapping type is 'new' and checklist doesn't exist, create it
                    if ($mapping['type'] === 'new' && !$checklistExists) {
                        // Create new checklist in DocumentChecklist table
                        $newChecklist = new DocumentChecklist();
                        $newChecklist->name = $checklistName;
                        $newChecklist->doc_type = 1; // Default doc_type, adjust if needed
                        $newChecklist->status = 1;
                        $newChecklist->save();
                    }
                    
                    // Check if document record exists (checklist without file)
                    $document = Document::where('client_id', $clientid)
                        ->where('doc_type', $doctype)
                        ->where('checklist', $checklistName)
                        ->where('type', $type)
                        ->whereNull('not_used_doc')
                        ->whereNull('file_name') // Only get checklists without files
                        ->first();
                    
                    // If checklist doesn't exist, create it
                    if (!$document) {
                        $document = new Document();
                        $document->user_id = Auth::user()->id;
                        $document->client_id = $clientid;
                        $document->type = $type;
                        $document->doc_type = $doctype;
                        $document->checklist = $checklistName;
                        
                        // Add category_id if provided
                        if ($request->has('category_id') && $request->category_id) {
                            $document->category_id = $request->category_id;
                        } else {
                            // Default to "General" category
                            $generalCategory = \App\Models\DocumentCategory::where('name', 'General')->where('is_default', true)->first();
                            if ($generalCategory) {
                                $document->category_id = $generalCategory->id;
                            }
                        }
                        
                        $document->save();
                    }
                    
                    // Upload file using S3 (same as checklist upload flow)
                    $nameWithoutExtension = pathinfo($fileName, PATHINFO_FILENAME);
                    $fileExtension = $file->getClientOriginalExtension();
                    $name = time() . $file->getClientOriginalName();
                    $filePath = $client_unique_id . '/' . $doctype . '/' . $name;

                    $s3Result = $this->putFileToS3WithLogging($file->getPathname(), $filePath, [
                        'operation' => 'bulkUploadDocuments',
                        'client_id' => $clientid,
                        'client_unique_id' => $client_unique_id,
                        'doctype' => $doctype,
                        'original_filename' => $fileName,
                        'user_id' => Auth::id(),
                    ]);
                    if (! $s3Result['success']) {
                        $this->s3UploadLog('error', '[S3DocumentUpload] bulk_upload_s3_failed_aborted', [
                            'operation' => 'bulkUploadDocuments',
                            'client_id' => $clientid,
                            'document_id' => $document->id ?? null,
                            's3_key' => $filePath,
                            'error' => $s3Result['error'] ?? 'unknown',
                        ]);
                        $errors[] = "Failed to upload '{$fileName}' to storage. Please try again.";
                        continue;
                    }
                    $disk = $this->s3Disk();
                    $fileUrl = $s3Result['file_url'] ?? $disk->url($filePath);
                    
                    // Update document with file info
                    $document->file_name = $nameWithoutExtension;
                    $document->filetype = $fileExtension;
                    $document->user_id = Auth::user()->id;
                    $document->myfile = $fileUrl;
                    $document->myfile_key = $name;
                    $document->file_size = $size;
                    $document->save();
                    
                    $uploadedCount++;
                    
                } catch (\Exception $e) {
                    $errors[] = "Error uploading '{$fileName}': " . $e->getMessage();
                    Log::error('Bulk upload error for file', [
                        'file' => $fileName,
                        'error' => $e->getMessage()
                    ]);
                }
            }
            
            if ($uploadedCount > 0) {
                // Log activity
                $subject = "bulk uploaded {$uploadedCount} documents";
                $description = "<p>Bulk uploaded {$uploadedCount} documents</p>";
                
                $objs = new ActivitiesLog;
                $objs->client_id = $clientid;
                $objs->created_by = Auth::user()->id;
                $objs->description = $description;
                $objs->subject = $subject;
                $objs->task_status = 0;
                $objs->pin = 0;
                $objs->save();
                
                $response['status'] = true;
                $response['message'] = "Successfully uploaded {$uploadedCount} file(s)";
                $response['uploaded'] = $uploadedCount;
                $response['errors'] = $errors;
            } else {
                $response['message'] = 'No files were uploaded. ' . implode('; ', $errors);
                $response['errors'] = $errors;
            }
            
        } catch (\Exception $e) {
            Log::error('Error in bulk upload', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            $response['message'] = 'An error occurred: ' . $e->getMessage();
        }
        
        return response()->json($response);
    }

    /**
     * Human-friendly download name (not the S3 object key). Format: {file_name}_{Ymd_His}.{ext}
     */
    public static function buildClientDocumentDownloadFilename(string $fileName, string $fileType, ?string $timestamp = null): string
    {
        $timestamp = $timestamp ?? date('Ymd_His');
        $fileName = trim($fileName);
        if ($fileName === '') {
            $fileName = 'document';
        }
        $fileName = str_replace(["\0", '"'], '', $fileName);
        $fileName = preg_replace('/[<>:"|?*\/\\\\]/u', '_', $fileName);
        $fileName = trim($fileName);
        if ($fileName === '') {
            $fileName = 'document';
        }
        $ext = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', (string) $fileType));
        if ($ext === '') {
            $ext = 'bin';
        }

        return $fileName . '_' . $timestamp . '.' . $ext;
    }

    /**
     * Sanitize filename from the request (path traversal, length).
     */
    private function sanitizeDownloadFilename(string $filename): string
    {
        $filename = str_replace(["\0", '../', '..\\'], '', $filename);
        $filename = preg_replace('/[\/\\\\]/', '', $filename);
        $filename = trim($filename, " \t\n\r\0\x0B\"");
        if ($filename === '') {
            $filename = 'document.bin';
        }
        if (strlen($filename) > 200) {
            $filename = substr($filename, 0, 200);
        }

        return $filename;
    }

    /**
     * The default "s3" disk is backed by {@see AwsS3V3Adapter}, which provides url() and temporaryUrl().
     */
    private function s3Disk(): AwsS3V3Adapter
    {
        $disk = Storage::disk('s3');
        if (! $disk instanceof AwsS3V3Adapter) {
            throw new \RuntimeException('The configured "s3" disk must use the S3 driver (AwsS3V3Adapter).');
        }

        return $disk;
    }

    /**
     * Write S3 upload diagnostics to default log and storage/logs/s3-upload.log (no secrets logged).
     */
    private function s3UploadLog(string $level, string $message, array $context = []): void
    {
        $context['log_tag'] = 'S3DocumentUpload';
        Log::log($level, $message, $context);
        Log::channel('s3_upload')->log($level, $message, $context);
    }

    /**
     * Log whether AWS/S3 env config is present (values only, never credentials).
     */
    private function logS3Environment(string $operation, array $extra = []): void
    {
        $this->s3UploadLog('info', '[S3DocumentUpload] environment_check', array_merge([
            'operation' => $operation,
            'aws_access_key_configured' => ! empty(config('filesystems.disks.s3.key')),
            'aws_secret_configured' => ! empty(config('filesystems.disks.s3.secret')),
            'aws_region' => config('filesystems.disks.s3.region'),
            'aws_bucket' => config('filesystems.disks.s3.bucket'),
            'aws_url' => config('filesystems.disks.s3.url'),
            'filesystem_s3_driver' => config('filesystems.disks.s3.driver'),
        ], $extra));
    }

    /**
     * Upload to S3 with detailed logging. Does not throw; preserves legacy caller behaviour.
     *
     * @return array{success: bool, file_url: ?string, error: ?string}
     */
    private function putFileToS3WithLogging(string $localPath, string $s3Key, array $context = []): array
    {
        $logContext = array_merge($context, [
            's3_key' => $s3Key,
            'local_path' => $localPath,
        ]);

        $this->logS3Environment('upload_attempt', $logContext);

        $result = [
            'success' => false,
            'file_url' => null,
            'error' => null,
        ];

        try {
            if (! is_readable($localPath)) {
                $result['error'] = 'Upload temp file is not readable';
                $this->s3UploadLog('error', '[S3DocumentUpload] read_failed', array_merge($logContext, [
                    'error' => $result['error'],
                ]));

                return $result;
            }

            $fileContents = file_get_contents($localPath);
            if ($fileContents === false) {
                $result['error'] = 'Failed to read file contents from upload temp path';
                $this->s3UploadLog('error', '[S3DocumentUpload] read_failed', array_merge($logContext, [
                    'error' => $result['error'],
                ]));

                return $result;
            }

            $this->s3UploadLog('info', '[S3DocumentUpload] read_ok', array_merge($logContext, [
                'file_size_bytes' => strlen($fileContents),
            ]));

            $disk = $this->s3Disk();
            $putResult = $disk->put($s3Key, $fileContents);

            $this->s3UploadLog('info', '[S3DocumentUpload] put_result', array_merge($logContext, [
                'put_returned' => $putResult,
                'put_return_type' => gettype($putResult),
            ]));

            if (! $putResult) {
                $result['error'] = 'Storage::put returned false';
                $this->s3UploadLog('error', '[S3DocumentUpload] put_failed', array_merge($logContext, [
                    'error' => $result['error'],
                ]));

                return $result;
            }

            $exists = $disk->exists($s3Key);
            $this->s3UploadLog('info', '[S3DocumentUpload] exists_check', array_merge($logContext, [
                'exists' => $exists,
            ]));

            if (! $exists) {
                $result['error'] = 'File not found in S3 immediately after put';
                $this->s3UploadLog('error', '[S3DocumentUpload] exists_failed', array_merge($logContext, [
                    'error' => $result['error'],
                ]));

                return $result;
            }

            $fileUrl = $disk->url($s3Key);
            $this->s3UploadLog('info', '[S3DocumentUpload] upload_success', array_merge($logContext, [
                'file_url' => $fileUrl,
            ]));

            $result['success'] = true;
            $result['file_url'] = $fileUrl;

            return $result;
        } catch (\Throwable $e) {
            $result['error'] = $e->getMessage();
            $this->s3UploadLog('error', '[S3DocumentUpload] exception', array_merge($logContext, [
                'error' => $e->getMessage(),
                'exception_class' => get_class($e),
            ]));

            return $result;
        }
    }

    /**
     * Download Document from S3
     */
    public function download_document(Request $request)
    {
        $fileUrl = $this->authorizeDocumentFileAccess($request);
        $filename = $this->sanitizeDownloadFilename((string) $request->input('filename', 'downloaded.pdf'));

        try {
            $tempUrl = $this->buildS3TemporaryAccessUrl(
                $fileUrl,
                'attachment; filename="' . $filename . '"'
            );

            return redirect($tempUrl);
        } catch (\InvalidArgumentException $e) {
            return abort(400, $e->getMessage());
        } catch (\RuntimeException $e) {
            return abort(404, $e->getMessage());
        } catch (\Exception $e) {
            Log::error('S3 download error: ' . $e->getMessage());
            return abort(500, 'Error generating download link');
        }
    }

    /**
     * Preview Document from S3 (inline disposition via presigned URL).
     * Supports ?format=json to return the presigned URL for Office Online embed.
     */
    public function preview_document(Request $request)
    {
        $fileUrl = $this->authorizeDocumentFileAccess($request);
        $filename = $this->sanitizeDownloadFilename((string) $request->input('filename', 'preview.pdf'));

        try {
            $contentType = $this->resolvePreviewContentType($filename);
            $presignOptions = [
                'ResponseContentDisposition' => 'inline; filename="' . $filename . '"',
            ];
            if ($contentType !== null) {
                $presignOptions['ResponseContentType'] = $contentType;
            }

            $tempUrl = $this->buildS3TemporaryAccessUrl(
                $fileUrl,
                'inline; filename="' . $filename . '"',
                $presignOptions
            );

            if ($request->query('format') === 'json' || $request->wantsJson()) {
                return response()->json([
                    'status' => true,
                    'url' => $tempUrl,
                ]);
            }

            return redirect($tempUrl);
        } catch (\InvalidArgumentException $e) {
            $this->s3UploadLog('warning', '[S3DocumentUpload] preview_invalid_url', [
                'operation' => 'preview_document',
                'file_url' => $fileUrl,
                'error' => $e->getMessage(),
            ]);

            return abort(400, $e->getMessage());
        } catch (\RuntimeException $e) {
            $this->s3UploadLog('error', '[S3DocumentUpload] preview_file_not_found', [
                'operation' => 'preview_document',
                'file_url' => $fileUrl,
                'error' => $e->getMessage(),
            ]);

            return abort(404, $e->getMessage());
        } catch (\Exception $e) {
            $this->s3UploadLog('error', '[S3DocumentUpload] preview_error', [
                'operation' => 'preview_document',
                'file_url' => $fileUrl,
                'error' => $e->getMessage(),
            ]);

            return abort(500, 'Error generating preview link');
        }
    }

    /**
     * Render a standalone HTML page that embeds the document (iframe/img) for new-tab preview.
     */
    public function preview_document_view(Request $request)
    {
        try {
            $filelink = $this->authorizeDocumentFileAccess($request);
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            return abort($e->getStatusCode(), $e->getMessage());
        }

        $filename = $this->sanitizeDownloadFilename((string) $request->query('filename', 'preview.pdf'));
        $filetype = strtolower((string) $request->query('filetype', ''));

        if ($this->isS3FileUrl($filelink)) {
            $contentSrc = url('/preview-document') . '?' . http_build_query([
                'filelink' => $filelink,
                'filename' => $filename,
            ]);
            if ($request->filled('document_id')) {
                $contentSrc .= '&document_id=' . urlencode((string) $request->input('document_id'));
            }
        } else {
            $contentSrc = $filelink;
            if (! str_starts_with($contentSrc, 'http://') && ! str_starts_with($contentSrc, 'https://')) {
                $contentSrc = url(ltrim($contentSrc, '/'));
            }
        }

        $isImage = in_array($filetype, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp'], true);

        return view('Admin.documents.preview-viewer', [
            'filename' => $filename,
            'contentSrc' => $contentSrc,
            'isImage' => $isImage,
        ]);
    }

    /**
     * Ensure staff may view/edit the client that owns a document (same rules as client detail).
     */
    private function assertCanAccessDocumentClient($clientId, bool $forEdit = false): Admin
    {
        if ($clientId === null || $clientId === '' || ! is_numeric($clientId)) {
            abort(404, 'Client not found');
        }

        $client = Admin::find((int) $clientId);
        if (! $client) {
            abort(404, 'Client not found');
        }

        $allowed = $forEdit ? $this->canEditClient($client) : $this->canViewClient($client);
        if (! $allowed) {
            abort(403, 'Unauthorized');
        }

        return $client;
    }

    /**
     * JSON document endpoints: null when client missing or staff not allowed.
     */
    private function resolveAccessibleDocumentClientForJson($clientId, bool $forEdit = false): ?Admin
    {
        if ($clientId === null || $clientId === '' || ! is_numeric($clientId)) {
            return null;
        }

        $client = Admin::find((int) $clientId);
        if (! $client) {
            return null;
        }

        $allowed = $forEdit ? $this->canEditClient($client) : $this->canViewClient($client);

        return $allowed ? $client : null;
    }

    /**
     * Authorize download/preview of a file URL for an authenticated admin.
     * filelink must belong to a documents row (myfile / signed_doc_link / derived S3 key).
     * Optional document_id pins the row. Existing clients keep sending only filelink.
     * Staff must be able to view the document's client (allocation / grants).
     *
     * @return string Canonical file URL to presign/serve
     */
    private function authorizeDocumentFileAccess(Request $request): string
    {
        $fileUrl = trim((string) $request->input('filelink', ''));
        $documentIdRaw = $request->input('document_id', $request->input('doc_id'));
        $documentId = is_numeric($documentIdRaw) ? (int) $documentIdRaw : null;

        if ($fileUrl === '' && ! $documentId) {
            $this->s3UploadLog('warning', '[S3DocumentUpload] authorize_missing_filelink', [
                'operation' => 'authorizeDocumentFileAccess',
                'user_id' => Auth::id(),
            ]);
            abort(400, 'Missing file URL');
        }

        if ($fileUrl !== '') {
            if (! $this->isAllowedPreviewFilelink($fileUrl)) {
                abort(400, 'Invalid file URL');
            }
        }

        if ($documentId) {
            $doc = Document::find($documentId);
            if (! $doc) {
                abort(404, 'Document not found');
            }
            if ($fileUrl !== '' && ! $this->documentOwnsFileUrl($doc, $fileUrl)) {
                $this->s3UploadLog('warning', '[S3DocumentUpload] authorize_document_mismatch', [
                    'operation' => 'authorizeDocumentFileAccess',
                    'user_id' => Auth::id(),
                    'document_id' => $documentId,
                ]);
                abort(403, 'File access denied');
            }
            $this->assertCanAccessDocumentClient($doc->client_id ?? null, false);
            if ($fileUrl !== '') {
                return $this->canonicalDocumentFileUrl($doc, $fileUrl);
            }
            $preferred = (string) ($doc->myfile ?: $doc->signed_doc_link ?: '');
            if ($preferred === '') {
                abort(404, 'Document has no file');
            }

            return $preferred;
        }

        $doc = $this->findDocumentByFileUrl($fileUrl);
        if (! $doc) {
            // No CRM ownership: still block open proxying of third-party / random S3 hosts
            if ((str_starts_with($fileUrl, 'http://') || str_starts_with($fileUrl, 'https://'))
                && ! $this->isAllowedStorageHost($fileUrl)) {
                $this->s3UploadLog('warning', '[S3DocumentUpload] authorize_host_denied', [
                    'operation' => 'authorizeDocumentFileAccess',
                    'user_id' => Auth::id(),
                    'host' => parse_url($fileUrl, PHP_URL_HOST),
                ]);
            }
            $this->s3UploadLog('warning', '[S3DocumentUpload] authorize_file_not_in_documents', [
                'operation' => 'authorizeDocumentFileAccess',
                'user_id' => Auth::id(),
                'file_url' => $fileUrl,
            ]);
            abort(403, 'File access denied');
        }

        $this->assertCanAccessDocumentClient($doc->client_id ?? null, false);

        return $this->canonicalDocumentFileUrl($doc, $fileUrl);
    }

    /**
     * Prefer the stored DB URL that maps to the same object (best for S3 exists/presign).
     */
    private function canonicalDocumentFileUrl(Document $doc, string $requestedUrl): string
    {
        foreach ([(string) ($doc->myfile ?? ''), (string) ($doc->signed_doc_link ?? '')] as $stored) {
            if ($stored !== '' && $this->urlsReferToSameObject($requestedUrl, $stored)) {
                return $stored;
            }
        }

        return $requestedUrl;
    }

    private function normalizeDocumentFileUrl(string $url): string
    {
        $url = trim(html_entity_decode($url, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        if (preg_match('#^https?://[^/]+/(https?://.+)$#i', $url, $matches)) {
            $url = $matches[1];
        }

        return rtrim($url, '/');
    }

    private function urlsReferToSameObject(string $a, string $b): bool
    {
        $na = $this->normalizeDocumentFileUrl($a);
        $nb = $this->normalizeDocumentFileUrl($b);
        if ($na !== '' && $na === $nb) {
            return true;
        }
        $ka = $this->resolveS3KeyFromFileUrl($na);
        $kb = $this->resolveS3KeyFromFileUrl($nb);

        return $ka !== null && $kb !== null && $ka === $kb;
    }

    private function documentOwnsFileUrl(Document $doc, string $fileUrl): bool
    {
        $fileUrl = $this->normalizeDocumentFileUrl($fileUrl);
        foreach ([(string) ($doc->myfile ?? ''), (string) ($doc->signed_doc_link ?? '')] as $stored) {
            if ($stored !== '' && $this->urlsReferToSameObject($fileUrl, $stored)) {
                return true;
            }
        }

        $reqKey = $this->resolveS3KeyFromFileUrl($fileUrl);
        if ($reqKey === null) {
            return false;
        }
        foreach ($this->buildS3DeleteKeyCandidates($doc) as $candidate) {
            if ($candidate === $reqKey) {
                return true;
            }
        }

        return false;
    }

    private function findDocumentByFileUrl(string $fileUrl): ?Document
    {
        $norm = $this->normalizeDocumentFileUrl($fileUrl);
        $raw = trim($fileUrl);

        $exact = Document::query()
            ->where(function ($q) use ($norm, $raw) {
                $q->where('myfile', $norm)
                    ->orWhere('myfile', $raw)
                    ->orWhere('signed_doc_link', $norm)
                    ->orWhere('signed_doc_link', $raw);
            })
            ->first();
        if ($exact) {
            return $exact;
        }

        $key = $this->resolveS3KeyFromFileUrl($norm);
        if ($key === null) {
            return null;
        }

        $base = basename($key);
        if ($base === '' || $base === '/' || $base === '.') {
            return null;
        }

        $likeBase = $this->escapeLikeWildcards($base);
        $candidates = Document::query()
            ->where(function ($q) use ($base, $likeBase) {
                $q->where('myfile_key', $base)
                    ->orWhere('myfile', 'like', '%' . $likeBase . '%')
                    ->orWhere('signed_doc_link', 'like', '%' . $likeBase . '%')
                    ->orWhere('myfile_key', 'like', '%' . $likeBase);
            })
            ->orderByDesc('id')
            ->limit(80)
            ->get();

        foreach ($candidates as $row) {
            if ($this->documentOwnsFileUrl($row, $norm)) {
                return $row;
            }
        }

        return null;
    }

    private function escapeLikeWildcards(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
    }

    /**
     * Allow only app host or configured AWS/S3 hosts (blocks arbitrary third-party URLs).
     */
    private function isAllowedStorageHost(string $url): bool
    {
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        if ($host === '') {
            return false;
        }

        $appHost = strtolower((string) parse_url((string) config('app.url'), PHP_URL_HOST));
        if ($appHost !== '' && ($host === $appHost || str_ends_with($host, '.' . $appHost))) {
            return true;
        }

        $bucket = strtolower((string) (config('filesystems.disks.s3.bucket') ?: env('AWS_BUCKET', '')));
        if ($bucket !== '') {
            if ($host === $bucket . '.s3.amazonaws.com' || str_starts_with($host, $bucket . '.s3.')) {
                return true;
            }
            // Path-style s3.<region>.amazonaws.com / s3.amazonaws.com with bucket in path
            if (preg_match('/^s3([.\-]|$)/i', $host) || str_contains($host, '.s3.') || str_contains($host, 'amazonaws.com')) {
                $path = ltrim((string) parse_url($url, PHP_URL_PATH), '/');
                if (str_starts_with(strtolower($path), $bucket . '/')) {
                    return true;
                }
            }
        }

        $awsUrl = (string) (config('filesystems.disks.s3.url') ?: env('AWS_URL', ''));
        if ($awsUrl !== '') {
            $awsHost = strtolower((string) parse_url($awsUrl, PHP_URL_HOST));
            if ($awsHost !== '' && $host === $awsHost) {
                return true;
            }
        }

        // Generic *.amazonaws.com only when path/key can be resolved for our bucket rules above failed
        // but host looks like virtual-hosted-style including bucket name
        if ($bucket !== '' && str_contains($host, 'amazonaws.com') && str_contains($host, $bucket)) {
            return true;
        }

        return false;
    }

    /**
     * Resolve an S3 object key from a stored file URL (virtual-hosted or path-style).
     * Matches key resolution used by preview/download (bucket prefix stripped when present).
     */
    private function resolveS3KeyFromFileUrl(?string $fileUrl): ?string
    {
        if ($fileUrl === null || trim($fileUrl) === '') {
            return null;
        }

        // Relative local filename (legacy non-S3) — not an S3 key
        if (! str_starts_with($fileUrl, 'http://') && ! str_starts_with($fileUrl, 'https://')) {
            return null;
        }

        $parsed = parse_url($fileUrl);
        if (! isset($parsed['path']) || $parsed['path'] === '' || $parsed['path'] === '/') {
            return null;
        }

        $s3Key = ltrim(urldecode($parsed['path']), '/');
        if ($s3Key === '') {
            return null;
        }

        $bucket = (string) (config('filesystems.disks.s3.bucket') ?: env('AWS_BUCKET', ''));
        if ($bucket !== '' && str_starts_with($s3Key, $bucket . '/')) {
            $s3Key = substr($s3Key, strlen($bucket) + 1);
        }

        return $s3Key !== '' ? $s3Key : null;
    }

    /**
     * Candidate S3 keys for a document row (best-effort, for delete/cleanup).
     *
     * @param  object  $data  documents table row
     * @return list<string>
     */
    private function buildS3DeleteKeyCandidates(object $data): array
    {
        $candidates = [];

        // 1) Full key from stored myfile URL (same parse as preview/download)
        if (! empty($data->myfile)) {
            $fromUrl = $this->resolveS3KeyFromFileUrl((string) $data->myfile);
            if ($fromUrl !== null) {
                $candidates[] = $fromUrl;
            }
        }

        // 2) client_unique_id / doc_type / myfile_key (current upload path shape)
        $myfileKey = isset($data->myfile_key) ? trim((string) $data->myfile_key) : '';
        if ($myfileKey !== '' && ! empty($data->client_id)) {
            $adminInfo = Admin::select('client_id')->where('id', $data->client_id)->first();
            $clientUniqueId = $adminInfo && ! empty($adminInfo->client_id)
                ? (string) $adminInfo->client_id
                : '';
            $docType = ! empty($data->doc_type) ? (string) $data->doc_type : 'documents';
            if ($clientUniqueId !== '') {
                $candidates[] = $clientUniqueId . '/' . $docType . '/' . $myfileKey;
            }
        }

        // 3) Legacy: path segments[0]/segments[1]/myfile_key
        if ($myfileKey !== '' && ! empty($data->myfile) && is_string($data->myfile)) {
            $parsedUrl = parse_url((string) $data->myfile);
            if (isset($parsedUrl['path']) && strpos((string) $parsedUrl['path'], '/') !== false) {
                $filePath = ltrim(urldecode((string) $parsedUrl['path']), '/');
                $filePathArr = array_values(array_filter(explode('/', $filePath), static function ($seg) {
                    return $seg !== '';
                }));
                $bucket = (string) (config('filesystems.disks.s3.bucket') ?: env('AWS_BUCKET', ''));
                // Drop leading bucket segment if path-style URL
                if ($bucket !== '' && isset($filePathArr[0]) && $filePathArr[0] === $bucket) {
                    array_shift($filePathArr);
                }
                if (count($filePathArr) >= 2) {
                    $candidates[] = $filePathArr[0] . '/' . $filePathArr[1] . '/' . $myfileKey;
                }
            }
        }

        // Unique, preserve order
        $seen = [];
        $unique = [];
        foreach ($candidates as $key) {
            if ($key === '' || isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $unique[] = $key;
        }

        return $unique;
    }

    /**
     * Best-effort S3 object delete for a document row. Never throws; logs outcomes.
     * Returns true if an object was deleted, false if none found/deleted.
     */
    private function tryDeleteDocumentS3Object(object $data): bool
    {
        $candidates = $this->buildS3DeleteKeyCandidates($data);
        if ($candidates === []) {
            $this->s3UploadLog('info', '[S3DocumentUpload] deletealldocs_no_s3_candidates', [
                'operation' => 'deletealldocs',
                'document_id' => $data->id ?? null,
                'myfile' => $data->myfile ?? null,
                'myfile_key' => $data->myfile_key ?? null,
            ]);

            return false;
        }

        try {
            $disk = $this->s3Disk();
        } catch (\Throwable $e) {
            $this->s3UploadLog('warning', '[S3DocumentUpload] deletealldocs_s3_disk_unavailable', [
                'operation' => 'deletealldocs',
                'document_id' => $data->id ?? null,
                'error' => $e->getMessage(),
            ]);

            return false;
        }

        foreach ($candidates as $s3Key) {
            try {
                if ($disk->exists($s3Key)) {
                    $disk->delete($s3Key);
                    $this->s3UploadLog('info', '[S3DocumentUpload] deletealldocs_s3_deleted', [
                        'operation' => 'deletealldocs',
                        'document_id' => $data->id ?? null,
                        's3_key' => $s3Key,
                        'candidates_tried' => $candidates,
                    ]);

                    return true;
                }
            } catch (\Throwable $e) {
                $this->s3UploadLog('warning', '[S3DocumentUpload] deletealldocs_s3_delete_attempt_failed', [
                    'operation' => 'deletealldocs',
                    'document_id' => $data->id ?? null,
                    's3_key' => $s3Key,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->s3UploadLog('warning', '[S3DocumentUpload] deletealldocs_s3_object_not_found', [
            'operation' => 'deletealldocs',
            'document_id' => $data->id ?? null,
            'candidates' => $candidates,
            'myfile' => $data->myfile ?? null,
            'myfile_key' => $data->myfile_key ?? null,
        ]);

        return false;
    }

    /**
     * Build a short-lived presigned S3 URL from a stored file URL.
     *
     * @param  array<string, string>|null  $presignOptions  Extra S3 presign params (e.g. ResponseContentType)
     */
    private function buildS3TemporaryAccessUrl(string $fileUrl, string $contentDisposition, ?array $presignOptions = null): string
    {
        $s3Key = $this->resolveS3KeyFromFileUrl($fileUrl);
        if ($s3Key === null) {
            throw new \InvalidArgumentException('Invalid S3 URL format');
        }

        $disk = $this->s3Disk();
        if (! $disk->exists($s3Key)) {
            $this->s3UploadLog('error', '[S3DocumentUpload] s3_key_not_found', [
                'operation' => 'buildS3TemporaryAccessUrl',
                's3_key' => $s3Key,
                'file_url' => $fileUrl,
                'bucket' => env('AWS_BUCKET'),
                'region' => env('AWS_DEFAULT_REGION'),
            ]);
            throw new \RuntimeException('File not found in S3');
        }

        $options = $presignOptions ?? [
            'ResponseContentDisposition' => $contentDisposition,
        ];
        if (! isset($options['ResponseContentDisposition'])) {
            $options['ResponseContentDisposition'] = $contentDisposition;
        }

        return $disk->temporaryUrl(
            $s3Key,
            now()->addMinutes(5),
            $options
        );
    }

    private function resolvePreviewContentType(string $filename): ?string
    {
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        return match ($ext) {
            'pdf' => 'application/pdf',
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'bmp' => 'image/bmp',
            default => null,
        };
    }

    private function isS3FileUrl(string $url): bool
    {
        return (bool) preg_match('/amazonaws\.com/i', $url) || (bool) preg_match('/\.s3[\.\-]/i', $url);
    }

    private function isAllowedPreviewFilelink(string $filelink): bool
    {
        $filelink = trim($filelink);
        if ($filelink === '') {
            return false;
        }
        if (preg_match('/^\s*(javascript|data|vbscript):/i', $filelink)) {
            return false;
        }
        if (str_starts_with($filelink, 'http://') || str_starts_with($filelink, 'https://')) {
            return (bool) filter_var($filelink, FILTER_VALIDATE_URL);
        }
        if (str_starts_with($filelink, '/')) {
            return ! str_contains($filelink, '..');
        }

        return ! str_contains($filelink, '..');
    }
    //Back To Document List From Not Used Tab
    public function backtodoc(Request $request){ //dd($request->all());
		$doc_id = $request->doc_id;
        $doc_type = $request->doc_type;
        if(\App\Models\Document::where('id',$doc_id)->exists()){
            $upd = DB::table('documents')->where('id', $doc_id)->update(array('not_used_doc' => null));
            if($upd){
                $docInfo = \App\Models\Document::where('id',$doc_id)->first();
                $subject = $doc_type.' document moved to document tab';
                $objs = new ActivitiesLog;
				$objs->client_id = $docInfo->client_id;
				$objs->created_by = Auth::user()->id;
				$objs->description = '';
				$objs->subject = $subject;
				$objs->task_status = 0; // Required NOT NULL field (0 = activity, 1 = task)
				$objs->pin = 0; // Required NOT NULL field (0 = not pinned, 1 = pinned)
				$objs->save();

                if($docInfo){
                    if( isset($docInfo->user_id) && $docInfo->user_id!= "" ){
                        $adminInfo = ClientDocumentStaffResolver::firstNameRowByStaffId($docInfo->user_id);
                        $response['Added_By'] = $adminInfo->first_name;
                        $response['Added_date'] = date('d/m/Y',strtotime($docInfo->created_at));
                    } else {
                        $response['Added_By'] = "N/A";
                        $response['Added_date'] = "N/A";
                    }


                    if( isset($docInfo->checklist_verified_by) && $docInfo->checklist_verified_by!= "" ){
                        $verifyInfo = ClientDocumentStaffResolver::firstNameRowStaffThenAdmin($docInfo->checklist_verified_by);
                        $response['Verified_By'] = $verifyInfo->first_name;
                        $response['Verified_At'] = date('d/m/Y',strtotime($docInfo->checklist_verified_at));
                    } else {
                        $response['Verified_By'] = "N/A";
                        $response['Verified_At'] = "N/A";
                    }

                }

                $response['docInfo'] = $docInfo;
                $response['doc_type'] = $doc_type;
                $response['doc_id'] = $doc_id;
				$response['status'] = 	true;
				$response['data']	=	$doc_type.' document moved to document tab';
			} else {
				$response['status'] 	= 	false;
				$response['message']	=	'Please try again';
                $response['doc_type'] = "";
                $response['doc_id'] = "";
                $response['docInfo'] = "";

                $response['Added_By'] = "";
                $response['Added_date'] = "";
                $response['Verified_By'] = "";
                $response['Verified_At'] = "";
			}
		} else {
			$response['status'] 	= 	false;
			$response['message']	=	'Please try again';
            $response['doc_type'] = "";
            $response['doc_id'] = "";
            $response['docInfo'] = "";

            $response['Added_By'] = "";
            $response['Added_date'] = "";
            $response['Verified_By'] = "";
            $response['Verified_At'] = "";
		}
		echo json_encode($response);
	}
     //Delete all document
     public function deletealldocs(Request $request){
		$note_id = $request->note_id;
        if(\App\Models\Document::where('id',$note_id)->exists()){
            $data = DB::table('documents')->where('id', @$note_id)->first();

            // Best-effort S3 cleanup before DB delete. Failures never block row removal.
            if ($data && (
                (! empty($data->myfile_key)) || (! empty($data->myfile) && is_string($data->myfile) && str_starts_with((string) $data->myfile, 'http'))
            )) {
                $this->tryDeleteDocumentS3Object($data);
            }

            $res = DB::table('documents')->where('id', @$note_id)->delete();
            if($res){
                $subject = 'deleted a document';
                $objs = new ActivitiesLog;
				$objs->client_id = $data->client_id;
				$objs->created_by = Auth::user()->id;
				$objs->description = '';
				$objs->subject = $subject;
				$objs->task_status = 0; // Required NOT NULL field (0 = activity, 1 = task)
				$objs->pin = 0; // Required NOT NULL field (0 = not pinned, 1 = pinned)
				$objs->save();
				$response['status'] 	= 	true;
				$response['data']	=	'Document removed successfully';
			} else {
				$response['status'] 	= 	false;
				$response['message']	=	'Please try again';
			}
		} else {
			$response['status'] 	= 	false;
			$response['message']	=	'Please try again';
		}
		echo json_encode($response);
	}
  
    //Rename all document
    public function renamealldoc(Request $request){
		$id = $request->id;
		$filename = $request->filename;
		if(\App\Models\Document::where('id',$id)->exists()){
			$doc = \App\Models\Document::where('id',$id)->first();
			$res = DB::table('documents')->where('id', @$id)->update(['file_name' => $filename]);
			if($res){
				$response['status'] 	= 	true;
				$response['data']	=	'Document saved successfully';
				$response['Id']	=	$id;
				$response['filename']	=	$filename;
				$response['filetype']	=	$doc->filetype;
			}else{
				$response['status'] 	= 	false;
				$response['message']	=	'Please try again';
			}
		}else{
			$response['status'] 	= 	false;
			$response['message']	=	'Please try again';
		}
		echo json_encode($response);
	}
  
    //Not Used Document
    public function notuseddoc(Request $request){ //dd($request->all());
		$doc_id = $request->doc_id;
        $doc_type = $request->doc_type;
        if(\App\Models\Document::where('id',$doc_id)->exists()){
            $upd = DB::table('documents')->where('id', $doc_id)->update(array('not_used_doc' => 1));
            if($upd){
                $docInfo = \App\Models\Document::where('id',$doc_id)->first();
                $subject = $doc_type.' document moved to Not Used Tab';
                $objs = new ActivitiesLog;
				$objs->client_id = $docInfo->client_id;
				$objs->created_by = Auth::user()->id;
				$objs->description = '';
				$objs->subject = $subject;
				$objs->task_status = 0; // Required NOT NULL field (0 = activity, 1 = task)
				$objs->pin = 0; // Required NOT NULL field (0 = not pinned, 1 = pinned)
				$objs->save();

                if($docInfo){
                    if( isset($docInfo->user_id) && $docInfo->user_id!= "" ){
                        $adminInfo = ClientDocumentStaffResolver::firstNameRowByStaffId($docInfo->user_id);
                        $response['Added_By'] = $adminInfo->first_name;
                        $response['Added_date'] = date('d/m/Y',strtotime($docInfo->created_at));
                    } else {
                        $response['Added_By'] = "N/A";
                        $response['Added_date'] = "N/A";
                    }


                    if( isset($docInfo->checklist_verified_by) && $docInfo->checklist_verified_by!= "" ){
                        $verifyInfo = ClientDocumentStaffResolver::firstNameRowStaffThenAdmin($docInfo->checklist_verified_by);
                        $response['Verified_By'] = $verifyInfo->first_name;
                        $response['Verified_At'] = date('d/m/Y',strtotime($docInfo->checklist_verified_at));
                    } else {
                        $response['Verified_By'] = "N/A";
                        $response['Verified_At'] = "N/A";
                    }

                }

                $response['docInfo'] = $docInfo;
                $response['doc_type'] = $doc_type;
                $response['doc_id'] = $doc_id;
				$response['status'] = 	true;
				$response['data']	=	$doc_type.' document moved to Not Used Tab';
			} else {
				$response['status'] 	= 	false;
				$response['message']	=	'Please try again';
                $response['doc_type'] = "";
                $response['doc_id'] = "";
                $response['docInfo'] = "";

                $response['Added_By'] = "";
                $response['Added_date'] = "";
                $response['Verified_By'] = "";
                $response['Verified_At'] = "";
			}
		} else {
			$response['status'] 	= 	false;
			$response['message']	=	'Please try again';
            $response['doc_type'] = "";
            $response['doc_id'] = "";
            $response['docInfo'] = "";

            $response['Added_By'] = "";
            $response['Added_date'] = "";
            $response['Verified_By'] = "";
            $response['Verified_At'] = "";
		}
		echo json_encode($response);
	}

    //Rename checklist in Document
    public function renamechecklistdoc(Request $request){
		$id = $request->id;
		$checklist = $request->checklist;
		if(\App\Models\Document::where('id',$id)->exists()){
			$doc = \App\Models\Document::where('id',$id)->first();
			$res = DB::table('documents')->where('id', @$id)->update(['checklist' => $checklist]);
			if($res){
				$response['status'] 	= 	true;
				$response['data']	=	'Checklist saved successfully';
				$response['Id']	=	$id;
				$response['checklist']	=	$checklist;
			}else{
				$response['status'] 	= 	false;
				$response['message']	=	'Please try again';
			}
		}else{
			$response['status'] 	= 	false;
			$response['message']	=	'Please try again';
		}
		echo json_encode($response);
	}

	//Add All Doc checklist
    public function addalldocchecklist(Request $request){ //dd($request->all());
        try {
            $response = ['status' => false, 'message' => 'Please try again'];
            $clientid = $request->clientid;
            
            if(empty($clientid)) {
                $response['message'] = 'Client ID is required';
                echo json_encode($response);
                return;
            }
            
            $admin_info1 = Admin::select('client_id')->where('id', $clientid)->first(); //dd($admin);
            if(!empty($admin_info1)){
                $client_unique_id = $admin_info1->client_id;
            } else {
                $client_unique_id = "";
            }  //dd($client_unique_id);
            $doctype = isset($request->doctype)? $request->doctype : '';

            if ($request->has('checklist'))
        {
            $checklistArray = $request->input('checklist'); //dd($checklistArray);
            if (is_array($checklistArray) && !empty($checklistArray))
            {
                $saved = false;
                foreach ($checklistArray as $item)
                {
                    if(empty($item)) continue; // Skip empty items
                    $obj = new \App\Models\Document;
                    $obj->user_id = Auth::user()->id;
                    $obj->client_id = $clientid;
                    $obj->type = $request->type;
                    $obj->doc_type = $doctype;
                    $obj->checklist = $item;
                    
                    // Add category_id if provided
                    if ($request->has('category_id') && $request->category_id) {
                        $obj->category_id = $request->category_id;
                    } else {
                        // Default to "General" category if no category specified
                        $generalCategory = \App\Models\DocumentCategory::where('name', 'General')->where('is_default', true)->first();
                        if ($generalCategory) {
                            $obj->category_id = $generalCategory->id;
                        }
                    }
                    
                    $saved = $obj->save();
                } //end foreach

                if($saved)
                {
                    if($request->type == 'client'){
                        $subject = 'added document checklist';
                        $objs = new ActivitiesLog;
                        $objs->client_id = $clientid;
                        $objs->created_by = Auth::user()->id;
                        $objs->description = '';
                        $objs->subject = $subject;
                        $objs->task_status = 0; // Required NOT NULL field (0 = activity, 1 = task)
                        $objs->pin = 0; // Required NOT NULL field (0 = not pinned, 1 = pinned)
                        $objs->save();
                    }

                    $response['status'] 	= 	true;
                    $response['message']	=	'You have successfully added your document checklist';
                    
                    // Check if this is an AJAX request
                    if($request->ajax() || $request->wantsJson()) {
                        return response()->json($response, 200, ['Content-Type' => 'application/json']);
                    }

                    $fetchd = \App\Models\Document::where('client_id',$clientid)->whereNull('not_used_doc')->where('doc_type',$doctype)->where('type',$request->type)->orderBy('updated_at', 'DESC')->get();
                    ob_start();
                    foreach($fetchd as $docKey=>$fetch)
                    {
                        $admin = ClientDocumentStaffResolver::staffRowById($fetch->user_id);
                        $addedByInfo = ($admin ? $admin->first_name : 'N/A') . ' on ' . date('d/m/Y', strtotime($fetch->created_at));
                        //Checklist verified by
                        /*if( isset($fetch->checklist_verified_by) && $fetch->checklist_verified_by != "") {
                            $checklist_verified_Info = ClientDocumentStaffResolver::firstNameRowStaffThenAdmin($fetch->checklist_verified_by);
                            $checklist_verified_by = $checklist_verified_Info->first_name;
                        } else {
                            $checklist_verified_by = 'N/A';
                        }

                        if( isset($fetch->checklist_verified_at) && $fetch->checklist_verified_at != "") {
                            $checklist_verified_at = date('d/m/Y', strtotime($fetch->checklist_verified_at));
                        } else {
                            $checklist_verified_at = 'N/A';
                        }*/
                        ?>
                        <tr class="drow document-row" id="id_<?php echo $fetch->id; ?>"
                            data-doc-id="<?php echo $fetch->id;?>"
                            data-checklist-name="<?php echo htmlspecialchars($fetch->checklist, ENT_QUOTES, 'UTF-8'); ?>"
                            data-file-name="<?php echo htmlspecialchars($fetch->file_name ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                            data-file-type="<?php echo htmlspecialchars($fetch->filetype ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                            data-myfile="<?php echo htmlspecialchars($fetch->myfile ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                            data-myfile-key="<?php echo isset($fetch->myfile_key) ? htmlspecialchars($fetch->myfile_key, ENT_QUOTES, 'UTF-8') : ''; ?>"
                            data-doc-type="<?php echo htmlspecialchars($fetch->doc_type, ENT_QUOTES, 'UTF-8'); ?>"
                            data-user-role="<?php echo Auth::user()->role; ?>"
                            title="Added by: <?php echo htmlspecialchars($addedByInfo, ENT_QUOTES, 'UTF-8'); ?>"
                            style="cursor: context-menu;">
                            <td style="white-space: initial;">
                                <div data-id="<?php echo $fetch->id;?>" data-personalchecklistname="<?php echo $fetch->checklist; ?>" class="personalchecklist-row">
                                    <span><?php echo $fetch->checklist; ?></span>
                                </div>
                            </td>
                            <td style="white-space: initial;">
                                <?php
                                if( isset($fetch->file_name) && $fetch->file_name !=""){ ?>
                                    <div data-id="<?php echo $fetch->id; ?>" data-name="<?php echo $fetch->file_name; ?>" class="doc-row">
                                        <?php if( isset($fetch->myfile_key) && $fetch->myfile_key != ""){ //For new file upload ?>
                                            <a href="javascript:void(0);" onclick="previewFile('<?php echo $fetch->filetype;?>','<?php echo \App\Helpers\Helper::documentFileUrlAttr($fetch->myfile); ?>','preview-container-alldocumentlist')">
                                                <?php echo \App\Helpers\IconHelper::render('file-image'); ?> <span><?php echo $fetch->file_name . '.' . $fetch->filetype; ?></span>
                                            </a>
                                        <?php } else {  //For old file upload
                                            $url = 'https://'.env('AWS_BUCKET').'.s3.'. env('AWS_DEFAULT_REGION') . '.amazonaws.com/';
                                            $myawsfile = $url.$client_unique_id.'/'.$fetch->doc_type.'/'.$fetch->myfile;
                                            ?>
                                            <a href="javascript:void(0);" onclick="previewFile('<?php echo $fetch->filetype;?>','<?php echo \App\Helpers\Helper::documentFileUrlAttr($myawsfile); ?>','preview-container-alldocumentlist')">
                                                <?php echo \App\Helpers\IconHelper::render('file-image'); ?> <span><?php echo $fetch->file_name . '.' . $fetch->filetype; ?></span>
                                            </a>
                                        <?php } ?>
                                    </div>
                                <?php
                                }
                                else
                                {?>
                                    <div class="allupload_document" style="display:inline-block;">
                                        <form method="POST" enctype="multipart/form-data" id="upload_form_<?php echo $fetch->id;?>">
                                            <input type="hidden" name="_token" value="<?php echo csrf_token();?>" />
                                            <input type="hidden" name="clientid" value="<?php echo $clientid;?>">
                                            <input type="hidden" name="fileid" value="<?php echo $fetch->id;?>">
                                            <input type="hidden" name="type" value="client">
                                            <input type="hidden" name="doctype" value="documents">
                                            <a href="javascript:;" class="btn btn-primary"><?php echo \App\Helpers\IconHelper::render('plus'); ?> Add Document</a>
                                            <input class="alldocupload" data-fileid="<?php echo $fetch->id;?>" type="file" name="document_upload"/>
                                        </form>
                                    </div>
                                <?php
                                }?>
                            </td>
                            <!--<td id="docverifiedby_<?php //echo $fetch->id;?>">
                                <?php
                                //echo $checklist_verified_by. "<br>";
                                //echo $checklist_verified_at;
                                ?>
                            </td>-->
                        </tr>
			        <?php
			        } //end foreach

                    $data = ob_get_clean();
                    ob_start();
                    foreach($fetchd as $fetch)
                    {
                        $admin = ClientDocumentStaffResolver::staffRowById($fetch->user_id);
                        ?>
                        <div class="grid_list">
                            <div class="grid_col">
                                <div class="grid_icon">
                                    <?php echo \App\Helpers\IconHelper::render('file-image'); ?>
                                </div>
                                <div class="grid_content">
                                    <span id="grid_<?php echo $fetch->id; ?>" class="gridfilename"><?php echo $fetch->file_name; ?></span>
                                    <div class="dropdown d-inline dropdown_ellipsis_icon">
                                        <a class="dropdown-toggle" type="button" id="" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><?php echo \App\Helpers\IconHelper::render('ellipsis-v'); ?></a>
                                        <div class="dropdown-menu">
                                            <?php
                                            //$url = 'https://'.env('AWS_BUCKET').'.s3.'. env('AWS_DEFAULT_REGION') . '.amazonaws.com/';
                                            ?>
                                            <?php if( isset($fetch->myfile) && $fetch->myfile != ""){?>
                                            <!--<a class="dropdown-item" href="<?php //echo $url.$client_unique_id.'/'.$doctype.'/'.$fetch->myfile; ?>">Preview</a>
                                            <a download class="dropdown-item" href="<?php //echo $url.$client_unique_id.'/'.$doctype.'/'.$fetch->myfile; ?>">Download</a>-->

                                            <!--<a class="dropdown-item" href="<?php //echo $fetch->myfile; ?>">Preview</a>
                                            <a download class="dropdown-item" href="<?php //echo $fetch->myfile; ?>">Download</a>-->
                                          
                                            <a class="dropdown-item" href="javascript:void(0);" onclick="previewFile('<?php echo $fetch->filetype;?>','<?php echo \App\Helpers\Helper::documentFileUrlAttr($fetch->myfile); ?>','preview-container-alldocumentlist')">Preview</a>
                                            <?php
                                                $suggestedDlName = self::buildClientDocumentDownloadFilename(
                                                    (string) ($fetch->file_name ?? 'document'),
                                                    (string) ($fetch->filetype ?? '')
                                                );
                                            ?>
                                            <a href="<?php echo url('/download-document') . '?filelink=' . urlencode($fetch->myfile) . '&filename=' . urlencode($suggestedDlName); ?>" class="dropdown-item download-file" data-filelink="<?php echo htmlspecialchars($fetch->myfile, ENT_QUOTES, 'UTF-8'); ?>" data-filename="<?php echo htmlspecialchars($suggestedDlName, ENT_QUOTES, 'UTF-8'); ?>" data-dl-base="<?php echo htmlspecialchars((string) ($fetch->file_name ?? ''), ENT_QUOTES, 'UTF-8'); ?>" data-dl-ext="<?php echo htmlspecialchars((string) ($fetch->filetype ?? ''), ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener">Download</a>


                                            <?php if( Auth::user()->role == 1 ){ //echo Auth::user()->role;//super admin ?>
                                                <a data-id="<?php echo $fetch->id; ?>" class="dropdown-item deletenote" data-href="deletealldocs" href="javascript:;" >Delete</a>
                                            <?php }?>
                                            <a data-id="<?php echo $fetch->id; ?>" class="dropdown-item verifydoc" data-doctype="documents" data-href="verifydoc" href="javascript:;">Verify</a>
                                            <a data-id="<?php echo $fetch->id; ?>" class="dropdown-item notuseddoc" data-doctype="documents" data-href="notuseddoc" href="javascript:;">Not Used</a>
                                            <?php }?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php
                    } //end foreach
                    $griddata = ob_get_clean();
                    $response['data']	= $data;
                    $response['griddata'] = $griddata;
                } //end if
                else
                {
                    $response['status'] = false;
                    $response['message'] = 'Please try again';
                } //end else
            } //end if
            else
            {
                $response['status'] = false;
                $response['message'] = 'Please try again';
            } //end else
        }
        else
        {
            $response['status'] = false;
            $response['message'] = 'Please try again';
        } //end else
        echo json_encode($response);
        } catch (\Exception $e) {
            Log::error('Error in addalldocchecklist: ' . $e->getMessage() . ' at line ' . $e->getLine());
            $response = ['status' => false, 'message' => 'An error occurred: ' . $e->getMessage()];
            echo json_encode($response);
        }
    }

    //Update all Document upload
	public function uploadalldocument(Request $request){ //dd($request->all());
        if ($request->hasfile('document_upload'))
        {
            $clientid = $request->clientid;
            $admin_info1 = Admin::select('client_id')->where('id', $clientid)->first(); //dd($admin);
            if(!empty($admin_info1)){
                $client_unique_id = $admin_info1->client_id;
            } else {
                $client_unique_id = "";
            }  //dd($client_unique_id);

            $doctype = isset($request->doctype)? $request->doctype : '';

            $files = $request->file('document_upload');
            $size = $files->getSize();
            $fileName = $files->getClientOriginalName();
            $explodeFileName = explode('.', $fileName);
            $nameWithoutExtension = pathinfo($fileName, PATHINFO_FILENAME);
            $fileExtension = $files->getClientOriginalExtension();
            //echo $nameWithoutExtension."===".$fileExtension;
            $name = time() . $files->getClientOriginalName();
            $filePath = $client_unique_id.'/'.$doctype.'/'. $name;

            $this->s3UploadLog('info', '[S3DocumentUpload] uploadalldocument_started', [
                'operation' => 'uploadalldocument',
                'client_id' => $clientid,
                'client_unique_id' => $client_unique_id,
                'document_id' => $request->fileid,
                'doctype' => $doctype,
                'original_filename' => $fileName,
                'file_size_bytes' => $size,
                's3_key' => $filePath,
                'user_id' => Auth::id(),
            ]);

            if ($client_unique_id === '') {
                $this->s3UploadLog('warning', '[S3DocumentUpload] uploadalldocument_empty_client_unique_id', [
                    'operation' => 'uploadalldocument',
                    'client_id' => $clientid,
                    'document_id' => $request->fileid,
                    's3_key' => $filePath,
                ]);
            }

            $s3Result = $this->putFileToS3WithLogging($files->getPathname(), $filePath, [
                'operation' => 'uploadalldocument',
                'client_id' => $clientid,
                'client_unique_id' => $client_unique_id,
                'document_id' => $request->fileid,
                'doctype' => $doctype,
                'original_filename' => $fileName,
                'user_id' => Auth::id(),
            ]);

            // Do not update DB / pretend success when S3 put failed (avoids UI links that 404)
            if (! $s3Result['success']) {
                $this->s3UploadLog('error', '[S3DocumentUpload] uploadalldocument_s3_failed_aborted', [
                    'operation' => 'uploadalldocument',
                    'client_id' => $clientid,
                    'document_id' => $request->fileid,
                    's3_key' => $filePath,
                    'error' => $s3Result['error'] ?? 'unknown',
                    'user_id' => Auth::id(),
                ]);
                $response = [
                    'status' => false,
                    'message' => 'File upload to storage failed. Please try again.',
                ];
                echo json_encode($response);

                return;
            }

            $disk = $this->s3Disk();
            $exploadename = explode('.', $name);

            $req_file_id = $request->fileid;
            $obj = \App\Models\Document::find($req_file_id);
            if (! $obj) {
                $this->s3UploadLog('error', '[S3DocumentUpload] uploadalldocument_missing_document_row', [
                    'operation' => 'uploadalldocument',
                    'client_id' => $clientid,
                    'document_id' => $req_file_id,
                    's3_key' => $filePath,
                ]);
                // Best-effort cleanup of the S3 object we just wrote (orphan if find failed)
                try {
                    if ($disk->exists($filePath)) {
                        $disk->delete($filePath);
                    }
                } catch (\Throwable $e) {
                    $this->s3UploadLog('warning', '[S3DocumentUpload] uploadalldocument_orphan_cleanup_failed', [
                        'operation' => 'uploadalldocument',
                        's3_key' => $filePath,
                        'error' => $e->getMessage(),
                    ]);
                }
                $response = [
                    'status' => false,
                    'message' => 'Document record not found. Please refresh and try again.',
                ];
                echo json_encode($response);

                return;
            }

            $obj->file_name = $nameWithoutExtension; //$explodeFileName[0];
            $obj->filetype = $fileExtension;//$exploadename[1];
            $obj->user_id = Auth::user()->id;
            //$obj->myfile = $name;
            // Get the full URL of the uploaded file
            $fileUrl = $s3Result['file_url'] ?? $disk->url($filePath);
            $obj->myfile = $fileUrl;
            $obj->myfile_key = $name;

            $obj->type = $request->type;
            $obj->file_size = $size;
            $obj->doc_type = $doctype;
            
            // Assign to default "General" category if doc_type is 'documents' and no category_id is set
            if ($doctype == 'documents' && !$obj->category_id) {
                $generalCategory = \App\Models\DocumentCategory::where('name', 'General')
                    ->where('is_default', true)
                    ->first();
                if ($generalCategory) {
                    $obj->category_id = $generalCategory->id;
                }
            }
            
            $saved = $obj->save();

            $this->s3UploadLog($saved ? 'info' : 'error', '[S3DocumentUpload] uploadalldocument_db_save', [
                'operation' => 'uploadalldocument',
                'client_id' => $clientid,
                'document_id' => $req_file_id,
                'saved' => (bool) $saved,
                's3_upload_success' => true,
                'file_url_saved' => $fileUrl,
                's3_key' => $filePath,
            ]);

			if($saved){
				if($request->type == 'client'){
                    $subject = 'uploaded document';
                    $objs = new ActivitiesLog;
                    $objs->client_id = $clientid;
                    $objs->created_by = Auth::user()->id;
                    $objs->description = '';
                    $objs->subject = $subject;
                    $objs->task_status = 0; // Required NOT NULL field for PostgreSQL (0 = activity, 1 = task)
                    $objs->pin = 0; // Required NOT NULL field for PostgreSQL (0 = not pinned, 1 = pinned)
                    $objs->save();
                }
				$response['status'] 	= 	true;
				$response['message']	=	'You have successfully uploaded your document';
			$fetchd = \App\Models\Document::where('client_id',$clientid)->whereNull('not_used_doc')->where('doc_type',$doctype)->where('type',$request->type)->orderByRaw('updated_at DESC NULLS LAST')->get();
			ob_start();
			foreach($fetchd as  $docKey=>$fetch){
				$admin = ClientDocumentStaffResolver::staffRowById($fetch->user_id);
					$addedByInfo = $admin->first_name . ' on ' . date('d/m/Y', strtotime($fetch->created_at));
					?>
					<tr class="drow document-row" id="id_<?php echo $fetch->id; ?>" 
						data-doc-id="<?php echo $fetch->id;?>"
						data-checklist-name="<?php echo htmlspecialchars($fetch->checklist, ENT_QUOTES, 'UTF-8'); ?>"
						data-file-name="<?php echo htmlspecialchars($fetch->file_name, ENT_QUOTES, 'UTF-8'); ?>"
						data-file-type="<?php echo htmlspecialchars($fetch->filetype, ENT_QUOTES, 'UTF-8'); ?>"
						data-myfile="<?php echo htmlspecialchars($fetch->myfile, ENT_QUOTES, 'UTF-8'); ?>"
						data-myfile-key="<?php echo isset($fetch->myfile_key) ? htmlspecialchars($fetch->myfile_key, ENT_QUOTES, 'UTF-8') : ''; ?>"
						data-doc-type="<?php echo htmlspecialchars($fetch->doc_type, ENT_QUOTES, 'UTF-8'); ?>"
						data-user-role="<?php echo Auth::user()->role; ?>"
						title="Added by: <?php echo htmlspecialchars($addedByInfo, ENT_QUOTES, 'UTF-8'); ?>"
						style="cursor: context-menu;">
						<td style="white-space: initial;">
							<div data-id="<?php echo $fetch->id;?>" data-personalchecklistname="<?php echo $fetch->checklist; ?>" class="personalchecklist-row">
								<span><?php echo $fetch->checklist; ?></span>
							</div>
						</td>
						<td style="white-space: initial;">
							<?php
							if( isset($fetch->file_name) && $fetch->file_name !=""){ ?>
								<div data-id="<?php echo $fetch->id; ?>" data-name="<?php echo $fetch->file_name; ?>" class="doc-row">
									<?php if( isset($fetch->myfile_key) && $fetch->myfile_key != ""){ //For new file upload ?>
										<a href="javascript:void(0);" onclick="previewFile('<?php echo $fetch->filetype;?>','<?php echo \App\Helpers\Helper::documentFileUrlAttr($fetch->myfile); ?>','preview-container-alldocumentlist')">
											<?php echo \App\Helpers\IconHelper::render('file-image'); ?> <span><?php echo $fetch->file_name . '.' . $fetch->filetype; ?></span>
										</a>
									<?php } else {  //For old file upload
										$url = 'https://'.env('AWS_BUCKET').'.s3.'. env('AWS_DEFAULT_REGION') . '.amazonaws.com/';
										$myawsfile = $url.$client_unique_id.'/'.$fetch->doc_type.'/'.$fetch->myfile;
										?>
										<a href="javascript:void(0);" onclick="previewFile('<?php echo $fetch->filetype;?>','<?php echo \App\Helpers\Helper::documentFileUrlAttr($myawsfile); ?>','preview-container-alldocumentlist')">
											<?php echo \App\Helpers\IconHelper::render('file-image'); ?> <span><?php echo $fetch->file_name . '.' . $fetch->filetype; ?></span>
										</a>
									<?php } ?>
								</div>
							<?php
							}
							else
							{?>
								<div class="allupload_document" style="display:inline-block;">
									<form method="POST" enctype="multipart/form-data" id="upload_form_<?php echo $fetch->id;?>">
										<input type="hidden" name="_token" value="<?php echo csrf_token();?>" />
										<input type="hidden" name="clientid" value="<?php echo $fetch->client_id;?>">
										<input type="hidden" name="fileid" value="<?php echo $fetch->id;?>">
										<input type="hidden" name="type" value="client">
										<input type="hidden" name="doctype" value="documents">
										<a href="javascript:;" class="btn btn-primary"><?php echo \App\Helpers\IconHelper::render('plus'); ?> Add Document</a>
										<input class="alldocupload" data-fileid="<?php echo $fetch->id;?>" type="file" name="document_upload"/>
									</form>
								</div>
							<?php
							}?>
						</td>
					</tr>
					<?php
				}
				$data = ob_get_clean();
				ob_start();
				foreach($fetchd as $fetch){
					$admin = ClientDocumentStaffResolver::staffRowById($fetch->user_id);
					?>
					<div class="grid_list">
						<div class="grid_col">
							<div class="grid_icon">
								<?php echo \App\Helpers\IconHelper::render('file-image'); ?>
							</div>
							<div class="grid_content">
								<span id="grid_<?php echo $fetch->id; ?>" class="gridfilename"><?php echo $fetch->file_name; ?></span>
								<div class="dropdown d-inline dropdown_ellipsis_icon">
									<a class="dropdown-toggle" type="button" id="" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><?php echo \App\Helpers\IconHelper::render('ellipsis-v'); ?></a>
									<div class="dropdown-menu">
										<?php
                                        //$url = 'https://'.env('AWS_BUCKET').'.s3.'. env('AWS_DEFAULT_REGION') . '.amazonaws.com/';
                                        ?>
										<!--<a class="dropdown-item" href="<?php //echo $url.$client_unique_id.'/'.$doctype.'/'.$fetch->myfile; ?>">Preview</a>
										<a download class="dropdown-item" href="<?php //echo $url.$client_unique_id.'/'.$doctype.'/'.$fetch->myfile; ?>">Download</a>-->

                                        <!--<a class="dropdown-item" href="<?php //echo $fetch->myfile; ?>">Preview</a>
										<a download class="dropdown-item" href="<?php //echo $fetch->myfile; ?>">Download</a>-->
                                      
                                        <a class="dropdown-item" href="javascript:void(0);" onclick="previewFile('<?php echo $fetch->filetype;?>','<?php echo \App\Helpers\Helper::documentFileUrlAttr($fetch->myfile); ?>','preview-container-alldocumentlist')">Preview</a>
                                        <?php
                                            $suggestedDlNameUpload = self::buildClientDocumentDownloadFilename(
                                                (string) ($fetch->file_name ?? 'document'),
                                                (string) ($fetch->filetype ?? '')
                                            );
                                        ?>
                                        <a href="<?php echo url('/download-document') . '?filelink=' . urlencode($fetch->myfile) . '&filename=' . urlencode($suggestedDlNameUpload); ?>" class="dropdown-item download-file" data-filelink="<?php echo htmlspecialchars($fetch->myfile, ENT_QUOTES, 'UTF-8'); ?>" data-filename="<?php echo htmlspecialchars($suggestedDlNameUpload, ENT_QUOTES, 'UTF-8'); ?>" data-dl-base="<?php echo htmlspecialchars((string) ($fetch->file_name ?? ''), ENT_QUOTES, 'UTF-8'); ?>" data-dl-ext="<?php echo htmlspecialchars((string) ($fetch->filetype ?? ''), ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener">Download</a>



                                        <?php if( Auth::user()->role == 1 ){ //echo Auth::user()->role;//super admin ?>
                                        <a data-id="<?php echo $fetch->id; ?>" class="dropdown-item deletenote" data-href="deletealldocs" href="javascript:;" >Delete</a>
                                        <?php } ?>
                                        <a data-id="<?php echo $fetch->id; ?>" class="dropdown-item verifydoc" data-doctype="documents" data-href="verifydoc" href="javascript:;">Verify</a>
                                        <a data-id="<?php echo $fetch->id; ?>" class="dropdown-item notuseddoc" data-doctype="documents" data-href="notuseddoc" href="javascript:;">Not Used</a>
                                    </div>
								</div>
							</div>
						</div>
					</div>
					<?php
				}
				$griddata = ob_get_clean();
				$response['data']	= $data;
				$response['griddata'] = $griddata;
			}else{
				$response['status'] = false;
				$response['message'] = 'Please try again';
			}
		} else {
			 $response['status'] 	= 	false;
			$response['message']	=	'Please try again';
		}
		echo json_encode($response);
	}
    

    //verify document
    public function verifydoc(Request $request){ //dd($request->all());
		$doc_id = $request->doc_id;
        $doc_type = $request->doc_type;
        if(\App\Models\Document::where('id',$doc_id)->exists()){
            $upd = DB::table('documents')->where('id', $doc_id)->update(array(
                'checklist_verified_by' => Auth::user()->id,
                'checklist_verified_at' => date('Y-m-d H:i:s')
            ));
            if($upd){
                $docInfo = \App\Models\Document::select('client_id')->where('id',$doc_id)->first();
                $subject = 'verified '.$doc_type.' document';
                $objs = new ActivitiesLog;
				$objs->client_id = $docInfo->client_id;
				$objs->created_by = Auth::user()->id;
				$objs->description = '';
				$objs->subject = $subject;
				$objs->task_status = 0; // Required NOT NULL field (0 = activity, 1 = task)
				$objs->pin = 0; // Required NOT NULL field (0 = not pinned, 1 = pinned)
				$objs->save();
                //Get verified at and verified by
                $admin_info = ClientDocumentStaffResolver::firstNameRowByStaffId(Auth::user()->id);
                if($admin_info){
                    $response['verified_by'] = 	$admin_info->first_name;
                    $response['verified_at'] = 	date('d/m/Y');
                } else {
                    $response['verified_by'] = "";
                    $response['verified_at'] = "";
                }
                $response['doc_type'] = $doc_type;
				$response['status'] = 	true;
				$response['data']	=	$doc_type.' Document verified successfully';
			} else {
				$response['status'] 	= 	false;
				$response['message']	=	'Please try again';
                $response['verified_by'] = "";
                $response['verified_at'] = "";
                $response['doc_type'] = "";
			}
		} else {
			$response['status'] 	= 	false;
			$response['message']	=	'Please try again';
            $response['verified_by'] = "";
            $response['verified_at'] = "";
            $response['doc_type'] = "";
		}
		echo json_encode($response);
	} 

    /**
     * Public browser URL for a document row (S3 full URL or legacy local filename).
     */
    private function documentAccessUrlForDisplay(object $doc): string
    {
        $myfile = trim((string) ($doc->myfile ?? ''));
        if ($myfile === '') {
            return '';
        }
        if (! empty($doc->myfile_key) || preg_match('#^https?://#i', $myfile)) {
            return \App\Helpers\Helper::documentFileUrl($myfile);
        }

        return asset('img/documents/' . ltrim($myfile, '/'));
    }

    private function documentAccessUrlAttr(object $doc): string
    {
        return htmlspecialchars($this->documentAccessUrlForDisplay($doc), ENT_QUOTES, 'UTF-8');
    }

    private function documentIsLegacyLocalFile(object $doc): bool
    {
        $myfile = trim((string) ($doc->myfile ?? ''));
        if ($myfile === '') {
            return false;
        }
        if (! empty($doc->myfile_key)) {
            return false;
        }
        if (preg_match('#^https?://#i', $myfile)) {
            return false;
        }

        return true;
    }

    /**
     * Upload document for client (education/migration and similar).
     * New uploads go to S3 like uploadalldocument; legacy rows on disk remain readable.
     */
    public function uploaddocument(Request $request){
		$id = $request->clientid;
        $doctype = isset($request->doctype)? $request->doctype : '';
        $response = ['status' => false, 'message' => 'Please try again'];

		if ($request->hasfile('document_upload')) {
            if (! $this->resolveAccessibleDocumentClientForJson($id, true)) {
                $response['message'] = 'Unauthorized';
                echo json_encode($response);

                return;
            }

			if(!is_array($request->file('document_upload'))){
				$files[] = $request->file('document_upload');
			}else{
				$files = $request->file('document_upload');
			}

            $adminInfo = Admin::select('client_id')->where('id', $id)->first();
            $clientUniqueId = $adminInfo && ! empty($adminInfo->client_id)
                ? (string) $adminInfo->client_id
                : '';
            if ($clientUniqueId === '') {
                $this->s3UploadLog('warning', '[S3DocumentUpload] uploaddocument_empty_client_unique_id', [
                    'operation' => 'uploaddocument',
                    'client_id' => $id,
                    'doctype' => $doctype,
                    'user_id' => Auth::id(),
                ]);
                $response['message'] = 'Client storage ID is missing. Cannot upload document.';
                echo json_encode($response);

                return;
            }

            $saved = false;
            $uploadFailMessage = null;

			foreach ($files as $file) {

				$size = $file->getSize();
				$fileName = $file->getClientOriginalName();
				$nameWithoutExtension = pathinfo($fileName, PATHINFO_FILENAME);
				$fileExtension = $file->getClientOriginalExtension();
				$storageName = time() . $file->getClientOriginalName();
                $s3Key = $clientUniqueId . '/' . ($doctype !== '' ? $doctype : 'documents') . '/' . $storageName;

                $s3Result = $this->putFileToS3WithLogging($file->getPathname(), $s3Key, [
                    'operation' => 'uploaddocument',
                    'client_id' => $id,
                    'client_unique_id' => $clientUniqueId,
                    'doctype' => $doctype,
                    'original_filename' => $fileName,
                    'user_id' => Auth::id(),
                ]);

                if (! $s3Result['success']) {
                    $this->s3UploadLog('error', '[S3DocumentUpload] uploaddocument_s3_failed_aborted', [
                        'operation' => 'uploaddocument',
                        'client_id' => $id,
                        's3_key' => $s3Key,
                        'error' => $s3Result['error'] ?? 'unknown',
                    ]);
                    $uploadFailMessage = 'File upload to storage failed. Please try again.';
                    continue;
                }

                $disk = $this->s3Disk();
                $fileUrl = $s3Result['file_url'] ?? $disk->url($s3Key);

				$obj = new Document;
				$obj->file_name = $nameWithoutExtension;
				$obj->filetype = $fileExtension;
				$obj->user_id = Auth::user()->id;
				$obj->myfile = $fileUrl;
                $obj->myfile_key = $storageName;
				$obj->client_id = $id;
				$obj->type = $request->type;
				$obj->file_size = $size;
				$obj->doc_type = $doctype;
				
				// Assign to default "General" category if doc_type is 'documents' and no category_id is set
				if ($doctype == 'documents' && !$request->has('category_id')) {
					$generalCategory = \App\Models\DocumentCategory::where('name', 'General')
						->where('is_default', true)
						->first();
					if ($generalCategory) {
						$obj->category_id = $generalCategory->id;
					}
				} elseif ($request->has('category_id')) {
					// Use provided category_id if available
					$obj->category_id = $request->category_id;
				}
				
				$saved = $obj->save() || $saved;

                $this->s3UploadLog($obj->exists ? 'info' : 'error', '[S3DocumentUpload] uploaddocument_db_save', [
                    'operation' => 'uploaddocument',
                    'client_id' => $id,
                    'document_id' => $obj->id ?? null,
                    's3_key' => $s3Key,
                    'file_url' => $fileUrl,
                ]);

			}

			if($saved){
				if($request->type == 'client'){
				$subject = 'added 1 document';
				$objs = new ActivitiesLog;
				$objs->client_id = $id;
				$objs->created_by = Auth::user()->id;
				$objs->description = '';
				$objs->subject = $subject;
				$objs->task_status = 0;
				$objs->pin = 0;
				$objs->save();

				}
				$response['status'] 	= 	true;
				$response['message']	=	'You\'ve successfully uploaded your document';
				$fetchd = Document::where('client_id',$id)->where('doc_type',$doctype)->where('type',$request->type)->orderby('created_at', 'DESC')->get();
				ob_start();
				foreach($fetchd as $fetch){
					$admin = ClientDocumentStaffResolver::staffRowById($fetch->user_id);
                    $previewUrl = $this->documentAccessUrlAttr($fetch);
                    $previewHref = $this->documentAccessUrlForDisplay($fetch);
                    $isLegacyLocal = $this->documentIsLegacyLocalFile($fetch);
                    $downloadHref = $isLegacyLocal
                        ? $previewHref
                        : (url('/download-document') . '?filelink=' . urlencode($previewHref) . '&filename=' . urlencode(
                            self::buildClientDocumentDownloadFilename(
                                (string) ($fetch->file_name ?? 'document'),
                                (string) ($fetch->filetype ?? '')
                            )
                        ));
                  
                    $preview_container_type = 'preview-container-documentlist';
                    if( isset($doctype) && $doctype == 'migration'){
                        $preview_container_type = 'preview-container-migrationdocumentlist';
                    } else if( isset($doctype) && $doctype == 'education'){
                        $preview_container_type = 'preview-container-documentlist';
                    }
                    $fileExt = strtolower((string) ($fetch->filetype ?? ''));
                    $isImageExt = in_array($fileExt, ['jpg', 'jpeg', 'png'], true);
					?>
					<tr class="drow" id="id_<?php echo $fetch->id; ?>">
						<td style="white-space: initial;">
                            <div data-id="<?php echo $fetch->id; ?>" data-name="<?php echo htmlspecialchars((string) $fetch->file_name, ENT_QUOTES, 'UTF-8'); ?>" class="doc-row">
								<a style="white-space: initial;" href="javascript:void(0);" onclick="previewFile('<?php echo htmlspecialchars($fileExt, ENT_QUOTES, 'UTF-8');?>','<?php echo $previewUrl; ?>','<?php echo $preview_container_type;?>')">
                                    <?php echo \App\Helpers\IconHelper::render('file-image'); ?> <span><?php echo htmlspecialchars($fetch->file_name . '.' . $fetch->filetype, ENT_QUOTES, 'UTF-8'); ?></span>
                                </a>
							</div>
                        </td>
						<td style="white-space: initial;"><?php echo htmlspecialchars((string) ($admin->first_name ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>

						<td style="white-space: initial;"><?php echo date('d/m/Y', strtotime($fetch->created_at)); ?></td>
						<td>
							<div class="dropdown d-inline">
								<button class="btn btn-primary dropdown-toggle" type="button" id="" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">Action</button>
								<div class="dropdown-menu">
									<a class="dropdown-item renamedoc" href="javascript:;">Rename</a>
									<?php if ($isLegacyLocal) { ?>
									<a target="_blank" class="dropdown-item" href="<?php echo htmlspecialchars($previewHref, ENT_QUOTES, 'UTF-8'); ?>">Preview</a>
									<?php } else { ?>
									<a class="dropdown-item" href="javascript:void(0);" onclick="previewFile('<?php echo htmlspecialchars($fileExt, ENT_QUOTES, 'UTF-8');?>','<?php echo $previewUrl; ?>','<?php echo $preview_container_type;?>')">Preview</a>
									<?php } ?>
									<?php
                                        // PDF conversion helper only works for legacy local images
										if ($isLegacyLocal && $isImageExt) {
														?>
															<a target="_blank" class="dropdown-item" href="<?php echo URL::to('/document/download/pdf'); ?>/<?php echo $fetch->id; ?>">PDF</a>
															<?php } ?>
									<?php if ($isLegacyLocal) { ?>
									<a download class="dropdown-item" href="<?php echo htmlspecialchars($previewHref, ENT_QUOTES, 'UTF-8'); ?>">Download</a>
									<?php } else { ?>
									<a class="dropdown-item download-file" href="<?php echo htmlspecialchars($downloadHref, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener">Download</a>
									<?php } ?>

									<a data-id="<?php echo $fetch->id; ?>" class="dropdown-item deletenote" data-href="deletedocs" href="javascript:;" >Delete</a>
								</div>
							</div>
						</td>
					</tr>
					<?php
				}
				$data = ob_get_clean();
				ob_start();
				foreach($fetchd as $fetch){
					$admin = ClientDocumentStaffResolver::staffRowById($fetch->user_id);
                    $previewUrl = $this->documentAccessUrlAttr($fetch);
                    $previewHref = $this->documentAccessUrlForDisplay($fetch);
                    $isLegacyLocal = $this->documentIsLegacyLocalFile($fetch);
                    $downloadHref = $isLegacyLocal
                        ? $previewHref
                        : (url('/download-document') . '?filelink=' . urlencode($previewHref) . '&filename=' . urlencode(
                            self::buildClientDocumentDownloadFilename(
                                (string) ($fetch->file_name ?? 'document'),
                                (string) ($fetch->filetype ?? '')
                            )
                        ));
                    $fileExt = strtolower((string) ($fetch->filetype ?? ''));
					?>
					<div class="grid_list">
						<div class="grid_col">
							<div class="grid_icon">
								<?php echo \App\Helpers\IconHelper::render('file-image'); ?>
							</div>
							<div class="grid_content">
								<span id="grid_<?php echo $fetch->id; ?>" class="gridfilename"><?php echo htmlspecialchars((string) $fetch->file_name, ENT_QUOTES, 'UTF-8'); ?></span>
								<div class="dropdown d-inline dropdown_ellipsis_icon">
									<a class="dropdown-toggle" type="button" id="" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><?php echo \App\Helpers\IconHelper::render('ellipsis-v'); ?></a>
									<div class="dropdown-menu">
										<?php if ($isLegacyLocal) { ?>
										<a class="dropdown-item" href="<?php echo htmlspecialchars($previewHref, ENT_QUOTES, 'UTF-8'); ?>">Preview</a>
										<a download class="dropdown-item" href="<?php echo htmlspecialchars($previewHref, ENT_QUOTES, 'UTF-8'); ?>">Download</a>
										<?php } else { ?>
										<a class="dropdown-item" href="javascript:void(0);" onclick="previewFile('<?php echo htmlspecialchars($fileExt, ENT_QUOTES, 'UTF-8');?>','<?php echo $previewUrl; ?>','preview-container-documentlist')">Preview</a>
										<a class="dropdown-item download-file" href="<?php echo htmlspecialchars($downloadHref, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener">Download</a>
										<?php } ?>
										<a data-id="<?php echo $fetch->id; ?>" class="dropdown-item deletenote" data-href="deletedocs" href="javascript:;" >Delete</a>
									</div>
								</div>
							</div>
						</div>
					</div>
					<?php
				}
				$griddata = ob_get_clean();
				$response['data']	=$data;
				$response['griddata']	=$griddata;
			}else{
				$response['status'] 	= 	false;
				$response['message']	=	$uploadFailMessage ?: 'Please try again';
			}
		 }else{
			 $response['status'] 	= 	false;
			$response['message']	=	'Please try again';
		 }
		 echo json_encode($response);
	}

    /**
     * Rename document
     */
    public function renamedoc(Request $request){
		$id = $request->id;
		$filename = $request->filename;
		if(Document::where('id',$id)->exists()){
			$doc = Document::where('id',$id)->first();
            if (! $this->resolveAccessibleDocumentClientForJson($doc->client_id ?? null, true)) {
                $response['status'] = false;
                $response['message'] = 'Unauthorized';
                echo json_encode($response);

                return;
            }
			$res = DB::table('documents')->where('id', @$id)->update(['file_name' => $filename]);
			if($res){
				$response['status'] 	= 	true;
				$response['data']	=	'Document saved successfully';
				$response['Id']	=	$id;
				$response['filename']	=	$filename;
				$response['filetype']	=	$doc->filetype;
			}else{
				$response['status'] 	= 	false;
				$response['message']	=	'Please try again';
			}
		}else{
			$response['status'] 	= 	false;
			$response['message']	=	'Please try again';
		}
		echo json_encode($response);
	}

    /**
     * Delete document (S3 or legacy local file cleanup is best-effort).
     */
    public function deletedocs(Request $request){
		$note_id = $request->note_id;

		if(Document::where('id',$note_id)->exists()){

			$data = DB::table('documents')->where('id', @$note_id)->first();
            if (! $data || ! $this->resolveAccessibleDocumentClientForJson($data->client_id ?? null, true)) {
                $response['status'] = false;
                $response['message'] = 'Unauthorized';
                echo json_encode($response);

                return;
            }

            // Best-effort storage cleanup before DB delete
            if ($data) {
                $hasS3 = (! empty($data->myfile_key))
                    || (! empty($data->myfile) && is_string($data->myfile)
                        && (str_starts_with((string) $data->myfile, 'http://')
                            || str_starts_with((string) $data->myfile, 'https://')));
                if ($hasS3) {
                    $this->tryDeleteDocumentS3Object($data);
                } elseif (! empty($data->myfile) && $this->documentIsLegacyLocalFile($data)) {
                    $localPath = public_path('img/documents/' . ltrim((string) $data->myfile, '/'));
                    if (is_file($localPath)) {
                        @unlink($localPath);
                    }
                }
            }

			$res = DB::table('documents')->where('id', @$note_id)->delete();

			if($res){

				$subject = 'deleted a document';

				$objs = new ActivitiesLog;
				$objs->client_id = $data->client_id;
				$objs->created_by = Auth::user()->id;
				$objs->description = '';
				$objs->subject = $subject;
				$objs->task_status = 0;
				$objs->pin = 0;
				$objs->save();
				$response['status'] 	= 	true;
				$response['data']	=	'Document removed successfully';
			}else{
				$response['status'] 	= 	false;
				$response['message']	=	'Please try again';
			}
		}else{
				$response['status'] 	= 	false;
				$response['message']	=	'Please try again';
		}
		echo json_encode($response);
	}

    public function downloadpdf(Request $request, $id = NULL){
	    	$fetchd = Document::where('id',$id)->first();
            if (! $fetchd) {
                abort(404, 'Document not found');
            }
            $this->assertCanAccessDocumentClient($fetchd->client_id ?? null, false);
            // Conversion view expects a local filename under img/documents
            if (! $this->documentIsLegacyLocalFile($fetchd)) {
                abort(400, 'PDF conversion is only available for local image documents.');
            }
	    	$data = ['title' => 'Welcome to codeplaners.com','image' => $fetchd->myfile];
        $pdf = PDF::loadView('myPDF', $data);

        return $pdf->stream('codeplaners.pdf');
	}

    // TODO: Move remaining document methods here:
    // - uploadalldocument
    // - addalldocchecklist
    // - deletealldocs
    // - renamealldoc
    // - renamechecklistdoc
    // - notuseddoc
    // - backtodoc
}
