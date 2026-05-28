"""
Parses Matrix for Ranking System of PSTDs.xlsx and outputs kpi-scores.csv.
Run this once to generate the seed data file, then run php artisan migrate:fresh --seed.

Output CSV columns: province, subrow_id, kpi_id, year, target, accomplished
Empty string = NULL (no data).
"""
import openpyxl
import csv
import re

EXCEL_PATH = r'c:\Herd\DOST-Provincial-Director-Ranking-System\Matrix for Ranking System of PSTDs.xlsx'
OUTPUT_PATH = r'c:\Herd\DOST-Provincial-Director-Ranking-System\storage\app\private\data\kpi-scores.csv'

# Excel row number (1-indexed) → (subrow_id, kpi_id)
# kpi-outcome.csv order → sequential auto-increment IDs after seeding.
# subrow IDs 1-61 total (2 new indicators added vs original: DRRM collaboration=29, SETUP completed=38).
ROW_TO_KPI = {
    # Outcome 1: Innovation Stimulated (kpi_id=1)
    74:  (1,  1),
    75:  (2,  1),
    # Outcome 2: Technology Adoption (kpi_id=2)
    77:  (3,  2),
    78:  (4,  2),
    79:  (5,  2),
    80:  (6,  2),
    81:  (7,  2),
    82:  (8,  2),
    # Outcome 3: Foster STI Culture (kpi_id=3)
    84:  (9,  3),
    # Outcome 4: Productivity and Efficiency (kpi_id=4)
    86:  (10, 4),
    87:  (11, 4),
    88:  (12, 4),
    89:  (13, 4),
    90:  (14, 4),
    91:  (15, 4),
    92:  (16, 4),
    94:  (17, 4),   # Micro to Small
    95:  (18, 4),   # Small to Medium
    96:  (19, 4),   # Medium to Large
    97:  (20, 4),
    98:  (21, 4),
    99:  (22, 4),
    100: (23, 4),
    101: (24, 4),
    102: (25, 4),
    103: (26, 4),
    # Outcome 5: Resiliency (kpi_id=5)
    106: (27, 5),   # Activities
    107: (28, 5),   # IEC materials
    108: (29, 5),   # DRRM-related collaboration with stakeholders
    # Outcome 7 → kpi_id=6: Effective STI Governance
    110: (30, 6),
    111: (31, 6),
    # Other Indicators → kpi_id=7
    113: (32, 7),
    114: (33, 7),
    115: (34, 7),
    116: (35, 7),
    117: (36, 7),
    118: (37, 7),   # Number of completed SETUP projects
    119: (38, 7),
    120: (39, 7),
    121: (40, 7),
    122: (41, 7),
    123: (42, 7),
    124: (43, 7),
    125: (44, 7),
    126: (45, 7),
    127: (46, 7),
    128: (47, 7),
    129: (48, 7),
    130: (49, 7),
    131: (50, 7),
    132: (51, 7),
    133: (52, 7),
    134: (53, 7),
    135: (54, 7),   # duplicate SETUP Refund Rate
    136: (55, 7),   # duplicate SETUP Refunded Amount
    # Row 137 = "53. Number of linkages..." header label, skip
    138: (56, 7),   # MOA
    139: (57, 7),   # MOU
    # Row 140 = "54. Amount of External funds..." header label, skip
    141: (58, 7),   # DOST Councils
    142: (59, 7),   # Other NGAs
    143: (60, 7),   # Funding Partners
}

# Year index → (target_col, accomplished_col) — 0-indexed columns
YEAR_COLS = {
    2022: (2, 3),
    2023: (4, 5),
    2024: (6, 7),
    2025: (8, 9),
}

# Sheet names to skip (not province data)
SKIP_SHEETS = {
    'Province Directory (Quick Acces',
    'Sheet6', 'Sheet8',
}

def normalize_value(v):
    """Return string representation or empty string for NULL-like values."""
    if v is None:
        return ''
    s = str(v).strip()
    if s in ('-', '', 'No target', 'None', 'none'):
        return ''
    # Convert floats that are whole numbers to integers for cleaner display
    if isinstance(v, float) and v == int(v):
        return str(int(v))
    if isinstance(v, float):
        return str(v)
    if isinstance(v, int):
        return str(v)
    # Keep text values (VS, N/A, complex strings) as-is
    return s


def parse_sheet(ws, province_name):
    rows_data = list(ws.iter_rows(values_only=True))
    records = []

    for row_num_1indexed, (subrow_id, kpi_id) in ROW_TO_KPI.items():
        row = rows_data[row_num_1indexed - 1]  # convert to 0-indexed
        for year, (tcol, acol) in YEAR_COLS.items():
            target = normalize_value(row[tcol] if tcol < len(row) else None)
            accomplished = normalize_value(row[acol] if acol < len(row) else None)
            # Only emit rows that have at least one non-empty value
            if target or accomplished:
                records.append({
                    'province':      province_name,
                    'subrow_id':     subrow_id,
                    'kpi_id':        kpi_id,
                    'year':          year,
                    'target':        target,
                    'accomplished':  accomplished,
                })
    return records


def main():
    wb = openpyxl.load_workbook(EXCEL_PATH, read_only=True, data_only=True)
    all_records = []
    skipped_sheets = []

    for sheet_name in wb.sheetnames:
        if sheet_name in SKIP_SHEETS or sheet_name.startswith('CSTC'):
            continue
        ws = wb[sheet_name]
        records = parse_sheet(ws, sheet_name)
        if records:
            all_records.extend(records)
            print(f'  {sheet_name}: {len(records)} records')
        else:
            skipped_sheets.append(sheet_name)
            print(f'  {sheet_name}: no data (skipped)')

    print(f'\nTotal records: {len(all_records)}')
    if skipped_sheets:
        print(f'Sheets with no data: {skipped_sheets}')

    with open(OUTPUT_PATH, 'w', newline='', encoding='utf-8') as f:
        writer = csv.DictWriter(f, fieldnames=['province', 'subrow_id', 'kpi_id', 'year', 'target', 'accomplished'])
        writer.writeheader()
        writer.writerows(all_records)

    print(f'Written to {OUTPUT_PATH}')


if __name__ == '__main__':
    main()
