<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\BccLogoMatcher;
use App\Services\DtsRegistrationService;
use App\Services\OcrService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class IdScanController extends Controller
{
    public function reset(Request $request): RedirectResponse
    {
        if ($path = $request->session()->get('registration_scan.path')) {
            Storage::disk('local')->delete($path);
        }

        $request->session()->forget(['registration_scan', 'registration_pending', 'registration_decision']);
        Inertia::clearHistory();

        return redirect()->route('home', [], 303);
    }

    public function create(DtsRegistrationService $registration): Response|RedirectResponse
    {
        if (! $registration->isOpen()) {
            return redirect()->route('register.details');
        }

        return Inertia::render('Auth/IdScan');
    }

    public function preview(Request $request): \Symfony\Component\HttpFoundation\BinaryFileResponse|RedirectResponse
    {
        $path = $request->session()->get('registration_scan.path');
        $disk = Storage::disk('local');

        if (! is_string($path) || ! str_starts_with($path, 'registration-ids/') || ! $disk->exists($path)) {
            return redirect()->route('register.details');
        }

        return response()->file($disk->path($path), [
            'Cache-Control' => 'private, no-store, no-cache, must-revalidate',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function store(Request $request, DtsRegistrationService $registration, OcrService $ocr, BccLogoMatcher $logoMatcher): RedirectResponse
    {
        if (! $registration->isOpen()) {
            return redirect()->route('register.details');
        }

        $request->merge(['document_type' => $request->input('document_type', 'school_id')]);
        $request->validate([
            'image' => 'required|string|max:8000000',
            'image_quality' => 'required|in:good,warn,blurry',
            'document_type' => 'required|in:school_id,enrollment_form',
        ]);
        $documentType = $request->string('document_type')->toString();

        // A new upload replaces the previous scan, even if the new photo fails.
        if ($previous = $request->session()->get('registration_scan.path')) {
            Storage::disk('local')->delete($previous);
        }
        $request->session()->forget(['registration_scan', 'registration_pending']);

        $parts = explode(',', $request->string('image')->toString(), 2);
        $data = isset($parts[1]) ? base64_decode($parts[1], true) : false;
        $info = $data ? @getimagesizefromstring($data) : false;
        $extension = match ($info[2] ?? null) {
            IMAGETYPE_JPEG => 'jpg',
            IMAGETYPE_PNG => 'png',
            IMAGETYPE_WEBP => 'webp',
            default => null,
        };

        if (! $extension || strlen($data) > 5 * 1024 * 1024 || $info[0] * $info[1] > 20000000) {
            return back()->withErrors(['image' => 'Upload a valid JPEG, PNG or WebP document image up to 5 MB and 20 megapixels.']);
        }

        $disk = Storage::disk('local');
        $path = 'registration-ids/'.Str::uuid().'.'.$extension;
        if (! $disk->put($path, $data)) {
            return back()->withErrors(['image' => 'The document image could not be saved. Try again.']);
        }

        try {
            $result = $ocr->extract($disk->path($path), $documentType);
        } catch (\Throwable $exception) {
            $disk->delete($path);
            throw $exception;
        }

        if (! $result->available) {
            $disk->delete($path);
            return back()->withErrors(['image' => 'Document scanning is temporarily unavailable. Try again later.']);
        }

        if ($documentType === 'school_id' && ! $logoMatcher->matches($disk->path($path))) {
            $disk->delete($path);
            return back()->withErrors([
                'image' => 'The BCC logo pattern could not be verified. Make sure the seal is visible in the photo and try uploading the ID again.',
            ]);
        }

        $request->session()->put('registration_scan', [
            'path' => $path,
            'document_type' => $documentType,
            'preview_key' => Str::uuid()->toString(),
            'quality' => $request->input('image_quality'),
            'name' => $result->name,
            'student_id' => $result->studentId,
            'course' => $result->course,
            'year_level' => $result->yearLevel,
            'section' => $result->section,
            'year_course' => $result->yearCourse,
            'name_issue' => ! $result->name && $request->input('image_quality') === 'blurry'
                ? 'blurry'
                : $result->nameIssue,
            'expires' => now()->addHour()->timestamp,
        ]);

        return redirect()->route('register.details');
    }
}
