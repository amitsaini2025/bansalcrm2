<?php

namespace App\Http\Controllers\Admin\Client;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Auth;

use App\Models\Admin;
use App\Models\ActivitiesLog;
use App\Models\Note;
use App\Models\ClientPhone;
use App\Mail\ClientVerifyMail;
use App\Mail\GoogleReviewMail;
use App\Traits\ClientAuthorization;

use GuzzleHttp\Client;

/**
 * Client email and SMS messaging
 *
 * Methods moved from ClientsController:
 * - uploadmail
 * - enhanceMessage
 * - fetchClientContactNo
 * - isgreviewmailsent
 * - updateemailverified
 * - emailVerify
 * - emailVerifyToken (public, no auth)
 * - thankyou (public, no auth)
 *
 * Note: free-form client SMS uses #sendSmsModal on client detail → Admin Console
 * features.sms.send (UnifiedSmsManager). Legacy sendmsg method/route was never migrated.
 */
class ClientMessagingController extends Controller
{
    use ClientAuthorization;

    protected $openAiClient;

    public function __construct()
    {
        $this->middleware('auth:admin')->except(['emailVerifyToken', 'thankyou']);
        
        $this->openAiClient = new Client([
            'base_uri' => 'https://api.openai.com/v1/',
            'headers' => [
                'Authorization' => 'Bearer ' . config('services.openai.api_key'),
                'Content-Type' => 'application/json',
            ],
        ]);
    }

    /**
     * Resolve Admin and ensure staff may view/edit (allocation + grants).
     */
    private function resolveAccessibleMessagingClient($clientId, bool $forEdit = false): ?Admin
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
     * Update email to be verified wrt client id
     */
    public function updateemailverified(Request $request)
    {
        $data = $request->all();
        if (! $this->resolveAccessibleMessagingClient($data['client_id'] ?? null, true)) {
            echo json_encode(['status' => false, 'message' => 'Unauthorized']);

            return;
        }

        $recExist = Admin::where('id', $data['client_id'])
        ->update(['manual_email_phone_verified' => $data['manual_email_phone_verified']]);
         if($recExist){
             $response['status'] 	= 	true;
             $response['message']	=	'Record updated successfully';
         } else {
             $response['status'] 	= 	false;
             $response['message']	=	'Please try again';
         }
         echo json_encode($response);
    }
    
    /**
     * Send email verification email to client
     */
    public function emailVerify(Request $request)
    {
        try {
            // Validate input
            $request->validate([
                'client_email' => 'required|email',
                'client_id' => 'required|integer',
                'client_fname' => 'required|string'
            ]);
            
            // Verify client exists
            $client = Admin::find($request->client_id);
            if (!$client) {
                return response()->json([
                    'status' => false,
                    'message' => 'Client not found.'
                ], 404);
            }

            if (! $this->canEditClient($client)) {
                return response()->json([
                    'status' => false,
                    'message' => 'Unauthorized',
                ], 403);
            }
            
            // Prepare email details
            $details = [
                'fullname' => $request->client_fname,
                'title' => 'Please verify your email address by clicking the button below.',
                'client_id' => $request->client_id
            ];

            // Configure mailer - uses .env by default when no From email provided
            $emailService = app(\App\Services\EmailService::class);
            if (!$emailService->configureMailerForEmail(null)) {
                return response()->json([
                    'status' => false,
                    'message' => 'No email configuration available. Configure MAIL_* in .env or add an active email in Admin Console.'
                ], 500);
            }

            Mail::mailer('ses')->to($request->client_email)->send(new ClientVerifyMail($details));
            
            return response()->json([
                'status' => true,
                'message' => 'Verification email sent successfully.'
            ]);
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            $errors = $e->errors();
            $errorMessages = [];
            foreach ($errors as $field => $messages) {
                $errorMessages[] = implode(', ', $messages);
            }
            return response()->json([
                'status' => false,
                'message' => 'Validation failed: ' . implode(' ', $errorMessages)
            ], 422);
        } catch (\Exception $e) {
            Log::error('Verification email error: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'Failed to send verification email: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Process email verification token from email link
     */
    public function emailVerifyToken($token)
    {
        try {
            // Decode token with error handling for PHP 8.x compatibility
            $base64_decoded = base64_decode($token);
            if ($base64_decoded === false) {
                return redirect('/')->withErrors(['error' => 'Invalid verification link.']);
            }
            
            $client_id = @convert_uudecode($base64_decoded);
            if ($client_id === false || $client_id === '' || !is_numeric($client_id)) {
                return redirect('/')->withErrors(['error' => 'Invalid verification link.']);
            }
            
            // Convert to integer for safety
            $client_id = (int)$client_id;
            
            // Find client
            $client = Admin::find($client_id);
            if (!$client) {
                return redirect('/')->withErrors(['error' => 'Client not found.']);
            }
            
            // Update verification status (using update() to avoid mass assignment issues)
            Admin::where('id', $client_id)->update([
                'manual_email_phone_verified' => 1,
                'email_verified_at' => now()
            ]);
            
            // Redirect to thank you page
            return redirect()->route('emailVerify.thankyou')->with('success', 'Email verified successfully!');
            
        } catch (\Throwable $e) {
            return redirect('/')->withErrors(['error' => 'Invalid verification link.']);
        }
    }
    
    /**
     * Thank you page after email verification
     */
    public function thankyou()
    {
        return view('thankyou');
    }

    /**
     * Fetch all contact list of any client at create note popup
     */
    public function fetchClientContactNo(Request $request){
        if (! $this->resolveAccessibleMessagingClient($request->client_id, false)) {
            echo json_encode([
                'status' => false,
                'message' => 'Unauthorized',
                'clientContacts' => [],
            ]);

            return;
        }

        if( ClientPhone::where('client_id', $request->client_id)->exists())
        { 
            //Fetch All client contacts (include NULL contact_type so Send SMS dropdown shows all phones)
            $clientContacts = ClientPhone::select('client_phone','client_country_code','contact_type')
                ->where('client_id', $request->client_id)
                ->where(function ($q) {
                    $q->where('contact_type', '!=', 'Not In Use')->orWhereNull('contact_type');
                })
                ->get(); 
            
            if( !empty($clientContacts) && count($clientContacts)>0 ){
                $response['status'] 	= 	true;
                $response['message']	=	'Client contact is successfully fetched.';
                $response['clientContacts']	=	$clientContacts;
            } else {
                $response['status'] 	= 	false;
                $response['message']	=	'Please try again';
                $response['clientContacts']	=	array();
            }
        }
        else
        { 
            if( Admin::where('id', $request->client_id)->exists()){
                //Fetch All client contacts
                $clientContacts = Admin::select('phone as client_phone','country_code as client_country_code','contact_type')->where('id', $request->client_id)->get();
                if( !empty($clientContacts) && count($clientContacts)>0 ){
                    $response['status'] 	= 	true;
                    $response['message']	=	'Client contact is successfully fetched.';
                    $response['clientContacts']	=	$clientContacts;
                } else {
                    $response['status'] 	= 	false;
                    $response['message']	=	'Please try again';
                    $response['clientContacts']	=	array();
                }
            }
            else {
                $response['status'] 	= 	false;
                $response['message']	=	'Please try again';
                $response['clientContacts']	=	array();
            }
        }
        echo json_encode($response);
	}
  
    /**
     * Google review email sent
     */
    public function isgreviewmailsent(Request $request){
        $data = $request->all();
        // Default when already sent (is_greview_mail_sent == 1) so json_encode never uses undefined $response
        $response = [
            'status' => true,
            'message' => 'Google review invitation already sent',
        ];
        if($data['is_greview_mail_sent'] != 1){
            $userInfo = Admin::select('first_name','email')->where('id', $data['id'])->first();
            if($userInfo){
                // Google review email - blade uses firstname + reviewLink (not free-form body)
                $details = [
                    'title' => 'Invitation For Google Review At Bansal Immigration',
                    'firstname' => $userInfo->first_name,
                    'email' => $userInfo->email,
                    'reviewLink' => env('GOOGLE_REVIEW_LINK'),
                ];

                // Configure mailer - uses .env by default when no From email provided
                $emailService = app(\App\Services\EmailService::class);
                if (!$emailService->configureMailerForEmail(null)) {
                    $response['status'] = false;
                    $response['message'] = 'No email configuration available. Configure MAIL_* in .env or add an active email in Admin Console.';
                } elseif (\Mail::mailer('ses')->to($userInfo->email)->send(new GoogleReviewMail($details))) {
                    $objs = new ActivitiesLog;
                    $objs->client_id = $data['id'];
                    $objs->created_by = Auth::user()->id;
                    $objs->description = '<span class="text-semi-bold">Google review inviatation sent successfully</span>';
                    $objs->subject = "Google review inviatation";
                    $objs->task_status = 0;
                    $objs->pin = 0;
                    $objs->save();

                    $response['status'] 	= 	true;
                    $response['message']	=	'Google review inviatation sent successfully';
                } else {
                    $response['status'] 	= 	false;
                    $response['message']	=	'Please try again';
                }
            } else {
                $response['status'] 	= 	false;
                $response['message']	=	'Please try again';
            }
        }
        echo json_encode($response);
    }
   
  	/**
     * ChatGPT enhance message
     */
    public function enhanceMessage(Request $request)
    {
        $request->validate([
            'message' => 'required|string',
        ]);

        try {
            $response = $this->openAiClient->post('chat/completions', [
                'json' => [
                    'model' => 'gpt-3.5-turbo',
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => 'You are a professional email writer. Rewrite the following content in a more professional and polished manner:'
                        ],
                        [
                            'role' => 'user',
                            'content' => $request->message
                        ]
                    ],
                    'temperature' => 0.7,
                    'max_tokens' => 500,
                ],
            ]);

            $result = json_decode($response->getBody(), true);
            $enhancedMessage = $result['choices'][0]['message']['content'];

            return response()->json(['enhanced_message' => $enhancedMessage]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to enhance message: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Upload/record mail against a client Email tab (manual entry; does not send).
     */
    public function uploadmail(Request $request)
    {
        $validated = $request->validate([
            'from' => 'required|string|max:255',
            'to' => 'required|string|max:500',
            'subject' => 'required|string|max:500',
            'message' => 'required|string|max:100000',
            'client_id' => 'required|integer|exists:admins,id',
        ]);

        $obj = new \App\Models\Email;
        $obj->user_id = Auth::user()->id;
        $obj->from_mail = trim($validated['from']);
        $obj->to_mail = trim($validated['to']);
        $obj->subject = trim($validated['subject']);
        $obj->message = $validated['message'];
        // Keep flags compatible with Email tab conversion/inbox queries (existing behavior)
        $obj->mail_type = 1;
        $obj->client_id = (int) $validated['client_id'];
        $obj->type = 'client';
        $obj->conversion_type = 'conversion_email_fetch';
        $obj->mail_body_type = 'inbox';
        $saved = $obj->save();

        if (! $saved) {
            return redirect()->back()->with('error', \Config::get('constants.server_error'))->withInput();
        }

        return redirect()->back()->with('success', 'Email uploaded Successfully');
    }

}
