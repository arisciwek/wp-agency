# Fix Jurisdiction Collection in Division Forms

## Issue
The JavaScript code for collecting checked jurisdiction checkboxes was incorrect, only sending the first checked value instead of all checked values. This caused jurisdiction assignments to not be saved properly when editing or creating divisions.

## Root Cause
Using `.val()` on multiple checkboxes returns only the first checked value. Need to use `:checked` selector with `.map()` and `.get()` to collect all values.

## Fixes Applied
- [x] Fixed `edit-division-form.js` to collect all checked jurisdictions
- [x] Fixed `create-division-form.js` to collect all checked jurisdictions
- [x] Fixed event name mismatch in `edit-division-form.js` for datatable refresh

## Files Modified
- `assets/js/division/edit-division-form.js`
- `assets/js/division/create-division-form.js`

## Testing
- Test editing a division with multiple jurisdictions selected
- Test creating a division with multiple jurisdictions
- Verify datatable updates correctly after save
- Check that success message appears and data is saved to database
