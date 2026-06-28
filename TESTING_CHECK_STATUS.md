# Testing the Check Status Feature

## Test Voter IDs

You can use these voter IDs to test the check status feature:

- **VID-2026-00004** (User: gsgd)
- **VID-2026-00005** (User: System Administrator)
- **VID-2026-00006** (User: Juan dela Cruz)

## How to Test

1. Go to: http://localhost/check-status (or your app URL)
2. Enter one of the voter IDs above
3. Click "Check Status"
4. You should see:
   - Name and Voter ID
   - Email Status: 🟡 Pending
   - School ID: 🟡 Pending
   - Account: 🟡 Pending Verification

## Expected Behavior

### Before Email Verification:
- Email: 🟡 Pending
- School ID: 🟡 Pending (waiting for email verification)
- Account: 🟡 Pending Verification

### After Email Verification:
- Email: ✅ Verified
- School ID: 🟡 Processing (OCR running)
- Account: 🟡 Pending Verification

### After OCR Completes:
- Email: ✅ Verified
- School ID: ✅ Completed
- Account: ✅ Approved (if score >= 80) OR 🟡 Pending (if score < 80)

## Troubleshooting

### "Voter ID not found" error
- Make sure you're typing the exact Voter ID (case-sensitive)
- Check the voter ID exists in database
- All existing users now have voter IDs generated

### Page doesn't update after clicking "Check Status"
- Check browser console for errors
- Make sure routes are cached: `php artisan route:cache`
- Clear browser cache

### Status fields show as null
- Run: `php artisan migrate` to ensure new fields exist
- Existing users have been updated with default status values

## Recent Fixes Applied

✅ Updated existing users to have status fields (email_status, ocr_status, verification_status)
✅ Generated Voter IDs for existing users who didn't have one
✅ Fixed controller to use redirect pattern with session
✅ Added null coalescing for status fields
✅ Added logging for debugging

## Check Logs

To see debug logs:
```bash
tail -f storage/logs/laravel.log
```

Look for entries like:
- "Check status request"
- "User found" 
- "Voter ID not found"
