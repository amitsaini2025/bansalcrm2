<?php

namespace App\Services;

use App\Models\Admin;
// use App\Models\ClientAddress; // Removed: client_addresses table doesn't exist in bansalcrm2 - addresses are stored in admins table
use App\Models\ClientPhone; // bansalcrm2 uses ClientPhone
// Removed: ClientEmail, ClientPassportInformation, ClientTravelInformation, ClientCharacter, ClientVisaCountry - tables don't exist
use App\Models\ActivitiesLog;
use App\Models\clientServiceTaken; // bansalcrm2 has client_service_takens table
use App\Models\Country;
use App\Models\Staff;
use App\Models\State;
use App\Models\VisaType;
use App\Helpers\PhoneHelper;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

class ClientImportService
{
    /**
     * Import client data from JSON
     * 
     * @param array $importData
     * @param bool $skipDuplicates
     * @return array ['success' => bool, 'client_id' => int|null, 'message' => string]
     */
    public function importClient(array $importData, $skipDuplicates = true)
    {
        DB::beginTransaction();

        try {
            // Normalize Office Visit Form format (office_visit_form_v1) to internal structure
            $importData = $this->normalizeOfficeVisitFormData($importData);

            // Validate import data structure
            if (!isset($importData['client'])) {
                throw new \Exception('Invalid import file: missing client data');
            }

            $clientData = $importData['client'];
            // Lead import sets type=lead before calling this service (L-12); same duplicate rules for both.
            $entityLabel = $this->importEntityLabel($clientData['type'] ?? null);

            // Check for duplicate email and phone number if skip_duplicates is enabled
            // Shared with lead import (L-12): primary admins.email + C-18 phone match (admins + client_phones).
            if ($skipDuplicates) {
                $emailMatch = false;
                $phoneMatch = false;
                $matchedClient = null;
                $matchedPhoneNumbers = [];

                // Check for duplicate email (shared uniqueness space: clients and leads use admins.email)
                if (!empty($clientData['email'])) {
                    $existingClientByEmail = Admin::where('email', $clientData['email'])->first();

                    if ($existingClientByEmail) {
                        $emailMatch = true;
                        $matchedClient = $existingClientByEmail;
                    }
                }

                // Check for duplicate phone numbers (efficient candidate queries + normalized compare)
                $importPhoneNumbers = $this->extractPhoneNumbersFromImport($importData);

                if (! empty($importPhoneNumbers)) {
                    foreach ($importPhoneNumbers as $importPhone) {
                        $normalizedCountryCode = PhoneHelper::normalizeCountryCode($importPhone['country_code'] ?? '');
                        $normalizedPhone = $this->normalizePhoneNumber($importPhone['phone'] ?? '');

                        if ($normalizedPhone === '') {
                            continue;
                        }

                        $existingClientByPhone = $this->findExistingClientByPhone(
                            $normalizedCountryCode,
                            $normalizedPhone
                        );

                        if ($existingClientByPhone) {
                            $phoneMatch = true;
                            if (! $matchedClient) {
                                $matchedClient = $existingClientByPhone;
                            }

                            $displayPhone = PhoneHelper::formatPhoneNumber(
                                $normalizedCountryCode,
                                $importPhone['phone'] ?? $normalizedPhone
                            );

                            if (! in_array($displayPhone, $matchedPhoneNumbers, true)) {
                                $matchedPhoneNumbers[] = $displayPhone;
                            }

                            break;
                        }
                    }
                }

                // If either email or phone matches, skip import and return appropriate message
                if ($emailMatch || $phoneMatch) {
                    DB::rollBack();
                    
                    $messages = [];
                    if ($emailMatch) {
                        $messages[] = 'email ' . $clientData['email'];
                    }
                    if ($phoneMatch) {
                        if (count($matchedPhoneNumbers) === 1) {
                            $messages[] = 'phone number ' . $matchedPhoneNumbers[0];
                        } else {
                            $messages[] = 'phone number(s) ' . implode(', ', $matchedPhoneNumbers);
                        }
                    }
                    
                    $message = $entityLabel . ' with ' . implode(' and ', $messages) . ' already exists. Import skipped.';
                    
                    return [
                        'success' => false,
                        'client_id' => null,
                        'message' => $message
                    ];
                }
            }

            // Create the client first (we need the ID to generate client_id)
            $client = new Admin();
            $client->first_name = $clientData['first_name'];
            $client->last_name = $clientData['last_name'] ?? null;
            $client->email = $clientData['email'];
            $client->phone = $clientData['phone'] ?? null;
            $client->country_code = $clientData['country_code'] ?? null;
            
            // Personal Information
            $client->dob = $this->parseDate($clientData['dob'] ?? null);
            $client->age = $clientData['age'] ?? null;
            $client->gender = $clientData['gender'] ?? null;
            $client->marital_status = $clientData['marital_status'] ?? null;
            
            // Address
            $client->address = $clientData['address'] ?? null;
            $client->city = $clientData['city'] ?? null;
            $client->state = $this->mapState($clientData['state'] ?? null);
            $client->country = $this->mapCountry($clientData['country'] ?? null);
            $client->zip = $clientData['zip'] ?? null;
            
            // Passport (store country name for compatibility with migrationmanager2 import/export)
            $client->country_passport = $this->normalizeCountryPassport($clientData['country_passport'] ?? null);
            $client->passport_number = $clientData['passport_number'] ?? null; // bansalcrm2 has this in admins table
            
            // Visa Information
            $client->visa_type = $clientData['visa_type'] ?? null;
            $client->visa_opt = $clientData['visa_opt'] ?? null;
            $client->visaexpiry = $this->parseDate($clientData['visa_expiry'] ?? $clientData['visaExpiry'] ?? null);
            
            // Professional Details (bansalcrm2 specific)
            $client->nomi_occupation = $clientData['nomi_occupation'] ?? null;
            $client->skill_assessment = $clientData['skill_assessment'] ?? null;
            $client->high_quali_aus = $clientData['high_quali_aus'] ?? null;
            $client->high_quali_overseas = $clientData['high_quali_overseas'] ?? null;
            $client->relevant_work_exp_aus = $clientData['relevant_work_exp_aus'] ?? null;
            $client->relevant_work_exp_over = $clientData['relevant_work_exp_over'] ?? null;
            
            $client->email_type = $clientData['email_type'] ?? null;
            
            // Internal Information
            $client->service = $clientData['service'] ?? null;
            $client->assignee = $clientData['assignee'] ?? null;
            $client->lead_quality = $clientData['lead_quality'] ?? null;
            $client->comments_note = $clientData['comments_note'] ?? null;
            $client->married_partner = $clientData['married_partner'] ?? null;
            $client->tagname = $clientData['tagname'] ?? null;
            $client->related_files = $clientData['related_files'] ?? null;
            
            // Other
            $client->naati_py = $clientData['naati_py'] ?? null;
            // naati_test, naati_date, nati_language, py_test, py_date, py_field removed - columns don't exist in bansalcrm2 database
            $client->total_points = $clientData['total_points'] ?? null;
            $client->source = $clientData['source'] ?? null;
            $client->contact_type = $clientData['contact_type'] ?? null;
            $client->type = $clientData['type'] ?? 'client';
            // Lead list filters converted=0; set explicitly so lead imports appear on /leads
            if (strtolower((string) $client->type) === 'lead') {
                $client->converted = 0;
            }
            // Ensure status is an integer (handle string "1" from JSON)
            $client->status = is_numeric($clientData['status'] ?? 1) ? (int)$clientData['status'] : 1;
            $client->agent_id = $clientData['agent_id'] ?? null;
            
            // Verification metadata fields removed - columns don't exist in bansalcrm2 database:
            // dob_verified_date, dob_verify_document, phone_verified_date, visa_expiry_verified_at
            
            // Emergency Contact fields removed - columns don't exist in bansalcrm2 database:
            // emergency_country_code, emergency_contact_no, emergency_contact_type
            
            // System fields
            $client->password = Hash::make('CLIENT_IMPORT_' . time()); // Temporary password
            // Status already set above, don't override
            
            // System Fields — staff office from authenticated admin guard only
            $importingStaff = $this->resolveImportingStaff();
            $client->office_id = $clientData['office_id'] ?? $importingStaff?->office_id ?? null;
            // Fix: Use null coalescing operator consistently to avoid "Undefined array key" error
            $verifiedValue = $clientData['verified'] ?? 0;
            $client->verified = is_numeric($verifiedValue) ? (int)$verifiedValue : 0;
            
            $showDashboardValue = $clientData['show_dashboard_per'] ?? 0;
            $client->show_dashboard_per = is_numeric($showDashboardValue) ? (int)$showDashboardValue : 0;
            
            // Note: Archive status (is_archived, archived_by) is NOT imported - imported clients start fresh (not archived)
            // Set default value: imported clients are not archived
            $client->is_archived = 0;
            
            // Client ID (if provided, otherwise will be generated)
            if (!empty($clientData['client_id'])) {
                $client->client_id = $clientData['client_id'];
            }
            
            $client->save();
            $newClientId = $client->id;

            // Generate client_id after saving if not provided (format: FIRSTNAME + YYMM + ID)
            if (empty($client->client_id)) {
                $first_name = $clientData['first_name'] ?? 'CLIENT';
                $client->client_id = strtoupper($first_name) . date('ym') . $newClientId;
                $client->save();
            }

            // Skip addresses import - bansalcrm2 doesn't have a separate client_addresses table
            // Addresses are stored directly in the admins table (address, city, state, country, zip)
            // The primary address is already imported from the client object above (lines 74-78)
            // if (isset($importData['addresses']) && is_array($importData['addresses'])) {
            //     // Multiple addresses not supported in bansalcrm2 - only primary address in admins table
            // }

            // Import contacts (phone numbers) - Use ClientPhone for bansalcrm2
            // user_id = staff who imported (staff.id), same semantics as client create/edit phones
            $staffUserId = $importingStaff?->id;
            if (isset($importData['contacts']) && is_array($importData['contacts'])) {
                foreach ($importData['contacts'] as $contactData) {
                    if (class_exists(\App\Models\ClientPhone::class)) {
                        \App\Models\ClientPhone::create([
                            'client_id' => $newClientId,
                            'user_id' => $staffUserId,
                            'contact_type' => $contactData['contact_type'] ?? null,
                            'client_country_code' => $contactData['country_code'] ?? null,
                            'client_phone' => $contactData['phone'] ?? null,
                        ]);
                    }
                }
            }

            // Import emails - bansalcrm2 stores emails in admins table, not separate client_emails table
            if (isset($importData['emails']) && is_array($importData['emails'])) {
                foreach ($importData['emails'] as $emailData) {
                    // Update primary email if it's the main email
                    if (!empty($emailData['email']) && empty($client->email)) {
                        $client->email = $emailData['email'];
                        $client->email_type = $emailData['email_type'] ?? $client->email_type;
                        // Handle email verification
                        if (isset($emailData['is_verified']) && $emailData['is_verified']) {
                            $client->manual_email_phone_verified = 1;
                        }
                        if (!empty($emailData['verified_at'])) {
                            $client->email_verified_at = $this->parseDateTime($emailData['verified_at']);
                        }
                        $client->save();
                    }
                }
            }

            // Import passport - bansalcrm2 stores passport in admins table, not separate client_passport_informations table
            if (!empty($importData['passport']) && is_array($importData['passport'])) {
                if (empty($client->passport_number) && !empty($importData['passport']['passport_number'])) {
                    $client->passport_number = $importData['passport']['passport_number'] ?? $importData['passport']['passport'] ?? null;
                }
                if (empty($client->country_passport) && !empty($importData['passport']['passport_country'])) {
                    $client->country_passport = $this->normalizeCountryPassport($importData['passport']['passport_country'] ?? null);
                }
                // Note: passport_issue_date and passport_expiry_date are not stored in bansalcrm2 admins table
                $client->save();
            }

            // Import travel information - bansalcrm2 doesn't have client_travel_informations table
            // Skip travel import - table doesn't exist

            // Import visa countries - bansalcrm2 stores visa data in admins table (first entry only)
            if (isset($importData['visa_countries']) && is_array($importData['visa_countries']) && !empty($importData['visa_countries'])) {
                $visaData = $importData['visa_countries'][0];
                $resolvedId = app(VisaTypeResolveService::class)->resolve($visaData);
                $visaTypeModel = VisaType::find($resolvedId);
                $client->visa_type_id = $resolvedId;
                $client->visa_type = $visaTypeModel ? $visaTypeModel->name : $this->resolveVisaTypeName($visaData);
                $client->visa_grant_date = $this->parseDate($visaData['visa_grant_date'] ?? null);
                if (empty($client->visa_opt) && !empty($visaData['visa_description'])) {
                    $client->visa_opt = $visaData['visa_description'];
                }
                if (empty($client->visaexpiry) && !empty($visaData['visa_expiry_date'])) {
                    $client->visaexpiry = $this->parseDate($visaData['visa_expiry_date']);
                }
                $client->save();
            }

            // Import character information - bansalcrm2 doesn't have client_characters table
            // Skip character import - table doesn't exist

            // Import test scores (client_testscore - match migrationmanager2)
            if (isset($importData['test_scores']) && is_array($importData['test_scores']) && class_exists(\App\Models\ClientTestScore::class)) {
                foreach ($importData['test_scores'] as $testData) {
                    \App\Models\ClientTestScore::create([
                        'client_id' => $newClientId,
                        'admin_id' => null,
                        'test_type' => $testData['test_type'] ?? null,
                        'listening' => $testData['listening'] ?? null,
                        'reading' => $testData['reading'] ?? null,
                        'writing' => $testData['writing'] ?? null,
                        'speaking' => $testData['speaking'] ?? null,
                        'overall_score' => $testData['overall_score'] ?? $testData['overall'] ?? null,
                        'test_date' => $this->parseDate($testData['test_date'] ?? null),
                        'relevant_test' => 1,
                    ]);
                }
            }

            // Import services (bansalcrm2 specific - client_service_takens table)
            if (isset($importData['services']) && is_array($importData['services']) && class_exists(\App\Models\clientServiceTaken::class)) {
                foreach ($importData['services'] as $serviceData) {
                    $serviceRecord = [
                        'client_id' => $newClientId,
                        'service_type' => $serviceData['service_type'] ?? null,
                    ];
                    
                    // Add migration-specific fields
                    if ($serviceData['service_type'] === 'Migration') {
                        $serviceRecord['mig_ref_no'] = $serviceData['mig_ref_no'] ?? null;
                        $serviceRecord['mig_service'] = $serviceData['mig_service'] ?? null;
                        $serviceRecord['mig_notes'] = $serviceData['mig_notes'] ?? null;
                    }
                    
                    // Add education-specific fields
                    if ($serviceData['service_type'] === 'Education') {
                        $serviceRecord['edu_course'] = $serviceData['edu_course'] ?? null;
                        $serviceRecord['edu_college'] = $serviceData['edu_college'] ?? null;
                        $serviceRecord['edu_service_start_date'] = $serviceData['edu_service_start_date'] ?? null;
                        $serviceRecord['edu_notes'] = $serviceData['edu_notes'] ?? null;
                    }
                    
                    \App\Models\clientServiceTaken::create($serviceRecord);
                }
            }

            // Import activities (same structure from migrationmanager2 and bansalcrm2)
            if (isset($importData['activities']) && is_array($importData['activities'])) {
                foreach ($importData['activities'] as $activityData) {
                    $activityCreatedAt = $this->parseDateTime($activityData['created_at'] ?? null);
                    $taskStatusValue = $activityData['task_status'] ?? 0;
                    $pinValue = $activityData['pin'] ?? 0;
                    
                    $activityRecord = [
                        'client_id' => $newClientId,
                        'created_by' => $activityData['created_by'] ?? $staffUserId,
                        'subject' => $activityData['subject'] ?? 'Imported Activity',
                        'description' => $activityData['description'] ?? null,
                        'use_for' => $activityData['use_for'] ?? null,
                        'task_status' => is_numeric($taskStatusValue) ? (int)$taskStatusValue : 0,
                        'pin' => is_numeric($pinValue) ? (int)$pinValue : 0,
                        'created_at' => $activityCreatedAt ?? now(),
                        'updated_at' => $activityCreatedAt ?? now(),
                    ];
                    // activity_type exists in bansalcrm2 activities_logs
                    if (Schema::hasColumn('activities_logs', 'activity_type')) {
                        $activityRecord['activity_type'] = $activityData['activity_type'] ?? 'note';
                    }
                    ActivitiesLog::create($activityRecord);
                }
            }

            DB::commit();

            return [
                'success' => true,
                'client_id' => $newClientId,
                'client_id_reference' => $client->client_id,
                'message' => $entityLabel . ' imported successfully. Client ID: ' . $client->client_id
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            
            // Log full error details for debugging
            $fullError = $e->getMessage();
            $errorDetails = [
                'error' => $fullError,
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ];
            
            // Extract SQL error details if available
            if (method_exists($e, 'getPrevious') && $e->getPrevious()) {
                $errorDetails['previous'] = $e->getPrevious()->getMessage();
                $fullError = $e->getPrevious()->getMessage() . ' | ' . $fullError;
            }
            
            Log::error('Client import error', $errorDetails);

            // Provide more user-friendly error messages
            $errorMessage = $fullError;
            
            // Check for common database errors and provide clearer messages
            if (strpos($errorMessage, 'SQLSTATE') !== false || strpos($errorMessage, 'violates') !== false) {
                // Extract column name from error if possible
                preg_match('/column "([^"]+)"/i', $errorMessage, $columnMatch);
                $columnName = !empty($columnMatch[1]) ? $columnMatch[1] : 'unknown';
                
                if (strpos($errorMessage, 'violates not-null constraint') !== false || strpos($errorMessage, 'null value in column') !== false) {
                    $errorMessage = "Database error: Required field '{$columnName}' is missing or null. Please check the import file contains all required client data.";
                } elseif (strpos($errorMessage, 'duplicate key value') !== false || strpos($errorMessage, 'unique constraint') !== false) {
                    $errorMessage = 'Database error: Duplicate record detected. The client may already exist in the system (email, phone, or client_id already exists).';
                } elseif (strpos($errorMessage, 'foreign key constraint') !== false) {
                    $errorMessage = 'Database error: Invalid reference data. Please check agent_id, country, or state values in the import file.';
                } elseif (strpos($errorMessage, 'invalid input syntax') !== false) {
                    $errorMessage = "Database error: Invalid data format for field '{$columnName}'. Please check the data type matches the expected format.";
                } else {
                    // Show the actual error for debugging
                    $errorMessage = 'Database error: ' . $fullError . ' Please check the import file format and try again.';
                }
            } elseif (strpos($errorMessage, 'Call to a member function') !== false) {
                $errorMessage = 'Data format error: Invalid data structure in import file. Please verify the JSON file is correctly formatted.';
            }

            return [
                'success' => false,
                'client_id' => null,
                'message' => $errorMessage
            ];
        }
    }

    /**
     * Normalize Office Visit Form (office_visit_form_v1) data to internal CRM structure.
     * Maps field names and applies fixed/default values per spec.
     */
    private function normalizeOfficeVisitFormData(array $importData): array
    {
        if (($importData['format'] ?? '') !== 'office_visit_form_v1') {
            return $importData;
        }

        $client = $importData['client'] ?? [];

        // Map visa_expiry → visaExpiry (for backwards compat with existing import logic)
        if (isset($client['visa_expiry']) && !isset($client['visaExpiry'])) {
            $client['visaExpiry'] = $client['visa_expiry'];
        }

        // Build naati_py from naati_test and py_test (Office Visit Form uses separate Yes/No fields)
        if (!isset($client['naati_py']) || $client['naati_py'] === '') {
            $naatiPy = [];
            if (($client['naati_test'] ?? '') === 'Yes') {
                $naatiPy[] = 'Naati';
            }
            if (($client['py_test'] ?? '') === 'Yes') {
                $naatiPy[] = 'PY';
            }
            $client['naati_py'] = !empty($naatiPy) ? implode(',', $naatiPy) : null;
        }

        // Apply fixed/default values per Office Visit Form spec
        $client['service'] = $client['service'] ?? 'Education';
        $client['source'] = $client['source'] ?? 'Office visit';
        $client['contact_type'] = $client['contact_type'] ?? 'Personal';
        $client['email_type'] = $client['email_type'] ?? 'Personal';
        $client['lead_quality'] = $client['lead_quality'] ?? 'Warm';
        $client['type'] = $client['type'] ?? 'lead';
        $client['status'] = $client['status'] ?? 1;

        $importData['client'] = $client;

        // Normalize test_scores: overall → overall_score (Office Visit Form uses 'overall')
        if (!empty($importData['test_scores']) && is_array($importData['test_scores'])) {
            foreach ($importData['test_scores'] as $i => $test) {
                if (isset($test['overall']) && !isset($test['overall_score'])) {
                    $importData['test_scores'][$i]['overall_score'] = $test['overall'];
                }
            }
        }

        return $importData;
    }

    /**
     * Parse date string to Y-m-d format.
     * Explicitly supports dd/mm/yyyy (Office Visit Form and CRM UI format).
     */
    private function parseDate($date)
    {
        if (empty($date)) {
            return null;
        }

        $date = trim($date);

        try {
            // Already Y-m-d
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
                return $date;
            }
            // dd/mm/yyyy (Office Visit Form spec)
            if (preg_match('/^\d{1,2}\/\d{1,2}\/\d{4}$/', $date)) {
                return Carbon::createFromFormat('d/m/Y', $date)->format('Y-m-d');
            }
            // Fallback: let Carbon try to parse (handles other common formats)
            return Carbon::parse($date)->format('Y-m-d');
        } catch (\Exception $e) {
            Log::warning('Failed to parse date: ' . $date, ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Parse datetime string
     */
    private function parseDateTime($datetime)
    {
        if (empty($datetime)) {
            return null;
        }

        try {
            return Carbon::parse($datetime);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Map state to database ID.
     * Supports: numeric ID, or name lookup (e.g. Office Visit Form sends "Victoria").
     */
    private function mapState($state)
    {
        if (empty($state)) {
            return null;
        }
        if (is_numeric($state)) {
            return $state;
        }
        $stateModel = State::where('name', $state)->first();
        return $stateModel ? $stateModel->id : $state;
    }

    /**
     * Map country to database ID.
     * Supports: numeric ID, sortname (e.g. AU, IN), or full name (e.g. Office Visit Form sends "Australia").
     */
    private function mapCountry($country)
    {
        if (empty($country)) {
            return null;
        }
        if (is_numeric($country)) {
            return $country;
        }

        if (is_string($country)) {
            $country = trim($country);
            if (strlen($country) <= 3) {
                $countryModel = Country::where('sortname', strtoupper($country))->first();
                if ($countryModel) {
                    return $countryModel->id;
                }
            }
            $countryModel = Country::where('name', $country)->first();
            if ($countryModel) {
                return $countryModel->id;
            }
        }

        return $country;
    }

    /**
     * Normalize country_passport to country name (bansalcrm2 and migrationmanager2 use name, not sortname).
     * If value is a 2–3 letter sortname (e.g. IN, AU), resolve to full name; otherwise return as-is.
     */
    private function normalizeCountryPassport(?string $value): ?string
    {
        if ($value === null || trim($value) === '') {
            return null;
        }
        $value = trim($value);
        if (strlen($value) <= 3) {
            $country = Country::where('sortname', strtoupper($value))->first();
            if ($country) {
                return $country->name;
            }
        }
        return $value;
    }

    /**
     * Resolve visa type name from visa data.
     * 
     * bansalcrm2 stores visa type as a name string (e.g., "600 - Visitor - Outside Australia"),
     * while migrationmanager2 exports include:
     * - visa_type: numeric Matter ID (not useful for bansalcrm2)
     * - visa_type_matter_nick_name: portable identifier
     * - visa_type_matter_title: human-readable title
     * 
     * Priority:
     * 1. visa_type_matter_nick_name (most portable cross-system identifier)
     * 2. visa_type_matter_title (human-readable fallback)
     * 3. visa_type if it's a non-numeric string (backwards compat with older/same-system exports)
     * 
     * @param array $visaData
     * @return string|null
     */
    private function resolveVisaTypeName(array $visaData)
    {
        // Prefer nick_name (most portable cross-system identifier)
        $nickName = $visaData['visa_type_matter_nick_name'] ?? null;
        if (is_string($nickName) && $nickName !== '') {
            return $nickName;
        }

        // Fall back to title (human-readable)
        $title = $visaData['visa_type_matter_title'] ?? null;
        if (is_string($title) && $title !== '') {
            return $title;
        }

        // Fall back to visa_type for backwards compatibility
        // Only use if it's a non-empty string that doesn't look like a pure numeric ID
        $visaType = $visaData['visa_type'] ?? null;
        if ($visaType !== null && $visaType !== '') {
            // If it's already a name string (not purely numeric), use it
            // This handles older bansalcrm2 exports where visa_type is already the name
            if (!is_numeric($visaType)) {
                return $visaType;
            }
            // If it's numeric, it's likely a Matter ID from migrationmanager2
            // We can't resolve it without the portable fields, so log a warning
            Log::warning('Visa import: numeric visa_type without portable fields', [
                'visa_type' => $visaType,
                'hint' => 'Export may be from migrationmanager2 without visa_type_matter_title/nick_name'
            ]);
        }

        return null;
    }

    /**
     * Logged-in admin staff performing the import (auth:admin / Staff model).
     * client_phones.user_id and similar columns store staff.id, not client admins.id.
     */
    private function resolveImportingStaff(): ?Staff
    {
        $user = Auth::guard('admin')->user();
        if ($user instanceof Staff) {
            return $user;
        }

        return null;
    }

    /**
     * Human label for import messages (client vs lead share this service).
     */
    private function importEntityLabel($type): string
    {
        return strtolower((string) ($type ?? 'client')) === 'lead' ? 'Lead' : 'Client';
    }

    /**
     * Find an existing admin who already owns this logical phone
     * (client_phones or admins.primary phone). Candidate-narrowed queries, then normalize in PHP.
     */
    private function findExistingClientByPhone(string $normalizedCountryCode, string $normalizedPhone): ?Admin
    {
        if ($normalizedPhone === '') {
            return null;
        }

        $suffix = $this->phoneCandidateSuffix($normalizedPhone);

        // client_phones: narrow by national number fragments (country formats differ in DB)
        $phoneRows = ClientPhone::query()
            ->where(function ($q) use ($normalizedPhone, $suffix) {
                $q->where('client_phone', $normalizedPhone)
                    ->orWhere('client_phone', 'like', '%'.$suffix);
            })
            ->limit(100)
            ->get(['id', 'client_id', 'client_country_code', 'client_phone']);

        foreach ($phoneRows as $existingPhone) {
            $attrs = $existingPhone->getAttributes();
            if (! $this->isSameLogicalPhone(
                $normalizedCountryCode,
                $normalizedPhone,
                $attrs['client_country_code'] ?? null,
                $attrs['client_phone'] ?? null
            )) {
                continue;
            }

            $owner = Admin::find($existingPhone->client_id);
            if ($owner) {
                return $owner;
            }
        }

        // admins.phone (+ country_code when present)
        $admins = Admin::query()
            ->whereNotNull('phone')
            ->where('phone', '!=', '')
            ->where(function ($q) use ($normalizedPhone, $suffix) {
                $q->where('phone', $normalizedPhone)
                    ->orWhere('phone', 'like', '%'.$suffix);
            })
            ->limit(100)
            ->get(['id', 'phone', 'country_code']);

        foreach ($admins as $client) {
            if ($this->isSameLogicalPhone(
                $normalizedCountryCode,
                $normalizedPhone,
                $client->country_code ?? null,
                $client->phone ?? null
            )) {
                return $client;
            }
        }

        return null;
    }

    /**
     * Digits must match; country matches when stored has a country, else phone-only (legacy admins).
     */
    private function isSameLogicalPhone(
        string $importCountryNormalized,
        string $importPhoneNormalized,
        $storedCountry,
        $storedPhone
    ): bool {
        $storedPhoneNormalized = $this->normalizePhoneNumber(
            $storedPhone === null ? null : (string) $storedPhone
        );

        if ($importPhoneNormalized === '' || $storedPhoneNormalized === '') {
            return false;
        }

        if ($importPhoneNormalized !== $storedPhoneNormalized) {
            return false;
        }

        $storedCountryRaw = trim((string) ($storedCountry ?? ''));
        if ($storedCountryRaw === '') {
            // Preserve former admins.phone behaviour: digits-only when no country stored
            return true;
        }

        return $importCountryNormalized === PhoneHelper::normalizeCountryCode($storedCountryRaw);
    }

    /**
     * Last 9 digits (or full number if shorter) for LIKE candidate window.
     */
    private function phoneCandidateSuffix(string $digits): string
    {
        $digits = preg_replace('/\D/', '', $digits) ?? '';
        if ($digits === '') {
            return '';
        }

        return strlen($digits) <= 9 ? $digits : substr($digits, -9);
    }

    /**
     * Extract phone numbers from import data
     * Checks both the contacts array and the client's direct phone fields
     * 
     * @param array $importData
     * @return array Array of phone numbers with country_code and phone
     */
    private function extractPhoneNumbersFromImport(array $importData): array
    {
        $phoneNumbers = [];

        // Extract from contacts array
        if (isset($importData['contacts']) && is_array($importData['contacts'])) {
            foreach ($importData['contacts'] as $contact) {
                if (!empty($contact['phone'])) {
                    $phoneNumbers[] = [
                        'country_code' => $contact['country_code'] ?? null,
                        'phone' => $contact['phone']
                    ];
                }
            }
        }

        // Extract from client's direct phone fields (if present)
        if (isset($importData['client'])) {
            $clientData = $importData['client'];
            
            // Check main phone field
            if (!empty($clientData['phone'])) {
                $phoneNumbers[] = [
                    'country_code' => $clientData['country_code'] ?? null,
                    'phone' => $clientData['phone']
                ];
            }
            
        }

        return $phoneNumbers;
    }

    /**
     * Normalize phone number for comparison
     * Removes spaces, dashes, parentheses, and other formatting
     * 
     * @param string|null $phone
     * @return string Normalized phone number
     */
    private function normalizePhoneNumber(?string $phone): string
    {
        if (empty($phone)) {
            return '';
        }

        // Remove all non-digit characters except + (though + should be in country code, not phone)
        return preg_replace('/[^\d]/', '', trim($phone));
    }
}
