# Public Status Check Feature

## Overview
Students can now check their registration status by entering their Voter ID on a public page - no login required!

## How to Access
1. Go to homepage: `/`
2. Click "Check Status" in the navigation
3. Or visit directly: `/check-status`

## How It Works

### Step 1: Enter Voter ID
- Student enters their Voter ID (e.g., `VID-2026-00001`)
- Clicks "Check Status"

### Step 2: View Status
The page displays:

#### Email Status
- ✅ **Verified** - Email has been verified
- 🟡 **Pending** - Email verification link not clicked yet

#### School ID Status
- ✅ **Completed** - OCR processing finished
- 🟡 **Processing** - OCR is currently running or queued
- 🟡 **Pending** - Waiting for email verification
- ❌ **Failed** - OCR failed after retries

#### Account Status
- ✅ **Approved** - Account is verified, user can login
- 🟡 **Pending Verification** - Under admin review
- ❌ **Rejected** - Account was rejected by admin

#### Additional Information
- **Name**: Student's full name
- **Voter ID**: Their voter registration number
- **Verification Score**: Fraud detection score (0-100)
- **Overall Status**: Whether account is fully verified

## Example Display

```
Registration Status

Name: John Lloyd Blanquera
Voter ID: VID-2026-00001

┌─────────────────────────────┐
│ Email:                      │
│ ✅ Verified                 │
└─────────────────────────────┘

┌─────────────────────────────┐
│ School ID:                  │
│ 🟡 Processing               │
│ Your School ID image is     │
│ being processed...          │
└─────────────────────────────┘

┌─────────────────────────────┐
│ Account:                    │
│ 🟡 Pending Verification     │
│ Your account is under       │
│ review...                   │
└─────────────────────────────┘

┌─────────────────────────────┐
│ Verification Score: 85      │
│ ████████████████░░░░        │
│ ✅ High confidence          │
└─────────────────────────────┘

✅ Your account is being processed.
```

## User Journey

1. **Register** → User fills form and scans ID
2. **Success Page** → Shows Voter ID with "Check My Status" button
3. **Check Email** → Receives email with verification link
4. **Verify Email** → Clicks link to verify
5. **Check Status** → Can track progress anytime using Voter ID
6. **Wait for Approval** → Monitor status until account is approved
7. **Login** → Once approved, can login and vote

## Benefits

✅ **Transparency** - Students know exactly where they are in the process
✅ **No Login Required** - Check status before account is approved
✅ **Real-time Updates** - See OCR and verification progress
✅ **Easy Access** - Just need their Voter ID
✅ **Clear Instructions** - Knows what to do next at each stage

## Privacy & Security

- Only shows status information, no sensitive data
- Requires exact Voter ID match
- No authentication needed (public information)
- Voter ID is randomly generated and unique

## Files Created

- **Controller**: `app/Http/Controllers/CheckStatusController.php`
- **Vue Page**: `resources/js/Pages/CheckStatus.vue`
- **Routes**: Added to `routes/web.php`

## Routes

```php
// Public routes (no authentication required)
Route::get('/check-status', [CheckStatusController::class, 'show']);
Route::post('/check-status', [CheckStatusController::class, 'check']);
```

## Testing

To test the feature:

1. Register a new user
2. Copy the Voter ID from success page
3. Go to `/check-status`
4. Enter the Voter ID
5. See the status display
6. Verify email and check status again
7. Watch status update as OCR processes
