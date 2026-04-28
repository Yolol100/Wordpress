=== Modern Image Manager ===
Contributors: webactueel
Tags: media, images, export, import, accessibility
Requires at least: 6.0
Tested up to: 6.9
Requires PHP: 8.1
Stable tag: 1.1.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Manage, import, export, scan and package WordPress image metadata from a secured admin interface.

== Description ==

Modern Image Manager helps administrators and media managers review image metadata, export selected media fields to CSV, import CSV updates, scan image usage and create a ZIP export of image files.

Security hardening in this release adds nonce validation, capability checks, safer file handling, scoped admin assets and cleaner packaging.

== Installation ==

1. Upload the plugin folder to `/wp-content/plugins/`.
2. Activate the plugin in WordPress admin.
3. Open **Modern Image** in the admin menu.

== Frequently Asked Questions ==

= Does the plugin delete media permanently? =

Yes. Delete and bulk-delete actions call WordPress attachment deletion. Test on staging before production use.

= Does the plugin process personal data? =

The plugin exports and imports media metadata such as titles, captions, descriptions, alt text and file URLs. Site owners should review whether those fields contain personal data.

== Changelog ==

= 1.1.2 =
* Added AJAX nonce checks and capability checks.
* Added import and export nonce checks.
* Scoped admin assets to plugin screens.
* Removed duplicate plugin bootstrap.
* Fixed missing helper include risk in duplicate scan callback.
* Fixed ZIP form selector mismatch.
* Removed template body tags and direct script include.
* Added package cleanup and WordPress.org-style readme.

= 1.1.0 =
* Initial public feature set.
