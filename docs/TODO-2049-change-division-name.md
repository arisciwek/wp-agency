# TODO-2049: Change Division Name

private function generatePusatDivision($agency, $division_user_id): void {}

private function generateCabangDivisions($agency): void {}

## Description


'name' => sprintf('%s Division %s',
$agency->name,
$regency_name),

in $division_data = [];

I want it to become:

'name' => sprintf('UPT %s',
$agency->name,
$regency_name),

## Tasks
- [x] Update `TODO.md` with completed tasks

## Files to Modify
- `src/Database/Demo/DivisionDemoData.php`
\- `docs/TODO-2049-change-division-name.md` (this file)
- `TODO.md`

## Notes
The division name is generated dynamically in the `generateCabangDivisions` method. 
