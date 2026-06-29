<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Jobs\SendVerificationEmail;
use App\Models\RegistrationAttempt;
use App\Models\User;
use App\Services\OtpService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class IdScanController extends Controller
{
    public function __construct(
        private readonly OtpService $otp,
    ) {}

    public function create(Request $request): Response|RedirectResponse
    {
        if (!$request->session()->has('reg_step1')) {
            return redirect()->route('register')
                ->with('error', 'Please complete Step 1 first.');
        }

        return Inertia::render('Auth/IdScan');
    }

    public function store(Request $request): RedirectResponse
    {
        $step1 = $request->session()->get('reg_step1');

        if (!$step1) {
            return redirect()->route('register')
                ->with('error', 'Session expired. Please start registration again.');
        }

        $request->validate([
            'image'         => 'required|string',
            'image_quality' => 'required|in:good,warn,blurry',
        ]);

        $imagePath = $this->saveBase64Image($request->input('image'));

        if (!$imagePath) {
            return back()->withErrors(['image' => 'Failed to process the captured image. Please try again.']);
        }

        $voterIdNumber = User::generateVoterIdNumber();
        $verifyToken = Str::random(64);

        $user = User::create([
            'name'                => $step1['name'],
            'email'               => $step1['email'],
            'student_id_number'   => $step1['student_id_number'],
            'department_id'       => $step1['department_id'],
            'course_id'           => $step1['course_id'],
            'year_level_id'       => $step1['year_level_id'],
            'password'            => Hash::make($step1['password']),
            'role'                => User::roleFromEmail($step1['email']),
            'voter_id_number'     => $voterIdNumber,
            'id_image_path'       => $imagePath,
            'image_quality'       => $request->input('image_quality'),
            'registration_status' => User::STATUS_PENDING_OTP,
            'is_verified'         => false,
            'email_status'        => 'pending',
            'email_send_status'   => 'pending',
            'ocr_status'          => 'pending',
            'verification_status' => 'pending',
            'email_verify_token'  => $verifyToken,
        ]);

        $fingerprint = $this->deviceFingerprint($request);

        RegistrationAttempt::create([
            'device_fingerprint' => $fingerprint,
            'ip_address'         => $request->ip(),
            'action'             => 'register',
            'user_id'            => $user->id,
        ]);

        $code = $this->otp->generate($user);
        $verifyUrl = route('email.verify', ['token' => $verifyToken]);

        SendVerificationEmail::dispatch($user->id, $verifyUrl, $code, $voterIdNumber);

        $request->session()->forget('reg_step1');
        $request->session()->put('reg_user_id', $user->id);
        $request->session()->put('reg_voter_id', $voterIdNumber);
        $request->session()->put('reg_device_fp', $fingerprint);
        $request->session()->put('reg_image_quality', $request->input('image_quality'));

        return redirect()->route('register.success');
    }

    private function saveBase64Image(string $dataUrl): ?string
    {
        if (!preg_match('/^data:image\/(\w+);base64,(.+)$/', $dataUrl, $matches)) {
            return null;
        }

        $ext  = in_array($matches[1], ['jpeg', 'jpg', 'png', 'webp']) ? $matches[1] : 'jpg';
        $data = base64_decode($matches[2]);

        if (!$data) {
            return null;
        }

        $path = 'id-cards/' . uniqid('id_', true) . '.' . $ext;
        Storage::disk('public')->put($path, $data);

        return $path;
    }

    private function deviceFingerprint(Request $request): string
    {
        return hash('sha256', $request->ip() . '|' . $request->userAgent());
    }
}
