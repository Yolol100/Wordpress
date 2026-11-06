# Repository 1/37: Modern Image Manager — kwaliteitslog (2026-11-04)

Status: **source changes reviewed in draft PR; production/release not approved**. This is a point-in-time audit, not a claim of zero defects.

- Repository: `Yolol100/Wordpress`, product: Modern Image Manager.
- Frozen `main` baseline: `1da9e23eec504fd2e1883bbce6983160a14d24f3`; plugin version: `1.5.55`.
- Change branch: `quality/static-source-gate-20261104`; [draft pull request #939](https://github.com/Yolol100/Wordpress/pull/939).
- Scope: namespaced PHP controllers/services, media REST endpoints, CSV request validation, CSV import/export, ZIP export, admin JavaScript/CSS, existing workflow boundaries, manifests and docs.
- Environment: GitHub Actions on isolated MySQL/WordPress, **not** a connected customer website.
- Exact-head proof: review the latest PR check result **after** the last audit/test commit. Historical runs below are diagnostic evidence only.

## Confirmed defects and fixes

| ID | Severity | Finding and source | Before / reproduction | Correction and acceptance |
|---|---|---|---|---|
| WP-001 | Medium | `src/Services/MediaService.php`: a request for page 999 after deleting media could return no items but report page 1. WordPress skips `found_posts` for an empty result page. | [Failing runtime matrix](https://github.com/Yolol100/Wordpress/actions/runs/41837635128); [initial insufficient fix](https://github.com/Yolol100/Wordpress/actions/runs/41837939454); [fixture control](https://github.com/Yolol100/Wordpress/actions/runs/41838243314). | Recompute the count from page 1 only when a requested page is empty, then fetch the last valid page. Both `all` and `unlinked` are tested on real WordPress 6.8/PHP 8.1 and WP 7.0/PHP 8.3; [green run](https://github.com/Yolol100/Wordpress/actions/runs/41838483133). |
| WP-002 | Medium | `scripts/validate-request.php`: `payload: []` passed although the schema requires a JSON object. Associative `json_decode` turns `[]` and `{}` into the same PHP array. | [Negative regression failure on both PHP versions](https://github.com/Yolol100/Wordpress/actions/runs/41839011970). | Preserve the original object/list shape while parsing; reject list payloads and list roots; keep empty object payload valid. [Green run](https://github.com/Yolol100/Wordpress/actions/runs/41839217924). |
| WP-003 | Medium | `src/Services/ZipService.php`: `ZipArchive::CREATE` on an empty file created by `wp_tempnam` raised `ZipArchive::open(): Using empty file as ZipArchive is deprecated`, and `close()` errors were unchecked. | [Real PNG-to-ZIP roundtrip: ZIP bytes correct but PHP warning caused failed CI](https://github.com/Yolol100/Wordpress/actions/runs/41839765332). | Use `CREATE | OVERWRITE` (without removing/replacing the temporary path), check `close()`, clean up and fail closed on save error. [Green runtime matrix](https://github.com/Yolol100/Wordpress/actions/runs/41840049251). A forced disk-full/close-failure test is **not run**. |

## Test-implementation errors discovered and repaired

| ID | Test issue | Evidence and resolution |
|---|---|---|
| TEST-001 | The original WP-CLI `eval-file` fixture had a `strict_types` declaration that WP-CLI cannot eval as its first statement. | [Failing run](https://github.com/Yolol100/Wordpress/actions/runs/41831103689); the isolated fixture was adjusted without changing plugin logic; [green](https://github.com/Yolol100/Wordpress/actions/runs/41831404875). |
| TEST-002 | The initial page-recovery attempt trusted `found_posts` from an empty page, which WordPress does not always populate. | Red/green diagnostics in WP-001; final code queries page 1 only when needed. |
| TEST-003 | The initial negative test labeled `array root` actually encoded a JSON object with missing fields. | Corrected to a real root array and added an empty root array case; current CI must pass on the corrected head. |

## Read-only review, no deletion

- No exact duplicate source blobs were observed in the baseline repository tree. Generated assets and translation catalogs were not classified as redundant just because they contain similar strings.
- Admin bundle/CSS inspection found no `console.log`, `debugger`, `TODO`, `FIXME` or `AI-PATCH` markers in the files reviewed. This is a string inspection, **not** a full static-analysis or malware guarantee.
- REST routes already define capability callbacks. Anonymous reads and deletion are denied in isolated runtime smoke; authorized admin reads succeed. This is not a substitute for an independent adversarial security assessment.
- The `linked/unlinked` filter intentionally uses parent/`_wp_attachment_context`, not a complete content-usage proof. Do **not** interpret `unlinked` as safely unused media.
- `ImportService` and `MediaService` contain similar physical filename-rename logic. **Retain until** attachment metadata, generated thumbnails, old URLs, Elementor references, custom image sizes, atomic failure/rollback and WordPress multisite compatibility can be demonstrated. A blind deduplication here can lose content.
- The usage scanner applies `SCAN_ROW_LIMIT=5000` to individual sources; large-site completeness and performance have not been benchmarked. Do not label scan results exhaustive.
- ZIP export currently traverses the available media library without a global byte cap. Assess host quotas and large libraries before adding behavioral limits, because a new cap changes export semantics.
- CSV spreadsheet-formula escaping and re-import fidelity require a real spreadsheet and staging roundtrip before any normalization change.

## External comparisons and what we deliberately reused

1. [WordPress Core REST API Handbook](https://developer.wordpress.org/rest-api/extending-the-rest-api/adding-custom-endpoints/): per-route capabilities, request validation and dedicated controllers. Kept the existing namespaced controller architecture.
2. [WordPress `WP_Query`](https://developer.wordpress.org/reference/classes/wp_query/): query pagination/total behavior; added actual runtime regression rather than only a source regex.
3. [10up WP Scaffold](https://github.com/10up/wp-scaffold): repository-native CI, automated tests, PHP standards tooling and reproducible dependencies. Reused the **testing principle**, not the scaffold or unrelated dependencies.
4. [Enable Media Replace](https://github.com/short-pixel-optimizer/enable-media-replace): filename replacement must consider URL references and thumbnail artifacts. Deferred unsafe rename consolidation rather than copying runtime code.
5. [PHP `ZipArchive::open` and `close`](https://www.php.net/manual/en/ziparchive.open.php): `OVERWRITE` for a `tempnam` file; check `close()` because filesystem errors can occur only when writing the archive.

## Current gates and release boundaries

- **Source/CI:** PHP syntax matrix, request JSON contract regression, Python CSV preflight tests, JSON translation parse, JavaScript syntax, real WordPress/MySQL plugin activation, REST permission tests, pagination recovery, ZIP binary integrity and PHP debug-log gate.
- **Not run / not proven:** WordPress 6.0 minimum-version runtime; production or customer-site installation; browser/keyboard/visual QA; multisite; ACF/Elementor and custom integrations; large libraries; deliberate ZIP close/disk failure; WP-CLI destructive media operations; independent security review and release-package byte parity.
- **No production mutation:** all fixture attachments and ZIPs are created only inside isolated CI installations; `main` remains unchanged until reviewed merge.
- **Release caution:** the runtime code has changed but the plugin version/stable tag are still `1.5.55`. Do **not** ship this PR as a plugin release until the owner approves a version/changelog update, builds an installable ZIP and validates its extracted contents and staging behavior.
- **Stop condition:** green exact-head CI is required but insufficient alone. If CI fails, keep the PR draft and record the failure before any further code edits.
