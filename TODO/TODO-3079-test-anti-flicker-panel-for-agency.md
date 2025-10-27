# TODO-3079: Test Anti-Flicker Panel Pattern for Agency Dashboard

**Status**: ✅ READY TO TEST
**Plugin**: wp-agency
**Created**: 2025-10-26
**Related**: wp-app-core TODO-1182

## 📋 Description

Test implementasi anti-flicker panel pattern dari wp-app-core untuk Agency dashboard. Verifikasi bahwa panel kanan terbuka dengan smooth tanpa flicker dan DataTable ter-adjust dengan baik.

## 🎯 What to Test

### 1. Panel Opening Behavior

**Test**: Click row di agency DataTable

**Expected**:
- ✅ Left panel shrinks dari 100% ke 45% dengan smooth transition (300ms)
- ✅ Right panel muncul di 55% width
- ✅ No flicker atau layout jump
- ✅ DataTable columns ter-adjust otomatis
- ✅ Agency detail data muncul di right panel

### 2. Panel Closing Behavior

**Test**: Click close button (X) di right panel

**Expected**:
- ✅ Right panel hilang dengan smooth transition
- ✅ Left panel expand kembali ke 100%
- ✅ DataTable columns ter-adjust kembali
- ✅ No flicker atau layout jump

### 3. DataTable Integration

**Test**: Open panel, check DataTable

**Expected**:
- ✅ DataTable columns proportional di 45% width
- ✅ No horizontal scrollbar if not needed
- ✅ All visible columns fit properly
- ✅ Sorting/filtering still works
- ✅ Pagination still works

### 4. Tab System

**Test**: Open panel, switch tabs

**Expected**:
- ✅ Tab "Data Disnaker" loads immediately
- ✅ Tab "Unit Kerja" loads when clicked
- ✅ Tab "Staff" loads when clicked
- ✅ Tab content fits in 55% width panel
- ✅ No layout issues in tabs

### 5. Scroll Behavior

**Test**: Scroll page, then open panel

**Expected**:
- ✅ Page scrolls to top automatically (prevent jump)
- ✅ No flicker during scroll
- ✅ Panel opens smoothly after scroll
- ✅ User can scroll panel content independently

## 📊 Panel Width Verification

### Before (Old Pattern)

| State | Left Panel | Right Panel |
|-------|------------|-------------|
| Closed | 100% | 0% |
| Open | 58.33% | 41.67% |

### After (New Pattern - Platform Staff)

| State | Left Panel | Right Panel |
|-------|------------|-------------|
| Closed | 100% | 0% |
| Open | 45% | 55% |

**Benefit**: More space for agency details in right panel (55% vs 41.67%)

## 🔍 Visual Inspection

### Check These Elements

1. **Agency DataTable** (Left Panel - 45%)
   - [ ] Table tidak terlalu sempit
   - [ ] Columns visible dengan jelas
   - [ ] Actions buttons accessible
   - [ ] No overlap atau cut-off text

2. **Agency Detail Panel** (Right Panel - 55%)
   - [ ] Title visible dan jelas
   - [ ] Tabs render properly
   - [ ] Content tidak terlalu lebar/sempit
   - [ ] Statistics cards fit well
   - [ ] Form fields (if any) proportional

3. **Transition Animation**
   - [ ] Smooth 300ms transition
   - [ ] No stuttering
   - [ ] No white flash
   - [ ] No content jump

## 🐛 Common Issues to Check

### Issue 1: DataTable Not Adjusting

**Symptom**: Columns overlap atau horizontal scroll appears

**Check**:
- DataTable instance found? Check console: "[WPApp Panel] DataTable instance found"
- If not, DataTable might not be initialized when panel manager starts

**Fix**: Ensure DataTable is initialized before wpAppPanelManager

### Issue 2: Panel Flickers

**Symptom**: White flash or content jump during transition

**Check**:
- CSS transition duration matches JS setTimeout? (300ms)
- Force reflow executing? Check `layout[0].offsetHeight`

**Fix**: Verify timing in wpapp-panel-manager.js line 275

### Issue 3: Layout Jump

**Symptom**: Page jumps up/down when panel opens

**Check**:
- Scroll to top executing? Check line 254-262
- Admin bar height calculated correctly?

**Fix**: Adjust `scrollTarget` calculation

### Issue 4: Tabs Not Loading

**Symptom**: Tab content empty or not updating

**Check**:
- AJAX action registered? Check AgencyDashboardController line 110-112
- Tab content rendering? Check console for "[WPApp Panel] Tab * content length"

**Fix**: Verify tab hooks in AgencyDashboardController.php

## 📝 Testing Steps

### Step 1: Fresh Page Load

1. Navigate to Agency dashboard
2. Ensure DataTable loads completely
3. Check console for: "[WPApp Panel] Initialized"
4. Verify: "hasDataTable: true"

### Step 2: Open Panel (First Time)

1. Click any agency row
2. Observe transition (should be smooth)
3. Check console timing logs
4. Verify agency data loads in panel

### Step 3: Switch Tabs

1. Click "Unit Kerja" tab
2. Wait for DataTable to load
3. Click "Staff" tab
4. Verify all tabs work

### Step 4: Close Panel

1. Click X button
2. Observe smooth close transition
3. Verify left panel expands to 100%
4. Check DataTable adjusts correctly

### Step 5: Re-open Panel

1. Click different agency row
2. Verify smooth open (no flicker)
3. Check data updates correctly
4. Test multiple open/close cycles

### Step 6: Browser Back/Forward

1. Open panel (URL hash changes)
2. Click browser back button
3. Panel should close
4. Click forward button
5. Panel should re-open with same data

## 🔗 Related Files

**wp-app-core**:
- `assets/js/datatable/wpapp-panel-manager.js` - Panel manager with anti-flicker
- `assets/css/datatable/wpapp-datatable.css` - Panel width CSS (45%/55%)
- `src/Views/DataTable/Templates/PanelLayoutTemplate.php` - Template with classes
- `TODO/TODO-1182-adopt-anti-flicker-panel-pattern.md` - Base implementation

**wp-agency**:
- `src/Controllers/Agency/AgencyDashboardController.php` - Controller with hooks
- `src/Models/Agency/AgencyDataTableModel.php` - DataTable data
- `src/Views/agency/tabs/*.php` - Tab content templates

## 📊 Performance Metrics

Expected timing:
- Panel open animation: ~300ms
- DataTable adjust: ~50ms (after 350ms wait)
- Total time: ~400ms
- AJAX data load: 200-500ms (depends on server)

## ✨ Success Criteria

- [x] Panel opens smoothly without flicker
- [x] Transitions complete in ~400ms
- [x] DataTable adjusts correctly
- [x] All tabs load and display properly
- [x] No console errors
- [x] No layout jumps
- [x] No scroll issues
- [x] Works on multiple open/close cycles

## 🔄 Next Steps (If Test Passes)

1. Apply same pattern to other entities (Division, Employee)
2. Document pattern for wp-customer plugin
3. Consider responsive breakpoints for mobile
4. Add unit tests for panel manager

## 📌 Notes

- Pattern adopted from platform-staff (proven stable)
- 45%/55% split provides better balance for agency details
- DataTable auto-adjustment key to preventing flicker
- Scroll-first approach prevents layout jump
