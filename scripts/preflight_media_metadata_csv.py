#!/usr/bin/env python3
"""Read-only preflight for Modern Image Manager metadata-only CSV imports.

Usage: python scripts/preflight_media_metadata_csv.py --reference before.csv --candidate update.csv
The tool never changes either CSV or any WordPress site.
"""
from __future__ import annotations

import argparse
import collections
import csv
import json
import pathlib
import sys

ALIASES = {
    'id': 'id', 'attachment_id': 'id', 'bijlage_id': 'id',
    'titel': 'title', 'title': 'title',
    'alt_tag': 'alt_text', 'alt_text': 'alt_text',
    'bijschrift': 'caption', 'caption': 'caption',
    'beschrijving': 'description', 'description': 'description',
    'bestandsnaam': 'filename', 'filename': 'filename',
    'bestandspad': 'file_path', 'file_path': 'file_path',
    'bestands_url': 'file_url', 'file_url': 'file_url', 'url': 'file_url',
    'nieuwe_bestandsnaam': 'new_filename', 'new_filename': 'new_filename',
    'slug': 'slug', 'pagina': 'page', 'page': 'page',
}
METADATA = {'title', 'alt_text', 'caption', 'description'}
PROTECTED = {'filename', 'file_path', 'file_url', 'slug', 'page'}


def canonical(header: str) -> str:
    key = header.strip().lower().replace(' ', '_').replace('-', '_').replace('.', '_')
    return ALIASES.get(key, key)


def parse(path: pathlib.Path, errors: list[str], label: str) -> tuple[dict[int, dict[str, str]], set[str]]:
    result: dict[int, dict[str, str]] = {}
    with path.open('r', encoding='utf-8-sig', newline='') as handle:
        reader = csv.DictReader(handle)
        if not reader.fieldnames:
            errors.append(f'{label}: no header')
            return result, set()
        keys = [canonical(col) for col in reader.fieldnames]
        if len(keys) != len(set(keys)):
            errors.append(f'{label}: duplicate normalized header')
            return result, set(keys)
        if 'id' not in keys:
            errors.append(f'{label}: ID column required')
            return result, set(keys)
        for number, row in enumerate(reader, start=2):
            if None in row or any(value is None for value in row.values()):
                errors.append(f'{label}: invalid CSV field count at line {number}')
                continue
            data = dict(zip(keys, row.values()))
            raw_id = data.get('id', '').strip()
            if not raw_id.isdecimal() or int(raw_id) < 1:
                errors.append(f'{label}: invalid ID at line {number}')
                continue
            attachment_id = int(raw_id)
            if attachment_id in result:
                errors.append(f'{label}: duplicate ID {attachment_id}')
                continue
            result[attachment_id] = data
    return result, set(keys)


def check(reference: pathlib.Path, candidate: pathlib.Path) -> dict:
    errors: list[str] = []
    warnings: list[str] = []
    original, old_columns = parse(reference, errors, 'reference')
    proposed, new_columns = parse(candidate, errors, 'candidate')
    if not original or not proposed:
        errors.append('Reference and candidate must both contain image rows')
    if not new_columns & METADATA:
        errors.append('Candidate has no editable metadata columns')
    extra = new_columns - METADATA - PROTECTED - {'id', 'new_filename'}
    if extra:
        errors.append('Unexpected columns: ' + ', '.join(sorted(extra)))
    if not new_columns.issubset(old_columns):
        errors.append('Candidate columns missing from reference: ' + ', '.join(sorted(new_columns - old_columns)))
    edited_ids: list[int] = []
    changed_cells = collections.Counter()
    repeated_alt = collections.defaultdict(set)
    for attachment_id, row in proposed.items():
        baseline = original.get(attachment_id)
        if baseline is None:
            errors.append(f'Unknown media ID {attachment_id}: import would not be a verified update')
            continue
        if row.get('new_filename', '').strip():
            errors.append(f'ID {attachment_id}: new_filename must stay blank (metadata-only mode)')
        for col in PROTECTED & new_columns:
            if row.get(col, '') != baseline.get(col, ''):
                errors.append(f'ID {attachment_id}: protected field {col} changed')
        touched = False
        for col in METADATA & new_columns:
            before, after = baseline.get(col, ''), row.get(col, '')
            if before == after:
                continue
            if after == '':
                warnings.append(f'ID {attachment_id}: clearing {col} requires explicit WordPress overwrite-empty control')
            else:
                touched = True
                changed_cells[col] += 1
        if touched:
            edited_ids.append(attachment_id)
        if row.get('alt_text', '').strip():
            repeated_alt[row['alt_text'].strip().casefold()].add(attachment_id)
    for alt, ids in repeated_alt.items():
        if len(ids) >= 10:
            warnings.append(f'{len(ids)} media IDs share an identical alt text; verify that each represents equivalent visible content')
    if len(proposed) > 0 and len(edited_ids) == 0:
        warnings.append('No nonempty metadata changes found')
    return {
        'status': 'PASS_WITH_WARNINGS' if warnings and not errors else ('FAIL' if errors else 'PASS'),
        'reference_rows': len(original), 'candidate_rows': len(proposed),
        'changed_media_ids': len(edited_ids), 'changed_fields': dict(sorted(changed_cells.items())),
        'errors': errors, 'warnings': warnings,
        'note': 'Static CSV preflight only. Verify visible image, page-level alt and staging readback separately.',
    }


def main() -> int:
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--reference', type=pathlib.Path, required=True)
    parser.add_argument('--candidate', type=pathlib.Path, required=True)
    args = parser.parse_args()
    try:
        report = check(args.reference, args.candidate)
    except (OSError, UnicodeError, csv.Error) as exc:
        report = {'status': 'FAIL', 'errors': [str(exc)]}
    print(json.dumps(report, ensure_ascii=False, indent=2))
    return 0 if report['status'] != 'FAIL' else 2


if __name__ == '__main__':
    sys.exit(main())
