<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\OtpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    protected $otpService;

    public function __construct(OtpService $otpService)
    {
        $this->otpService = $otpService;
    }

    /**
     * Register Step 1: Submit registration data and send OTP
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', 'unique:users,username', 'alpha_dash'],
            'date_of_birth' => ['required', 'date', 'before:today'],
            'gender' => ['required', 'in:male,female,other'],
            'identifier' => ['required', 'string'], // email or phone
            'identifier_type' => ['required', 'in:email,phone'],
            'password' => ['required', 'confirmed', Password::min(8)
                ->mixedCase()
                ->numbers()
                ->symbols()
            ],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $request->all();
        $identifierType = $data['identifier_type'];
        $identifier = $data['identifier'];

        // Additional validation based on identifier type
        if ($identifierType === 'email') {
            $emailValidator = Validator::make(['email' => $identifier], [
                'email' => ['email', 'unique:users,email']
            ]);

            if ($emailValidator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $emailValidator->errors()
                ], 422);
            }
        } else {
            $phoneValidator = Validator::make(['phone' => $identifier], [
                'phone' => ['regex:/^01[3-9]\d{8}$/', 'unique:users,phone'] // BD phone format
            ]);

            if ($phoneValidator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $phoneValidator->errors()
                ], 422);
            }
        }

        // Store registration data in session/cache for later use
        cache()->put(
            "registration_data_{$identifier}",
            $data,
            now()->addMinutes(30)
        );

        // Send OTP
        $otpSent = $identifierType === 'email'
            ? $this->otpService->sendEmailOtp($identifier, 'registration')
            : $this->otpService->sendPhoneOtp($identifier, 'registration');

        if (!$otpSent) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send OTP. Please try again.'
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'OTP sent successfully',
            'data' => [
                'identifier' => $identifier,
                'identifier_type' => $identifierType,
            ]
        ]);
    }

    /**
     * Register Step 2: Verify OTP and create user
     */
    public function verifyRegistrationOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'identifier' => ['required', 'string'],
            'identifier_type' => ['required', 'in:email,phone'],
            'otp' => ['required', 'string', 'size:6'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $identifier = $request->identifier;
        $identifierType = $request->identifier_type;
        $otp = $request->otp;

        // Verify OTP
        $isValid = $this->otpService->verifyOtp(
            $identifier,
            $otp,
            $identifierType,
            'registration'
        );

        if (!$isValid) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired OTP'
            ], 400);
        }

        // Get cached registration data
        $registrationData = cache()->get("registration_data_{$identifier}");

        if (!$registrationData) {
            return response()->json([
                'success' => false,
                'message' => 'Registration session expired. Please register again.'
            ], 400);
        }

        // Create user
        $userData = [
            'name' => $registrationData['name'],
            'username' => $registrationData['username'],
            'date_of_birth' => $registrationData['date_of_birth'],
            'gender' => $registrationData['gender'],
            'password' => Hash::make($registrationData['password']),
        ];

        if ($identifierType === 'email') {
            $userData['email'] = $identifier;
            $userData['email_verified_at'] = now();
        } else {
            $userData['phone'] = $identifier;
            // You might want to add a phone_verified_at field
        }

        $user = User::create($userData);

        // Create wallets
        $user->wallets()->create(['type' => 'user', 'balance' => 0]);
        $user->wallets()->create(['type' => 'advertiser', 'balance' => 0]);

        // Clear cache
        cache()->forget("registration_data_{$identifier}");

        // Generate token
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Registration successful',
            'data' => [
                'user' => $user,
                'token' => $token,
            ]
        ]);
    }

    /**
     * Login with username/email and password
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'login' => ['required', 'string'], // username or email
            'password' => ['required', 'string'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $login = $request->login;
        $password = $request->password;

        // Check if login is email or username
        $loginType = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        $user = User::where($loginType, $login)->first();

        if (!$user || !Hash::check($password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials'
            ], 401);
        }

        // Check if user is banned
        if ($user->is_banned) {
            return response()->json([
                'success' => false,
                'message' => 'Your account has been banned. Reason: ' . $user->banned_reason
            ], 403);
        }

        // Generate token
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login successful',
            'data' => [
                'user' => $user,
                'token' => $token,
            ]
        ]);
    }

    /**
     * Resend OTP
     */
    public function resendOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'identifier' => ['required', 'string'],
            'identifier_type' => ['required', 'in:email,phone'],
            'purpose' => ['required', 'in:registration,login,password_reset'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $identifier = $request->identifier;
        $identifierType = $request->identifier_type;
        $purpose = $request->purpose;

        $otpSent = $identifierType === 'email'
            ? $this->otpService->sendEmailOtp($identifier, $purpose)
            : $this->otpService->sendPhoneOtp($identifier, $purpose);

        if (!$otpSent) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send OTP'
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'OTP sent successfully'
        ]);
    }

    /**
     * Logout
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully'
        ]);
    }

    /**
     * Get authenticated user
     */
    public function me(Request $request)
    {
        return response()->json([
            'success' => true,
            'data' => $request->user()
        ]);
    }
}