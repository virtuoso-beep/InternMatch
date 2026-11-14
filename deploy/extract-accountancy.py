"""Read a coordinator workbook without modifying it; retain row-level provenance."""
import datetime
import hashlib
import json
import sys
from pathlib import Path
import openpyxl

source, output = map(Path, sys.argv[1:3])
book = openpyxl.load_workbook(source, data_only=True)
result = {"source": source.name, "sha256": hashlib.sha256(source.read_bytes()).hexdigest(), "sheets": {}}
for sheet in book:
    if sheet.title in ("Instructions", "Field Guide"):
        continue
    rows = []
    for row_number, row in enumerate(sheet.values, 1):
        if row_number == 1 or not any(value is not None for value in row):
            continue
        if any(value in ("COLOR KEY", "CORE / REQUIRED", "SUPPORTING / OPTIONAL", "NOTES / LIMITATIONS") for value in row if isinstance(value, str)):
            continue
        values = [value.isoformat() if isinstance(value, (datetime.date, datetime.datetime)) else int(value) if isinstance(value, float) and value.is_integer() else value for value in row]
        rows.append({"row": row_number, "values": values})
    result["sheets"][sheet.title] = rows
output.parent.mkdir(parents=True, exist_ok=True)
output.write_text(json.dumps(result, ensure_ascii=False, indent=2), encoding="utf-8")
print(json.dumps({name: len(rows) for name, rows in result["sheets"].items()}))
