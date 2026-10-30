# Modern Image Manager

Modern Image Manager is a WordPress admin plugin for managing image metadata, importing and exporting media data, scanning image usage and creating media ZIP exports from one controlled interface.

The repository contains the plugin source and built runtime files. The WordPress.org-style `readme.txt` remains the canonical distribution metadata; this `README.md` provides the GitHub project overview.

## Why this project matters

WordPress media libraries can require repetitive edits across image metadata, export files and content-usage reports. Modern Image Manager brings these jobs into a single WordPress admin workflow so changes can be inspected before they are applied.

**Engineering work:** an admin interface for image metadata, CSV import/export, image-usage scanning and bounded media ZIP exports, with permission checks and validation for privileged operations.

**Technical decisions:** preview imports before writing, process larger libraries in bounded batches, and treat generated media/CSV exports as potentially client-sensitive data. These measures reduce risks; they do not replace staging checks or backups.

**Review the implementation:** [plugin bootstrap](modern-image-manager.php) · [source code](src/) · [WordPress distribution metadata](readme.txt)

**Developer:** [Andrew Baeten — WordPress Developer & Web Designer](https://github.com/Yolol100) · [GitHub project index](https://github.com/Yolol100?tab=repositories)

## What it does

Modern Image Manager helps administrators and media managers work with WordPress image attachments without having to inspect or edit records one by one.

Key capabilities include:

- Browse and manage image metadata from a dedicated admin interface.
- Edit titles, captions, descriptions, alt text and supported file information.
- Export selected media data to CSV.
- Preview and import CSV-based metadata updates.
- Scan where images are used across public content.
- Identify repeated or duplicate image usage.
- Create ZIP exports of media files.
- Process larger media libraries in bounded batches.
- Use WordPress capabilities and nonce validation for protected actions.

## Requirements

- WordPress 6.0 or newer.
- PHP 8.1 or newer.
- A user account with the required administration capability.

The current plugin version is defined in `modern-image-manager.php` and `readme.txt`.

## Installation

1. Copy the plugin directory to `wp-content/plugins/modern-image-manager/`.
2. Activate **Modern Image Manager** in WordPress.
3. Open **Mediatools** in the WordPress admin menu.

For production use, install the packaged plugin build rather than copying development-only source files into a live site.

## Usage

Use the Mediatools screens to select the workflow you need:

- manage media metadata;
- export media data;
- preview and import CSV changes;
- scan image usage;
- review duplicate usage;
- create a media ZIP export.

Test destructive or bulk operations on staging before using them on production content.

## Safety and data handling

The plugin uses WordPress permission checks and nonce validation around privileged actions. Import and download flows include file/path validation and bounded processing intended to reduce accidental or unsafe changes.

Media metadata can contain personal or client-specific information. Site owners remain responsible for reviewing exported CSV files and other generated artifacts before sharing them outside the site environment.

## Repository structure

- `modern-image-manager.php` — plugin bootstrap and metadata.
- `src/` — namespaced PHP application code.
- `build/` — built admin assets used by the plugin.
- `templates/` — plugin templates.
- `languages/` — translation files.
- `readme.txt` — WordPress distribution documentation and changelog.


## Safe bulk image SEO imports

Before importing CSV metadata at scale, read [the metadata-only import guardrails](docs/SAFE-MEDIA-SEO-IMPORT.md). An attachment has one global default alt text even when used on many local landing pages; page-specific Elementor \`alt\` values need separate checks. Never infer visual content from a filename or grey thumbnail placeholder. In metadata-only batches leave \`new_filename\` blank, keep media IDs, URLs, paths and slugs unchanged, and run the read-only preflight:

\`\`\`sh
python scripts/preflight_media_metadata_csv.py --reference before.csv --candidate proposed.csv
\`\`\`

The preflight does not replace a database/uploads backup, a staging import, or live page readback.

## License

GPL-2.0-or-later, as declared in the plugin metadata and `readme.txt`.
