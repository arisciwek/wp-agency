# TODO: Redefine Tables to Use Codes Instead of IDs for Wilayah Compatibility

## Completed Tasks

## Completed Tasks
- [x] Update AgencysDB.php: change provinsi_id to provinsi_code varchar(10), regency_id to regency_code varchar(10), remove foreign keys
- [x] Update DivisionsDB.php: change provinsi_id to provinsi_code varchar(10), regency_id to regency_code varchar(10), remove foreign keys
- [x] Update JurisdictionDB.php: change regency_id to regency_code varchar(10), change foreign key
- [x] Update AgencyDemoData.php to use codes instead of IDs for provinsi and regency
- [x] Update DivisionDemoData.php to use codes
- [x] Update JurisdictionDemoData.php to use codes
- [x] Update JurisdictionData.php to use codes directly
- [x] Update AgencyModel.php to handle code lookups
- [x] Update DivisionModel.php to handle code lookups
- [x] Update DivisionController.php to use codes

## Completed Tasks
- [x] Update views (forms) to use _code field names
- [x] Update JavaScript files to handle _code fields
- [x] Update agency forms and scripts to use _code fields
- [x] Update AgencyController to handle _code fields

## Pending Tasks
- [ ] Test the changes with demo data generation
- [ ] Run database migration to alter existing tables
