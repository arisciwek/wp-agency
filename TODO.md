# # TODO List for WP Agency Plugin


## TODO-2055: Add Read Capability to Agency Role
- [x] Add 'read' capability to agency role in PermissionModel.php
- [x] Create documentation file TODO-2055-add-read-capability.md
- [x] Update TODO.md with task reference

See: [docs/TODO-2055-add-read-capability.md](docs/TODO-2055-add-read-capability.md)

---

# TODO-2021 Implementation Steps

## 1. Create Templates
- [x] Create company-invoice-dashboard.php
- [x] Create company-invoice-left-panel.php (DataTable with view payment button)
- [x] Create company-invoice-right-panel.php (tabs: detail, payment)
- [x] Create company-invoice-no-access.php

## 2. Create Controller
- [x] Create CompanyInvoiceController.php with renderMainPage, AJAX handlers, CRUD methods

## 3. Update Menu
- [ ] Add "WP Invoice Perusahaan" menu in MenuManager.php

## 4. Create Assets
- [ ] Create company-invoice-style.css
- [ ] Create company-invoice-script.js (panel navigation, AJAX)

## 5. Update Dependencies
- [ ] Register assets in class-dependencies.php for 'toplevel_page_invoice_perusahaan'

## 6. Update Main Plugin File
- [ ] Initialize CompanyInvoiceController in wp-customer.php

## 7. Testing
- [ ] Test page loads correctly
- [ ] Test DataTable functionality
- [ ] Test panel navigation
- [ ] Test AJAX calls
- [ ] Verify permissions
