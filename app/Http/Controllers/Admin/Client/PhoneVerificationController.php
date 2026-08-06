<?php

namespace App\Http\Controllers\Admin\Client;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\ClientPhone;
use App\Services\Sms\PhoneVerificationService;
use App\Support\StaffClientVisibility;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PhoneVerificationController extends Controller
{
    protected $verificationService;

    public function __construct(PhoneVerificationService $verificationService)
    {
        $this->middleware('auth:admin');
        $this->verificationService = $verificationService;
    }

    /**
     * Resolve lead admin and ensure caller may access it (same rules as lead list/detail).
     *
     * @return Admin|JsonResponse
     */
    protected function resolveAccessibleLeadAdmin($leadId)
    {
        $admin = Admin::where('id', $leadId)->where('type', 'lead')->first()
            ?? Admin::where('lead_id', $leadId)->where('type', 'lead')->first();
        if (! $admin) {
            return response()->json(['success' => false, 'message' => 'Lead not found'], 404);
        }
        if (! StaffClientVisibility::canAccessAdminRecord((int) $admin->id, Auth::user())) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        return $admin;
    }

    /**
     * Ensure caller may access the parent admin/client for a client_phones row (C-14).
     *
     * @return ClientPhone|JsonResponse
     */
    protected function resolveAccessibleClientPhone($clientPhoneId)
    {
        $clientPhone = ClientPhone::find($clientPhoneId);
        if (! $clientPhone) {
            return response()->json(['success' => false, 'message' => 'Not found'], 404);
        }

        $denied = $this->denyUnlessCanAccessClientId($clientPhone->client_id);
        if ($denied instanceof JsonResponse) {
            return $denied;
        }

        return $clientPhone;
    }

    /**
     * Ensure caller may access the given admins.id (client or lead row used as client).
     *
     * @return true|JsonResponse
     */
    protected function denyUnlessCanAccessClientId($clientId)
    {
        if ($clientId === null || $clientId === '' || ! is_numeric($clientId)) {
            return response()->json(['success' => false, 'message' => 'Not found'], 404);
        }

        $client = Admin::find((int) $clientId);
        if (! $client) {
            return response()->json(['success' => false, 'message' => 'Not found'], 404);
        }

        if (! StaffClientVisibility::canAccessAdminRecord((int) $client->id, Auth::user())) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        return true;
    }

    /**
     * Legacy verify modal: accept client_id + phone_number, resolve ClientPhone, send OTP.
     */
    public function sendCodeLegacy(Request $request)
    {
        $request->validate([
            'client_id' => 'required|exists:admins,id',
            'phone_number' => 'required|string',
        ]);

        $access = $this->denyUnlessCanAccessClientId($request->client_id);
        if ($access instanceof JsonResponse) {
            return $access;
        }

        $clientPhone = $this->findClientPhoneByNumber((int) $request->client_id, $request->phone_number);
        if (! $clientPhone) {
            return response()->json([
                'success' => false,
                'message' => 'This phone number is not in the client\'s contact list. Please add it first, then verify.',
            ]);
        }

        return response()->json($this->verificationService->sendOTP($clientPhone->id));
    }

    /**
     * Legacy verify modal: accept client_id + phone_number + verification_code, resolve ClientPhone, verify OTP.
     */
    public function verifyCodeLegacy(Request $request)
    {
        $request->validate([
            'client_id' => 'required|exists:admins,id',
            'phone_number' => 'required|string',
            'verification_code' => 'required|string|size:6',
        ]);

        $access = $this->denyUnlessCanAccessClientId($request->client_id);
        if ($access instanceof JsonResponse) {
            return $access;
        }

        $clientPhone = $this->findClientPhoneByNumber((int) $request->client_id, $request->phone_number);
        if (! $clientPhone) {
            return response()->json([
                'success' => false,
                'message' => 'This phone number is not in the client\'s contact list.',
            ]);
        }

        return response()->json($this->verificationService->verifyOTP($clientPhone->id, $request->verification_code));
    }

    /**
     * Find ClientPhone for client by matching raw phone number (digits-only comparison).
     */
    protected function findClientPhoneByNumber(int $clientId, string $rawNumber): ?ClientPhone
    {
        $digits = preg_replace('/\D/', '', $rawNumber);
        if ($digits === '') {
            return null;
        }
        // Australian: 0412345678 -> 61412345678 for comparison
        if (strlen($digits) === 10 && str_starts_with($digits, '0')) {
            $digits = '61'.substr($digits, 1);
        }
        $phones = ClientPhone::where('client_id', $clientId)->get();
        foreach ($phones as $cp) {
            $stored = preg_replace('/\D/', '', ($cp->client_country_code ?? '').($cp->client_phone ?? ''));
            if ($stored !== '' && $stored === $digits) {
                return $cp;
            }
        }

        return null;
    }

    public function sendOTP(Request $request)
    {
        $request->validate(['client_phone_id' => 'required|exists:client_phones,id']);

        $clientPhone = $this->resolveAccessibleClientPhone($request->client_phone_id);
        if ($clientPhone instanceof JsonResponse) {
            return $clientPhone;
        }

        return response()->json($this->verificationService->sendOTP($clientPhone->id));
    }

    public function verifyOTP(Request $request)
    {
        $request->validate([
            'client_phone_id' => 'required|exists:client_phones,id',
            'otp_code' => 'required|string|size:6',
        ]);

        $clientPhone = $this->resolveAccessibleClientPhone($request->client_phone_id);
        if ($clientPhone instanceof JsonResponse) {
            return $clientPhone;
        }

        return response()->json($this->verificationService->verifyOTP($clientPhone->id, $request->otp_code));
    }

    public function resendOTP(Request $request)
    {
        $request->validate(['client_phone_id' => 'required|exists:client_phones,id']);

        $clientPhone = $this->resolveAccessibleClientPhone($request->client_phone_id);
        if ($clientPhone instanceof JsonResponse) {
            return $clientPhone;
        }

        if (! $this->verificationService->canResendOTP($clientPhone->id)) {
            return response()->json(['success' => false, 'message' => 'Please wait 30 seconds before resending.']);
        }

        return response()->json($this->verificationService->sendOTP($clientPhone->id));
    }

    public function getStatus($clientPhoneId)
    {
        $clientPhone = $this->resolveAccessibleClientPhone($clientPhoneId);
        if ($clientPhone instanceof JsonResponse) {
            return $clientPhone;
        }

        return response()->json([
            'success' => true,
            'is_verified' => (bool) $clientPhone->is_verified,
            'verified_at' => $clientPhone->verified_at?->toIso8601String(),
            'needs_verification' => $clientPhone->needsVerification(),
        ]);
    }

    public function sendOTPForLead(Request $request)
    {
        $request->validate(['lead_id' => 'required|integer']);
        $admin = $this->resolveAccessibleLeadAdmin($request->lead_id);
        if ($admin instanceof JsonResponse) {
            return $admin;
        }
        $result = $this->verificationService->sendOTPForLead($request->lead_id);
        if (isset($result['success']) && ! $result['success'] && isset($result['message']) && $result['message'] === 'Lead not found') {
            return response()->json($result, 404);
        }

        return response()->json($result);
    }

    public function verifyOTPForLead(Request $request)
    {
        $request->validate([
            'lead_id' => 'required|integer',
            'otp_code' => 'required|string|size:6',
        ]);
        $admin = $this->resolveAccessibleLeadAdmin($request->lead_id);
        if ($admin instanceof JsonResponse) {
            return $admin;
        }

        return response()->json($this->verificationService->verifyOTPForLead($request->lead_id, $request->otp_code));
    }

    public function resendOTPForLead(Request $request)
    {
        $request->validate(['lead_id' => 'required|integer']);
        $admin = $this->resolveAccessibleLeadAdmin($request->lead_id);
        if ($admin instanceof JsonResponse) {
            return $admin;
        }
        if (! $this->verificationService->canResendOTPForLead($request->lead_id)) {
            return response()->json(['success' => false, 'message' => 'Please wait 30 seconds before resending.']);
        }
        $result = $this->verificationService->sendOTPForLead($request->lead_id);
        if (isset($result['success']) && ! $result['success'] && isset($result['message']) && $result['message'] === 'Lead not found') {
            return response()->json($result, 404);
        }

        return response()->json($result);
    }

    public function getStatusForLead($leadId)
    {
        $admin = $this->resolveAccessibleLeadAdmin($leadId);
        if ($admin instanceof JsonResponse) {
            return $admin;
        }

        return response()->json([
            'success' => true,
            'is_verified' => (bool) ($admin->is_verified ?? false),
            'verified_at' => $admin->verified_at?->toIso8601String(),
            'needs_verification' => $admin->needsVerification(),
        ]);
    }
}
