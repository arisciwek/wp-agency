# TODO: Add Regency Data for Provinces Riau, Jambi, Sumatera Selatan

## Tasks
- [x] Add new division entries in JurisdictionData.php for provinces 14 (Riau), 15 (Jambi), 16 (Sumatera Selatan)
- [ ] Validate regency codes exist in database
- [ ] Test JurisdictionDemoData generation

## Completed Tasks
- [x] Implement select available division on create division (TODO-0622): Updated getAvailableRegenciesForDivisionCreation method to use query for regencies not assigned as jurisdictions in divisions of the province, added debug logging.
