# TODO-0532: Remove Unused NPWP and NIB Fields

## Status: IN PROGRESS

## Tasks
- [x] Update src/Database/Tables/AgencysDB.php: remove npwp and nib fields from schema, remove unique keys, update changelog.
- [x] Update src/Database/Migration.php: add runRemoveUnusedFieldsMigration method to drop npwp and nib columns if exist.
- [x] Update src/Models/Agency/AgencyModel.php: remove existsByNPWP, existsByNIB methods, remove npwp nib from create/update arrays, remove from fillable.
- [x] Update src/Controllers/AgencyController.php: remove npwp nib from create/update operations, from DOCX/PDF variables.
- [x] Update src/Validators/AgencyValidator.php: remove npwp nib validation rules.
- [x] Update src/Controllers/Auth/AgencyRegistrationHandler.php: remove npwp nib from registration.
- [x] Update src/API/APIController.php: remove npwp nib from API fields.
- [x] Update src/Database/Demo/AgencyDemoDataHelperTrait.php: remove npwp nib from demo data generation.
- [x] Update src/Database/Demo/AgencyDemoData.php: remove npwp nib from demo data.
- [x] Update view files: remove npwp nib inputs and displays from forms and templates (edit-agency-form.php, create-agency-form.php, register.php, _agency_details.php, _division_details.php, agency-detail-pdf.php).
- [x] Update JS files: remove npwp nib validation and input handling (edit-agency-form.js, create-agency-form.js, register.js).
- [x] Update assets/css/employee/employee-style.css: remove npwp related styles.
- [x] Update includes/docgen/agency-detail/class-agency-detail-provider.php: remove npwp nib from provider.
- [x] Run the migration to drop columns.
- [x] Update main TODO.md to reflect completion.
