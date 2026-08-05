<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\Signer;
use App\Models\DocumentNote;
use App\Models\ActivitiesLog;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Http;

/**
 * Public Document Controller
 * 
 * Handles public-facing document signing operations without authentication.
 * Access is controlled through unique tokens sent via email.
 */
class PublicDocumentController extends Controller
{
    /**
     * Python service base URL
     */
    protected $pythonServiceUrl;

    /**
     * No authentication required - using token-based validation
     */
    public function __construct()
    {
        // Public controller - no authentication middleware
        $this->pythonServiceUrl = env('PYTHON_SERVICE_URL', 'http://127.0.0.1:5001');
    }

    /**
     * Show the public signing form for a document using a tokenized link.
     */
    public function sign($id, $token)
    {
        $documentId = (int) $id;
        if ($documentId <= 0) {
            Log::warning('Invalid document ID in public sign method', ['id' => $id]);
            abort(404, 'Invalid document link.');
        }

        // Validate token format
        if (!$token || !is_string($token) || strlen($token) < 32 || !preg_match('/^[a-zA-Z0-9]+$/', $token)) {
            Log::warning('Invalid token format in public sign method', ['token_length' => strlen($token ?? '')]);
            abort(403, 'Invalid or expired signing link.');
        }

        try {
            $document = Document::findOrFail($documentId);
            $signer = $document->signers()->where('token', $token)->first();

            if (!$signer || $signer->status === 'signed' || $signer->status === 'cancelled') {
                Log::warning('Invalid signer, already signed, or cancelled', [
                    'document_id' => $documentId,
                    'signer_exists' => !is_null($signer),
                    'signer_status' => $signer ? $signer->status : 'none'
                ]);
                
                if ($signer && $signer->status === 'signed') {
                    return redirect()->route('public.documents.thankyou', ['id' => $document->id])
                        ->with('info', 'This document has already been signed.');
                }
                
                if ($signer && $signer->status === 'cancelled') {
                    return $this->showErrorPage('Signature Cancelled', 'This signing link has been cancelled. Please contact the document sender for assistance.', $document);
                }
                
                return $this->showErrorPage('Invalid Signing Link', 'Invalid or expired signing link. Please contact the document sender for assistance.', $document ?? null);
            }

            // Track when document was opened
            if (!$signer->opened_at) {
                $signer->update(['opened_at' => now()]);
            }

            $signatureFields = $document->signatureFields()->get();
            
            // Get PDF path - handle both S3 and local files
            $url = $document->myfile;
            $pdfPath = null;
            $tempFile = null;
            
            // Check if it's an S3 URL
            if ($url && (str_contains($url, 's3.') || str_contains($url, 'amazonaws.com'))) {
                // Download from S3 to a temporary file for page counting
                $pdfPath = $this->downloadS3FileToTemp($url, $documentId);
                $tempFile = $pdfPath; // Track for cleanup
            } elseif ($url && file_exists(storage_path('app/public/' . $url))) {
                // Local file
                $pdfPath = storage_path('app/public/' . $url);
            }

            // Count PDF pages using Python service
            $pdfPages = 1;
            if ($pdfPath && file_exists($pdfPath)) {
                $pdfPages = $this->countPdfPages($pdfPath) ?: 1;
            }
            
            // Clean up temp file after counting pages
            if ($tempFile && file_exists($tempFile)) {
                unlink($tempFile);
            }

            return view('documents.sign', compact('document', 'signer', 'signatureFields', 'pdfPages'));
        } catch (\Exception $e) {
            Log::error('Error in public sign method', [
                'error' => $e->getMessage(),
                'document_id' => $documentId,
                'token_present' => !empty($token)
            ]);
            return redirect('/')->with('error', 'An error occurred while loading the signing page.');
        }
    }

    /**
     * Submit signatures for a public document
     * Supports both legacy format (with signer_id, signature_positions) and new format (token + signatures object)
     */
    public function submitSignatures(Request $request, $id)
    {
        $isAjax = $request->expectsJson() || $request->ajax() || $request->isJson();
        
        Log::info('=== START submitSignatures ===', [
            'document_id' => $id,
            'is_ajax' => $isAjax,
            'has_token' => $request->has('token'),
            'has_signer_id' => $request->has('signer_id'),
            'has_signatures' => $request->has('signatures'),
        ]);

        $documentId = (int) $id;
        if ($documentId <= 0) {
            Log::error('Invalid document ID in submitSignatures', ['id' => $id]);
            if ($isAjax) {
                return response()->json(['success' => false, 'message' => 'Invalid document ID.'], 400);
            }
            return redirect('/')->with('error', 'Invalid document ID.');
        }

        try {
            $document = Document::findOrFail($documentId);
            
            // Determine if this is new format (token only) or legacy format (signer_id)
            $isNewFormat = $request->has('token') && !$request->has('signer_id');
            
            if ($isNewFormat) {
                // New format: find signer by token
                $token = $request->input('token');
                if (!$token || strlen($token) < 32) {
                    if ($isAjax) {
                        return response()->json(['success' => false, 'message' => 'Invalid token.'], 400);
                    }
                    return redirect('/')->with('error', 'Invalid token.');
                }
                
                $signer = $document->signers()->where('token', $token)->first();
                if (!$signer) {
                    if ($isAjax) {
                        return response()->json(['success' => false, 'message' => 'Invalid or expired signing link.'], 400);
                    }
                    return redirect('/')->with('error', 'Invalid or expired signing link.');
                }
            } else {
                // Legacy format: validate with signer_id
                $request->validate([
                    'signer_id' => 'required|integer|exists:signers,id',
                    'token' => 'required|string|min:32',
                    'signatures' => 'required|array',
                    'signatures.*' => 'nullable|string',
                    'signature_positions' => 'required|array',
                    'signature_positions.*' => 'nullable|string'
                ]);
                
                $signer = Signer::findOrFail($request->signer_id);
                
                // Verify signer belongs to this document
                if ($signer->document_id !== $document->id) {
                    if ($isAjax) {
                        return response()->json(['success' => false, 'message' => 'Invalid signing attempt.'], 400);
                    }
                    return redirect('/')->with('error', 'Invalid signing attempt.');
                }

                // Verify token matches
                if ($signer->token !== $request->token) {
                    if ($isAjax) {
                        return response()->json(['success' => false, 'message' => 'Invalid or expired signing link.'], 400);
                    }
                    return redirect('/')->with('error', 'Invalid or expired signing link.');
                }
            }

            // Check if signer has already signed
            if ($signer->status === 'signed') {
                if ($isAjax) {
                    return response()->json([
                        'success' => true, 
                        'message' => 'This document has already been signed.',
                        'redirect' => route('public.documents.thankyou', ['id' => $document->id])
                    ]);
                }
                return redirect()->route('public.documents.thankyou', ['id' => $document->id])
                    ->with('info', 'This document has already been signed.');
            }
            
            // Check if signature has been cancelled
            if ($signer->status === 'cancelled') {
                if ($isAjax) {
                    return response()->json(['success' => false, 'message' => 'This signing link has been cancelled.'], 400);
                }
                return redirect('/')->with('error', 'This signing link has been cancelled.');
            }
            
            if ($signer->token !== null && $signer->status === 'pending') {
                // Get PDF file - check both S3 URL and local storage
                $url = $document->myfile;
                $pdfPath = null;
                $tempFile = null;
                
                // Check if it's an S3 URL
                if ($url && (str_contains($url, 's3.') || str_contains($url, 'amazonaws.com') || str_contains($url, 'http'))) {
                    // Download from S3/remote to a temporary file
                    $pdfPath = $this->downloadS3FileToTemp($url, $document->id);
                    $tempFile = $pdfPath;
                } elseif ($url && file_exists(storage_path('app/public/' . $url))) {
                    $pdfPath = storage_path('app/public/' . $url);
                }
                
                if (!$pdfPath || !file_exists($pdfPath)) {
                    Log::error('PDF file not found for document submission', [
                        'document_id' => $document->id,
                        'url' => $url,
                    ]);
                    if ($isAjax) {
                        return response()->json(['success' => false, 'message' => 'Document file not found. Please contact support.'], 400);
                    }
                    return redirect()->back()->with('error', 'Document file not found. Please contact support.');
                }
                
                $outputPath = storage_path('app/public/signed/' . $document->id . '_signed.pdf');
                
                // Ensure signed directory exists
                if (!file_exists(storage_path('app/public/signed'))) {
                    mkdir(storage_path('app/public/signed'), 0755, true);
                }

                // Process signatures - handle both new and legacy format
                $signaturePositions = [];
                $signatureLinks = [];
                $signaturesSaved = false;
                
                // Get signature fields from database for position reference
                $signatureFieldsFromDb = $document->signatureFields()->get()->keyBy('id');

                if ($isNewFormat) {
                    // New format: signatures = { fieldId: base64Data, ... }
                    foreach ($request->signatures as $fieldId => $signatureData) {
                        $sanitizedFieldId = (int) $fieldId;
                        if ($sanitizedFieldId <= 0 || !$signatureData) continue;
                        
                        // Get field position from database
                        $field = $signatureFieldsFromDb->get($sanitizedFieldId);
                        if (!$field) {
                            Log::warning('Signature field not found in database', ['field_id' => $sanitizedFieldId]);
                            continue;
                        }
                        
                        // Validate and decode signature
                        $sanitizedSignature = $this->sanitizeSignatureData($signatureData, $sanitizedFieldId);
                        if ($sanitizedSignature === false) continue;

                        $imageData = $sanitizedSignature['imageData'];

                        // Store signature locally
                        $filename = sprintf('%d_field_%d_%s.png', $signer->id, $sanitizedFieldId, bin2hex(random_bytes(8)));
                        $localSignaturePath = 'signatures/' . $filename;
                        
                        // Ensure signatures directory exists
                        if (!file_exists(storage_path('app/public/signatures'))) {
                            mkdir(storage_path('app/public/signatures'), 0755, true);
                        }
                        
                        Storage::disk('public')->put($localSignaturePath, $imageData);
                        $signaturePath = storage_path('app/public/' . $localSignaturePath);
                        $signatureUrl = asset('storage/' . $localSignaturePath);

                        $signaturePositions[$sanitizedFieldId] = [
                            'path' => $signaturePath,
                            'page' => $field->page_number,
                            'x_percent' => $field->x_percent,
                            'y_percent' => $field->y_percent,
                            'w_percent' => $field->width_percent ?? 20,
                            'h_percent' => $field->height_percent ?? 10
                        ];
                        $signatureLinks[$sanitizedFieldId] = $signatureUrl;
                        $signaturesSaved = true;
                    }
                } else {
                    // Legacy format: signatures grouped by page
                    foreach ($request->signatures as $page => $signaturesJson) {
                        $pageNum = (int) $page;
                        if ($pageNum < 1 || $pageNum > 999 || !$signaturesJson) {
                            continue;
                        }

                        $signatures = json_decode($signaturesJson, true);
                        $positions = json_decode($request->signature_positions[$page] ?? '{}', true);

                        if (!is_array($signatures) || !is_array($positions)) {
                            continue;
                        }

                        foreach ($signatures as $fieldId => $signatureData) {
                            $sanitizedFieldId = (int) $fieldId;
                            if ($sanitizedFieldId <= 0) continue;

                            // Validate and decode signature
                            $sanitizedSignature = $this->sanitizeSignatureData($signatureData, $sanitizedFieldId);
                            if ($sanitizedSignature === false) continue;

                            $imageData = $sanitizedSignature['imageData'];

                            // Store signature locally
                            $filename = sprintf('%d_field_%d_%s.png', $signer->id, $sanitizedFieldId, bin2hex(random_bytes(8)));
                            $localSignaturePath = 'signatures/' . $filename;
                            
                            // Ensure signatures directory exists
                            if (!file_exists(storage_path('app/public/signatures'))) {
                                mkdir(storage_path('app/public/signatures'), 0755, true);
                            }
                            
                            Storage::disk('public')->put($localSignaturePath, $imageData);
                            $signaturePath = storage_path('app/public/' . $localSignaturePath);
                            $signatureUrl = asset('storage/' . $localSignaturePath);

                            // Store position
                            $position = $positions[$fieldId] ?? [];
                            $sanitizedPosition = $this->sanitizePositionData($position);

                            $signaturePositions[$sanitizedFieldId] = [
                                'path' => $signaturePath,
                                'page' => $pageNum,
                                'x_percent' => $sanitizedPosition['x_percent'],
                                'y_percent' => $sanitizedPosition['y_percent'],
                                'w_percent' => $sanitizedPosition['w_percent'],
                                'h_percent' => $sanitizedPosition['h_percent']
                            ];
                            $signatureLinks[$sanitizedFieldId] = $signatureUrl;
                            $signaturesSaved = true;
                        }
                    }
                }

                if (!$signaturesSaved) {
                    if ($isAjax) {
                        return response()->json(['success' => false, 'message' => 'No valid signatures were detected. Please ensure all signature fields are properly signed.'], 400);
                    }
                    return redirect()->back()
                        ->with('error', 'No valid signatures were detected. Please ensure all signature fields are properly signed.')
                        ->withInput();
                }

                // Use Python service to add signatures to PDF
                $signaturesForPython = [];
                foreach ($signaturePositions as $fieldId => $sigData) {
                    // Read signature image and convert to base64
                    $signatureImageContent = file_get_contents($sigData['path']);
                    $signatureBase64 = base64_encode($signatureImageContent);
                    
                    // Convert percentages from 0-100 scale to 0-1 decimal scale for Python service
                    $signaturesForPython[] = [
                        'field_id' => $fieldId,
                        'page_number' => $sigData['page'],
                        'x_percent' => floatval($sigData['x_percent']) / 100,
                        'y_percent' => floatval($sigData['y_percent']) / 100,
                        'width_percent' => floatval($sigData['w_percent']) / 100,
                        'height_percent' => floatval($sigData['h_percent']) / 100,
                        'signature_data' => $signatureBase64
                    ];
                }
                
                Log::info('Signatures prepared for Python service', [
                    'document_id' => $document->id,
                    'signature_count' => count($signaturesForPython),
                    'positions' => array_map(function($s) {
                        return [
                            'field_id' => $s['field_id'],
                            'page' => $s['page_number'],
                            'x' => $s['x_percent'],
                            'y' => $s['y_percent'],
                            'w' => $s['width_percent'],
                            'h' => $s['height_percent']
                        ];
                    }, $signaturesForPython)
                ]);

                // Apply signatures via Python only — never mark "signed" from an unsigned fallback copy
                $pythonSignError = null;
                try {
                    // Remove any stale/partial output from a previous failed attempt
                    if (file_exists($outputPath)) {
                        @unlink($outputPath);
                    }

                    $response = Http::timeout(60)->post($this->pythonServiceUrl . '/add_signatures', [
                        'input_path' => $pdfPath,
                        'output_path' => $outputPath,
                        'signatures' => $signaturesForPython
                    ]);

                    if (! $response->successful()) {
                        $pythonSignError = 'Signature service returned HTTP ' . $response->status();
                        Log::error('Python service failed to add signatures', [
                            'document_id' => $document->id,
                            'status' => $response->status(),
                            'body' => $response->body(),
                        ]);
                    } else {
                        $result = $response->json();
                        if (! is_array($result) || ! ($result['success'] ?? false)) {
                            $pythonSignError = is_array($result)
                                ? (string) ($result['error'] ?? 'Signature service returned unsuccessful result')
                                : 'Signature service returned invalid response';
                            Log::warning('Python service returned unsuccessful result for add_signatures', [
                                'document_id' => $document->id,
                                'error' => $pythonSignError,
                                'body' => $response->body(),
                            ]);
                        }
                    }
                } catch (\Exception $e) {
                    $pythonSignError = 'Could not reach signature service: ' . $e->getMessage();
                    Log::error('Python service connection error for add_signatures', [
                        'document_id' => $document->id,
                        'error' => $e->getMessage(),
                    ]);
                }

                // Require a real stamped PDF before any status / hash / signed-doc updates
                $outputValid = is_string($outputPath)
                    && is_file($outputPath)
                    && filesize($outputPath) > 0;

                if ($pythonSignError === null && $outputValid) {
                    $originalHash = @hash_file('sha256', $pdfPath);
                    $outputHash = @hash_file('sha256', $outputPath);
                    // Same bytes as source almost always means signatures were not applied
                    if ($originalHash && $outputHash && hash_equals($originalHash, $outputHash)) {
                        $pythonSignError = 'Signature service did not modify the PDF';
                        Log::error('Python service output matches unsigned source PDF', [
                            'document_id' => $document->id,
                            'hash' => $outputHash,
                        ]);
                        $outputValid = false;
                    }
                }

                if ($pythonSignError !== null || ! $outputValid) {
                    if (is_string($outputPath) && is_file($outputPath)) {
                        @unlink($outputPath);
                    }
                    if ($tempFile && file_exists($tempFile)) {
                        @unlink($tempFile);
                    }

                    $userMessage = 'Could not apply signatures to the document. Please try again. If the problem continues, contact support.';
                    Log::error('Public signing aborted — document left pending', [
                        'document_id' => $document->id,
                        'signer_id' => $signer->id,
                        'python_error' => $pythonSignError,
                        'output_valid' => $outputValid,
                    ]);

                    if ($isAjax) {
                        return response()->json([
                            'success' => false,
                            'message' => $userMessage,
                        ], 503);
                    }

                    return redirect()->back()
                        ->with('error', $userMessage)
                        ->withInput();
                }

                // Generate SHA-256 hash for tamper detection (signed output only)
                $signedHash = hash_file('sha256', $outputPath);

                // Prefer S3 for signed PDF when configured and document has a client (same path pattern as other client docs)
                $signedPdfUrl = $this->uploadSignedPdfToS3OrLocal($document, $outputPath);

                // Update statuses and save hash
                $signer->update(['status' => 'signed', 'signed_at' => now()]);
                $document->status = 'signed';
                $document->signature_doc_link = json_encode($signatureLinks);
                $document->signed_doc_link = $signedPdfUrl;
                $document->signed_hash = $signedHash;
                $document->hash_generated_at = now();
                $document->save();

                // Create _signed checklist row below original (for client documents in Documents tab)
                if ($document->client_id && $document->type === 'client' && !$document->source_document_id) {
                    $exists = Document::where('source_document_id', $document->id)->exists();
                    if (!$exists) {
                        Document::create([
                            'client_id' => $document->client_id,
                            'category_id' => $document->category_id,
                            'type' => 'client',
                            'doc_type' => $document->doc_type ?? 'documents',
                            'checklist' => ($document->checklist ?? 'Document') . '_signed',
                            'file_name' => ($document->file_name ?? 'document') . '_signed',
                            'filetype' => 'pdf',
                            'myfile' => $signedPdfUrl,
                            'myfile_key' => $document->id . '_signed.pdf',
                            'source_document_id' => $document->id,
                            'user_id' => $document->user_id,
                            'not_used_doc' => null,
                        ]);
                    }
                }

                Log::info("Public document signed successfully", [
                    'document_id' => $document->id,
                    'signer_id' => $signer->id,
                    'signed_at' => now()->toISOString()
                ]);

                // Create notifications and activity logs
                $this->createSignatureNotifications($document, $signer);
                
                // Clean up temp file if exists
                if ($tempFile && file_exists($tempFile)) {
                    @unlink($tempFile);
                }

                if ($isAjax) {
                    return response()->json([
                        'success' => true,
                        'message' => 'Document signed successfully!',
                        'redirect' => route('public.documents.thankyou', ['id' => $document->id])
                    ]);
                }
                return redirect()->route('public.documents.thankyou', ['id' => $document->id])
                    ->with('success', 'Document signed successfully! You can now download your signed document.');
            }

            if ($isAjax) {
                return response()->json(['success' => false, 'message' => 'An unexpected error occurred while processing your signature.'], 500);
            }
            return redirect()->back()
                ->with('error', 'An unexpected error occurred while processing your signature.');
        } catch (\Exception $e) {
            Log::error("Error in public submitSignatures", [
                'error' => $e->getMessage(),
                'document_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);
            if ($isAjax) {
                return response()->json(['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()], 500);
            }
            return redirect()->back()
                ->with('error', 'An unexpected error occurred while processing your signature.');
        }
    }

    /**
     * Get a specific page of the PDF as an image
     */
    public function getPage($id, $page)
    {
        $tempFile = null;
        
        try {
            $document = Document::findOrFail($id);
            
            // Check if cached image already exists
            $cachedImagePath = storage_path('app/public/pdf_pages/doc_' . $id . '_page_' . $page . '.png');
            if (file_exists($cachedImagePath) && filesize($cachedImagePath) > 0) {
                return response()->file($cachedImagePath, [
                    'Content-Type' => 'image/png',
                    'Cache-Control' => 'public, max-age=86400',
                ]);
            }
            
            $url = $document->myfile;
            $pdfPath = null;

            // Check if it's an S3 URL
            if ($url && (str_contains($url, 's3.') || str_contains($url, 'amazonaws.com'))) {
                // Download from S3 to a temporary file
                $pdfPath = $this->downloadS3FileToTemp($url, $id);
                $tempFile = $pdfPath; // Track for cleanup
            } elseif ($url && file_exists(storage_path('app/public/' . $url))) {
                // Local file
                $pdfPath = storage_path('app/public/' . $url);
            }

            if (!$pdfPath || !file_exists($pdfPath)) {
                Log::error('Document file not found', [
                    'document_id' => $id,
                    'myfile' => $url,
                    'tried_path' => $pdfPath
                ]);
                abort(404, 'Document file not found');
            }

            // Ensure cache directory exists
            $cacheDir = storage_path('app/public/pdf_pages');
            if (!file_exists($cacheDir)) {
                mkdir($cacheDir, 0755, true);
            }

            // Use Python service to convert PDF page to image
            try {
                $response = Http::timeout(30)->post($this->pythonServiceUrl . '/convert_page', [
                    'file_path' => $pdfPath,
                    'page_number' => (int) $page,
                    'resolution' => 150
                ]);

                if ($response->successful()) {
                    $result = $response->json();
                    
                    if ($result['success'] ?? false) {
                        // Extract base64 image data
                        $imageData = $result['image_data'] ?? '';
                        
                        // Remove data URI prefix if present
                        if (strpos($imageData, 'data:image/png;base64,') === 0) {
                            $imageData = substr($imageData, strlen('data:image/png;base64,'));
                        }
                        
                        // Decode and save to cache
                        $imageContent = base64_decode($imageData);
                        if ($imageContent !== false) {
                            file_put_contents($cachedImagePath, $imageContent);
                            
                            // Clean up temp file
                            if ($tempFile && file_exists($tempFile)) {
                                unlink($tempFile);
                            }
                            
                            return response()->file($cachedImagePath, [
                                'Content-Type' => 'image/png',
                                'Cache-Control' => 'public, max-age=86400',
                            ]);
                        }
                    }
                    
                    Log::error('Python service returned unsuccessful result', [
                        'document_id' => $id,
                        'page' => $page,
                        'error' => $result['error'] ?? 'Unknown error'
                    ]);
                } else {
                    Log::error('Python service request failed', [
                        'document_id' => $id,
                        'page' => $page,
                        'status' => $response->status()
                    ]);
                }
            } catch (\Exception $e) {
                Log::error('Python service connection error', [
                    'document_id' => $id,
                    'page' => $page,
                    'error' => $e->getMessage()
                ]);
            }

            // Clean up temp file on error
            if ($tempFile && file_exists($tempFile)) {
                unlink($tempFile);
            }

            // Fallback: return error
            abort(503, 'PDF processing service not available. Please ensure the Python service is running.');
        } catch (\Exception $e) {
            // Clean up temp file on exception
            if ($tempFile && file_exists($tempFile)) {
                unlink($tempFile);
            }
            
            Log::error('Error in getPage', [
                'document_id' => $id,
                'page' => $page,
                'error' => $e->getMessage()
            ]);
            abort(500, 'An error occurred while retrieving the page');
        }
    }

    /**
     * Get document info including page count
     * Used by the signature editor to know how many pages the document has
     */
    public function getDocumentInfo($id)
    {
        $tempFile = null;
        
        try {
            $document = Document::findOrFail($id);
            
            $url = $document->myfile;
            $pdfPath = null;

            // Check if it's an S3 URL
            if ($url && (str_contains($url, 's3.') || str_contains($url, 'amazonaws.com'))) {
                // Download from S3 to a temporary file
                $pdfPath = $this->downloadS3FileToTemp($url, $id);
                $tempFile = $pdfPath; // Track for cleanup
            } elseif ($url && file_exists(storage_path('app/public/' . $url))) {
                // Local file
                $pdfPath = storage_path('app/public/' . $url);
            }

            if (!$pdfPath || !file_exists($pdfPath)) {
                Log::error('Document file not found for info', [
                    'document_id' => $id,
                    'myfile' => $url,
                    'tried_path' => $pdfPath
                ]);
                return response()->json([
                    'success' => false,
                    'error' => 'Document file not found',
                    'page_count' => 1
                ], 404);
            }

            // Get page count using Python service
            $pageCount = $this->countPdfPages($pdfPath);
            
            // Clean up temp file
            if ($tempFile && file_exists($tempFile)) {
                unlink($tempFile);
            }

            return response()->json([
                'success' => true,
                'document_id' => $id,
                'page_count' => $pageCount,
                'title' => $document->display_title,
                'status' => $document->status
            ]);

        } catch (\Exception $e) {
            // Clean up temp file on exception
            if ($tempFile && file_exists($tempFile)) {
                unlink($tempFile);
            }
            
            Log::error('Error getting document info', [
                'document_id' => $id,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'Failed to get document info',
                'page_count' => 1
            ], 500);
        }
    }
    
    /**
     * Download a file from S3 (or S3-compatible URL) to a temporary local file.
     * Prefer authenticated S3 disk access; HTTP uses SSL verification. Never disables TLS
     * outside local + ALLOW_INSECURE_S3_DOWNLOAD=true.
     */
    protected function downloadS3FileToTemp($s3Url, $documentId)
    {
        try {
            $tempDir = storage_path('app/temp');
            if (! file_exists($tempDir)) {
                mkdir($tempDir, 0755, true);
            }

            $tempPath = $tempDir . '/doc_' . $documentId . '_' . time() . '.pdf';
            $content = null;
            $method = null;
            $s3Key = $this->resolveS3ObjectKeyFromUrl((string) $s3Url);

            // 1) AWS SDK / Laravel S3 disk (no TLS-disable, works with private buckets)
            if ($s3Key !== null) {
                try {
                    $disk = Storage::disk('s3');
                    if ($disk->exists($s3Key)) {
                        $body = $disk->get($s3Key);
                        if (is_string($body) && $body !== '') {
                            $content = $body;
                            $method = 's3_disk_get';
                        }
                    }
                } catch (\Throwable $e) {
                    Log::warning('S3 disk get failed for public signing download', [
                        'document_id' => $documentId,
                        's3_key' => $s3Key,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // 2) Presigned URL + verified HTTP (if disk get failed but object may still be readable via URL)
            if (($content === null || $content === '') && $s3Key !== null) {
                try {
                    $disk = Storage::disk('s3');
                    if ($disk->exists($s3Key)) {
                        $presigned = $disk->temporaryUrl($s3Key, now()->addMinutes(5));
                        $response = Http::timeout(30)->get($presigned);
                        if ($response->successful() && $response->body() !== '') {
                            $content = $response->body();
                            $method = 's3_presigned_http';
                        }
                    }
                } catch (\Throwable $e) {
                    Log::warning('S3 presigned download failed for public signing', [
                        'document_id' => $documentId,
                        's3_key' => $s3Key,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // 3) Direct URL with SSL verification enabled (public objects)
            if (($content === null || $content === '') && filter_var((string) $s3Url, FILTER_VALIDATE_URL)) {
                try {
                    $response = Http::timeout(30)->get((string) $s3Url);
                    if ($response->successful() && $response->body() !== '') {
                        $content = $response->body();
                        $method = 'https_verified';
                    } else {
                        Log::warning('Verified HTTP download of S3 URL unsuccessful', [
                            'document_id' => $documentId,
                            'url' => $s3Url,
                            'status' => method_exists($response, 'status') ? $response->status() : null,
                        ]);
                    }
                } catch (\Throwable $e) {
                    Log::warning('Verified HTTP download of S3 URL failed', [
                        'document_id' => $documentId,
                        'url' => $s3Url,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // 4) Local-only emergency fallback (opt-in) — never used in production
            if (($content === null || $content === '') && $this->allowsInsecureS3Download()) {
                Log::warning('Using insecure SSL S3 download (local + ALLOW_INSECURE_S3_DOWNLOAD only)', [
                    'document_id' => $documentId,
                ]);
                $context = stream_context_create([
                    'http' => [
                        'timeout' => 30,
                        'user_agent' => 'BansalCRM/1.0',
                    ],
                    'ssl' => [
                        'verify_peer' => false,
                        'verify_peer_name' => false,
                    ],
                ]);
                $raw = @file_get_contents((string) $s3Url, false, $context);
                if ($raw !== false && $raw !== '') {
                    $content = $raw;
                    $method = 'insecure_local_fallback';
                }
            }

            if (is_string($content) && strlen($content) > 0) {
                file_put_contents($tempPath, $content);

                if (file_exists($tempPath) && filesize($tempPath) > 0) {
                    Log::info('Downloaded S3 file to temp', [
                        'document_id' => $documentId,
                        'temp_path' => $tempPath,
                        'size' => filesize($tempPath),
                        'method' => $method,
                        's3_key' => $s3Key,
                    ]);

                    return $tempPath;
                }
            }

            Log::error('Downloaded S3 file is empty or failed', [
                'url' => $s3Url,
                'document_id' => $documentId,
                's3_key' => $s3Key,
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Exception downloading S3 file', [
                'url' => $s3Url,
                'document_id' => $documentId,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Resolve S3 object key from a stored S3 URL (virtual-hosted or path-style).
     */
    protected function resolveS3ObjectKeyFromUrl(string $fileUrl): ?string
    {
        if ($fileUrl === '' || (! str_starts_with($fileUrl, 'http://') && ! str_starts_with($fileUrl, 'https://'))) {
            // May already be a relative key
            if ($fileUrl !== '' && ! str_contains($fileUrl, '..')) {
                return ltrim($fileUrl, '/');
            }

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
     * Insecure TLS download is blocked outside local development, and only when
     * ALLOW_INSECURE_S3_DOWNLOAD=true is set in .env (for broken CA chains on dev only).
     */
    protected function allowsInsecureS3Download(): bool
    {
        if (! app()->environment('local')) {
            return false;
        }

        return filter_var(env('ALLOW_INSECURE_S3_DOWNLOAD', false), FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Upload signed PDF to S3 when configured and document has a client; otherwise return local asset URL.
     * Keeps existing behavior when S3 is not configured or document has no client (e.g. partner docs).
     */
    protected function uploadSignedPdfToS3OrLocal(Document $document, string $localOutputPath): string
    {
        $localUrl = asset('storage/signed/' . $document->id . '_signed.pdf');

        if (!file_exists($localOutputPath) || filesize($localOutputPath) === 0) {
            return $localUrl;
        }

        $accessKey = config('filesystems.disks.s3.key');
        $bucket = config('filesystems.disks.s3.bucket');
        if (empty($accessKey) || empty($bucket)) {
            return $localUrl;
        }

        $client = $document->client_id ? $document->client : null;
        $clientUniqueId = $client ? ($client->client_id ?? null) : null;
        if (empty($clientUniqueId) || !is_string($clientUniqueId)) {
            return $localUrl;
        }

        $docType = $document->doc_type ?? 'documents';
        $s3Key = $clientUniqueId . '/' . $docType . '/' . $document->id . '_signed.pdf';

        try {
            $contents = file_get_contents($localOutputPath);
            if ($contents === false) {
                Log::warning('Could not read signed PDF for S3 upload', ['document_id' => $document->id]);
                return $localUrl;
            }
            Storage::disk('s3')->put($s3Key, $contents);
            $s3Url = Storage::disk('s3')->url($s3Key);
            Log::info('Signed PDF uploaded to S3', [
                'document_id' => $document->id,
                's3_key' => $s3Key,
            ]);
            return $s3Url;
        } catch (\Exception $e) {
            Log::warning('S3 upload failed for signed PDF, using local URL', [
                'document_id' => $document->id,
                'error' => $e->getMessage(),
            ]);
            return $localUrl;
        }
    }

    /**
     * Download signed document (from S3 when signed_doc_link is S3 URL, else from local storage for backward compatibility)
     */
    public function downloadSigned($id)
    {
        try {
            $document = Document::findOrFail($id);

            if (!$document->signed_doc_link) {
                return redirect('/')->with('error', 'Signed document not found.');
            }

            $link = $document->signed_doc_link;

            // S3: same behavior as ClientDocumentController::download_document
            if (str_contains($link, 's3.') || str_contains($link, 'amazonaws.com')) {
                $parsed = parse_url($link);
                if (isset($parsed['path'])) {
                    $s3Key = ltrim(urldecode($parsed['path']), '/');
                    if (Storage::disk('s3')->exists($s3Key)) {
                        $tempUrl = Storage::disk('s3')->temporaryUrl(
                            $s3Key,
                            now()->addMinutes(5),
                            [
                                'ResponseContentDisposition' => 'attachment; filename="' . $document->id . '_signed.pdf' . '"',
                                'ResponseContentType' => 'application/pdf',
                            ]
                        );
                        return redirect($tempUrl);
                    }
                }
                Log::warning('Signed document S3 key not found or invalid', ['document_id' => $id, 'link' => $link]);
                return redirect('/')->with('error', 'Signed document not found.');
            }

            // Local: backward compatibility for existing records with storage URL
            $filePath = storage_path('app/public/signed/' . $document->id . '_signed.pdf');
            if (file_exists($filePath)) {
                return response()->download($filePath, $document->id . '_signed.pdf');
            }

            return redirect('/')->with('error', 'Signed document not found.');
        } catch (\Exception $e) {
            Log::error('Error downloading signed document', [
                'document_id' => $id,
                'error' => $e->getMessage(),
            ]);
            return redirect('/')->with('error', 'An error occurred while downloading the document.');
        }
    }

    /**
     * Show thank you page after signing
     */
    public function thankyou(Request $request, $id = null)
    {
        $downloadUrl = null;
        $document = null;
        
        if ($id) {
            $document = Document::find($id);
            if ($document && $document->signed_doc_link) {
                $downloadUrl = route('public.documents.download.signed', $document->id);
            }
        }
        
        $message = 'You have successfully signed your document.';
        return view('documents.thankyou', compact('downloadUrl', 'message', 'id', 'document'));
    }

    /**
     * Send reminder to signer
     */
    public function sendReminder(Request $request, $id)
    {
        $documentId = (int) $id;
        if ($documentId <= 0) {
            return redirect()->back()->with('error', 'Invalid document ID.');
        }

        $request->validate([
            'signer_id' => 'required|integer|exists:signers,id'
        ]);

        $signerId = (int) $request->signer_id;

        try {
            $document = Document::findOrFail($documentId);
            $signer = $document->signers()->findOrFail($signerId);

            if ($signer->status === 'signed') {
                return redirect()->back()->with('error', 'Document is already signed.');
            }

            if ($signer->reminder_count >= 3) {
                return redirect()->back()->with('error', 'Maximum reminders already sent.');
            }

            // Send reminder email via AWS SES (signature From address)
            $emailService = app(\App\Services\EmailService::class);
            $emailConfig = $emailService->configureMailerForSignature(null);
            if (!$emailConfig) {
                return redirect()->back()->with('error', 'No email configuration available. Configure MAIL_* in .env or add an active email in Admin Console.');
            }

            $signingUrl = url("/sign/{$document->id}/{$signer->token}");
            $mailFrom = $emailConfig->email;
            $mailFromName = $emailConfig->display_name ?? $emailConfig->email;
            Mail::mailer('ses')->raw("This is a reminder to sign your document: " . $signingUrl, function ($message) use ($signer, $mailFrom, $mailFromName) {
                $message->to($signer->email, $signer->name)
                        ->from($mailFrom, $mailFromName)
                        ->subject('Reminder: Please Sign Your Document');
            });

            $signer->update([
                'last_reminder_sent_at' => now(),
                'reminder_count' => $signer->reminder_count + 1
            ]);

            return redirect()->back()->with('success', 'Reminder sent successfully!');
        } catch (\Exception $e) {
            Log::error('Error sending reminder', [
                'document_id' => $documentId,
                'signer_id' => $signerId,
                'error' => $e->getMessage()
            ]);
            return redirect()->back()->with('error', 'An error occurred while sending the reminder.');
        }
    }

    /**
     * Show document index
     */
    public function index($id = null)
    {
        return redirect('/')->with('info', 'Please use the link provided in your email.');
    }

    // ==================== Private Helper Methods ====================

    /**
     * Count PDF pages using Python service
     */
    protected function countPdfPages($pathToPdf)
    {
        try {
            $response = Http::timeout(15)->post($this->pythonServiceUrl . '/pdf_info', [
                'file_path' => $pathToPdf
            ]);

            if ($response->successful()) {
                $result = $response->json();
                if ($result['success'] ?? false) {
                    return $result['page_count'] ?? 1;
                }
            }
            
            Log::warning('Python service failed to get PDF info, defaulting to 1 page', [
                'file' => $pathToPdf
            ]);
            return 1;
        } catch (\Exception $e) {
            Log::error('Error counting PDF pages via Python service', [
                'file' => $pathToPdf,
                'error' => $e->getMessage()
            ]);
            return 1;
        }
    }

    /**
     * Sanitize signature data
     */
    private function sanitizeSignatureData($signatureData, $fieldId)
    {
        if (!is_string($signatureData) || empty($signatureData)) {
            return false;
        }

        $signatureData = strip_tags($signatureData);

        $dangerousPatterns = [
            '/javascript:/i', '/vbscript:/i', '/data:text\/html/i',
            '/data:application\/javascript/i', '/onclick/i', '/onload/i',
            '/onerror/i', '/<script/i', '/<iframe/i'
        ];

        foreach ($dangerousPatterns as $pattern) {
            if (preg_match($pattern, $signatureData)) {
                return false;
            }
        }

        if (!preg_match('/^data:image\/png;base64,([A-Za-z0-9+\/=]+)$/', $signatureData, $matches)) {
            return false;
        }

        $base64Data = $matches[1];

        if (strlen($base64Data) > 500000) {
            return false;
        }

        $imageData = base64_decode($base64Data, true);
        if ($imageData === false || strlen($imageData) < 100 || strlen($imageData) > 1000000) {
            return false;
        }

        // Validate PNG signature
        $pngSignature = "\x89\x50\x4E\x47\x0D\x0A\x1A\x0A";
        if (substr($imageData, 0, 8) !== $pngSignature) {
            return false;
        }

        return [
            'imageData' => $imageData,
            'base64Data' => $base64Data,
            'size' => strlen($imageData)
        ];
    }

    /**
     * Sanitize position data
     */
    private function sanitizePositionData($position)
    {
        $sanitized = [];
        $fields = ['x_percent', 'y_percent', 'w_percent', 'h_percent'];

        foreach ($fields as $field) {
            $value = $position[$field] ?? 0;
            $value = (float) $value;
            $value = max(0, min(1, $value));
            if ($field === 'w_percent') $value = max(0.1, $value);
            if ($field === 'h_percent') $value = max(0.05, $value);
            $sanitized[$field] = $value;
        }

        return $sanitized;
    }

    /**
     * Create notifications and activity logs when document is signed
     */
    private function createSignatureNotifications($document, $signer)
    {
        try {
            $signerName = $signer->name ?? 'Unknown Signer';
            $signedAt = now()->format('d/m/Y h:i A');

            // Prefer documentable morph (existing path); fall back to client_id used by client docs
            $client = null;
            $documentableType = (string) ($document->documentable_type ?? '');
            if ($document->documentable_id && (
                $documentableType === 'App\\Models\\Admin'
                || $documentableType === \App\Models\Admin::class
            )) {
                $client = \App\Models\Admin::find($document->documentable_id);
            }
            if (! $client && ! empty($document->client_id)) {
                $client = \App\Models\Admin::find($document->client_id);
            }

            if ($client) {
                $documentTitle = $document->title ?? $document->file_name ?? 'Document #' . $document->id;
                $subject = "{$signerName} signed document '{$documentTitle}' for client {$client->first_name} {$client->last_name}";
                $description = "Document signed by {$signerName} at {$signedAt}";

                // Create Activity Log
                ActivitiesLog::create([
                    'client_id' => $client->id,
                    'created_by' => $document->created_by ?? $document->user_id ?? 1,
                    'subject' => $subject,
                    'description' => $description,
                    'activity_type' => 'document',
                    'task_status' => 0,
                    'pin' => 0,
                ]);

                // Create Notification for the staff member who initiated the document when known
                $receiverId = $document->created_by ?? $document->user_id ?? null;
                if ($receiverId) {
                    Notification::create([
                        'sender_id' => $client->id,
                        'receiver_id' => $receiverId,
                        'module_id' => $document->id,
                        'url' => url("/clients/detail/{$client->id}"),
                        'notification_type' => 'document',
                        'message' => $subject,
                        'receiver_status' => 0,
                        'seen' => 0,
                    ]);
                }
            }

            Log::info('Document signature notifications processed', [
                'document_id' => $document->id,
                'client_resolved' => (bool) $client,
                'client_id' => $client->id ?? null,
            ]);

        } catch (\Exception $e) {
            Log::error('Error creating signature notifications', [
                'document_id' => $document->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Show error page for public document access
     */
    private function showErrorPage($title, $message, $document = null)
    {
        return response()->view('documents.error', [
            'title' => $title,
            'message' => $message,
            'document' => $document
        ], 200);
    }
}
