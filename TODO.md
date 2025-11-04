# TODO List for WP Agency Plugin


## TODO-3098: Add User Static ID Hook ✅ COMPLETED

**Status**: ✅ COMPLETED (2025-11-01)
**Priority**: HIGH

Add filter hooks sebelum wp_insert_user() di production code untuk allow static WordPress user ID injection. Enables demo data generation dengan predictable IDs, consistent dengan wp-customer TODO-2185. Implemented 10 hooks total (6 entity + 4 user) across wp-customer dan wp-agency.

**Hooks Implemented**:
- WordPress User: `wp_agency_agency_user_before_insert`, `wp_agency_employee_user_before_insert`
- Entity: `wp_agency_before_insert`, `wp_agency_division_before_insert`, `wp_agency_employee_before_insert`

**Changes**:
- AgencyController v1.0.8 - Added user static ID hook (lines 708-795)
- AgencyEmployeeController v1.0.8 - Added user static ID hook (lines 416-502)
- AgencyModel v1.0.11 - Added entity static ID hook
- DivisionModel v1.1.1 - Added entity static ID hook
- AgencyEmployeeModel v1.4.1 - Added entity static ID hook

**Testing**: test-user-static-id-hook.php, test-entity-static-id-hook.php, test-agency-static-id.php

**Related**: wp-customer TODO-2185, wp-app-core TODO-1190

---
