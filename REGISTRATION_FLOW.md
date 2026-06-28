# New Registration Flow - Implementation Summary

## Overview
The registration flow has been updated to include email verification with OTP, queued OCR processing, and automatic verification based on fraud score.

## Flow Diagram

```
Fill Registration Form
        ↓
Scan/Upload School ID
        ↓
Submit Registration
        ↓
Save User + Save ID Image
        ↓
Generate Voter Registration ID
        ↓
Send OTP + Verification Link Email
        ↓
User Verifies Email (clicks link or uses OTP)
        ↓
Queue OCR Job
        ↓
OCR Processing (with retry on rate limit)
        ↓
Compare OCR Data with Form Data
        ↓
Calculate Fraud Score
        ↓
Auto Verify (Score ≥ 80) OR Admin Review (Score < 80)
        ↓
Login Enabled
```

## Database Fields Added

### users table
- `email_status` (string): pending / verified
- `ocr_status` (string): pending / processing / completed / failed
- `verification_status` (string): pending / approved / rejected
- `email_verify_token` (string): unique token for email verification

## New Components

### 1. Migration
- `2026_06_28_154551_add_verification_status_fields_to_users_table.php`

### 2. Job
- `app/Jobs/ProcessOcrVerification.php`
  - Processes OCR extraction
  - Calculates fraud score
  - Auto-approves if score ≥ 80
  - Retries on rate limit (3 attempts with backoff)

### 3. Mail
- `app/Mail/VerifyEmail.php`
  - Sends verification link + OTP
  - Includes voter ID number
  - Email template: `resources/views/mail/verify-email.blade.php`

### 4. Controllers
- `Auth/RegistrationSuccessController.php` - Shows voter ID after registration
- `Auth/EmailVerificationController.php` - Handles email verification and dispatches OCR job
- `Auth/RegistrationStatusController.php` - Shows verification status page (for logged-in users)
- `CheckStatusController.php` - Public status check by voter ID (no login required)

### 5. Routes Added
```php
// Public routes
Route::get('/check-status', [CheckStatusController::class, 'show'])->name('check-status');
Route::post('/check-status', [CheckStatusController::class, 'check'])->name('check-status.check');

// Guest routes
Route::get('/register/success', [RegistrationSuccessController::class, 'show'])->name('register.success');
Route::get('/email/verify/{token}', [EmailVerificationController::class, 'verify'])->name('email.verify');

// Auth routes
Route::get('/registration-status', [RegistrationStatusController::class, 'show'])->name('registration.status');
```

### 6. Vue Pages
- `resources/js/Pages/Auth/RegistrationSuccess.vue`
- `resources/js/Pages/Auth/EmailVerificationResult.vue`
- `resources/js/Pages/Auth/RegistrationStatus.vue`
- `resources/js/Pages/CheckStatus.vue` - Public status check page

## Key Changes

### IdScanController
- Removed immediate OCR processing
- Added email verification token generation
- Changed email to send verification link + OTP
- Redirects to success page instead of OTP verification

### LoginController
- Added check for `email_status === 'verified'`
- Added check for `is_verified === true` (except admins)
- Prevents login for unverified users

### User Model
- Added new fillable fields

## OCR Rate Limit Handling

When OCR.Space rate limit is hit:
1. Job throws exception
2. Laravel queue retries with backoff: 60s, 300s, 900s
3. User status shows: `ocr_status = 'processing'`
4. After successful retry: `ocr_status = 'completed'`
5. After 3 failed attempts: `ocr_status = 'failed'`

## Public Status Check

Users can check their registration status without logging in:

1. Visit `/check-status`
2. Enter their Voter ID (e.g., VID-2026-00001)
3. View their verification status:
   - **Email**: ✅ Verified / 🟡 Pending
   - **School ID**: ✅ Completed / 🟡 Processing / 🟡 Pending / ❌ Failed
   - **Account**: ✅ Approved / 🟡 Pending Verification / ❌ Rejected
4. See their verification score and overall status

This allows students to track their registration progress before they can login.

## Status Page Display

### Email
- ✅ Verified (when email is verified)
- 🟡 Pending (when not yet verified)

### School ID
- 🟡 Processing (OCR in progress or queued)
- ✅ Completed (OCR finished)
- ❌ Failed (OCR failed after retries)

### Account
- 🟡 Pending Verification (awaiting approval)
- ✅ Approved (can login)
- ❌ Rejected (rejected by admin)

## Auto-Verification Logic

User is auto-verified when:
- Email verified ✓
- OCR completed ✓
- Fraud score ≥ 80 ✓

Otherwise, admin must manually review and approve.

## Queue Setup

Make sure to run queue worker:
```bash
php artisan queue:work
```

Or for development:
```bash
php artisan queue:listen
```

## Environment Variables

Required in `.env`:
```
QUEUE_CONNECTION=database
OCRSPACE_API_KEY=your_api_key
```

## Testing Checklist

- [ ] Register new user
- [ ] Receive email with OTP and verify link
- [ ] Click verify link
- [ ] Check OCR job runs
- [ ] Verify auto-approval for score ≥ 80
- [ ] Verify admin review for score < 80
- [ ] Test login blocked when email not verified
- [ ] Test login blocked when account not approved
- [ ] Test OCR retry on rate limit
- [ ] Check status page displays correct statuses
