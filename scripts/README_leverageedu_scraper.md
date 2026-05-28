# Leverage Edu Study Location Scraper

This tool fetches `https://leverageedu.com/study-locations/study-in-uk/`, discovers the other `study-in-*` country pages linked from that page, and writes the data into a fixed-schema Excel workbook.

## Install

```powershell
python -m pip install -r scripts/requirements-leverageedu-scraper.txt
```

## First Run

```powershell
python scripts/leverageedu_study_locations.py
```

Default outputs:

- `storage/app/leverageedu_study_locations_content.xlsx`
- `storage/app/leverageedu_study_locations_content.snapshot.json`
- `storage/app/leverageedu_study_locations_content.json`

The Laravel V2 country pages read the JSON file at request time:

```text
/countries-v2/study-in-uk
```

## Later Runs

Run the same command again:

```powershell
python scripts/leverageedu_study_locations.py
```

If the source data changed, the script prints the changed percentage and the first changed fields, writes a pending review workbook, then asks for approval before replacing the existing workbook and snapshot.

Useful options:

```powershell
python scripts/leverageedu_study_locations.py --no-approve
python scripts/leverageedu_study_locations.py --approve
python scripts/leverageedu_study_locations.py --force
```

- `--no-approve` fetches and compares but never replaces the approved workbook.
- `--approve` replaces automatically if changes are found.
- `--force` rewrites the workbook when there are no comparable data changes.

## Workbook Structure

The Excel workbook contains website-ready content only. It does not include scrape hashes, HTTP status, source audit columns, raw HTML, or change logs.

The generator excludes the site's "What's Trending" content from the workbook, JSON, and comparison snapshot so that section does not appear on the V2 country pages.

- `Pages` (including v2 route/nav/flag/hero metadata)
- `Sections`
- `Cards`
- `Courses` (structured "Top Courses to Study in..." records)
- `Images`
- `IndianStudents`
- `UiText` (v2 display labels, CTA labels/URLs, and small parsing label lists)

The content JSON file powers the website page. The snapshot JSON file is used internally for comparing future runs. Keep both beside the workbook if you want the dynamic page and change detection to keep working.

The comparison percentage is calculated as:

```text
(added records + removed records + modified records) / max(old record count, new record count) * 100
```

Volatile fetch timestamps are excluded from comparison, so a clean rerun does not show false changes.
