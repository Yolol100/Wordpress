=== Modern Image Manager ===
Contributors: webactueel
Tags: media, images, export, import, accessibility
Requires at least: 6.0
Tested up to: 7.0
Requires PHP: 8.1
Stable tag: 1.5.55
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Beheer, importeer, exporteer, scan en archiveer WordPress-afbeeldingsmetadata vanuit een beveiligde admininterface.

== Description ==

Modern Image Manager helpt beheerders en mediamanagers om afbeeldingsmetadata te controleren, geselecteerde mediavelden naar CSV te exporteren, CSV-updates te importeren, afbeeldingsgebruik te scannen en een ZIP-export van mediabestanden te maken.

Deze release bevat extra beveiliging met nonce-validatie, capability-controles, veiligere bestandsafhandeling, alleen op de pluginpagina geladen admin-assets en schonere packaging.

== Installation ==

1. Upload de pluginmap naar `/wp-content/plugins/`.
2. Activeer de plugin in WordPress admin.
3. Open **Mediatools** in het adminmenu.

== Frequently Asked Questions ==

= Verwijdert de plugin media permanent? =

Verwijderen en bulkverwijderen gebruiken WordPress attachment deletion zonder permanente verwijdering te forceren. Als media-prullenbak actief is, kunnen items eerst naar de prullenbak gaan; anders kan WordPress ze direct verwijderen. Test dit op staging vóór productiegebruik.

= Verwerkt de plugin persoonsgegevens? =

De plugin exporteert en importeert mediametadata zoals titels, bijschriften, beschrijvingen, alt-tekst en bestands-URL’s. Site-eigenaren moeten beoordelen of die velden persoonsgegevens bevatten.

== Changelog ==

= 1.5.55 =
* Merge-release: behoudt capability-hardening, CSV-exporthardening, batch-filtering en extensie-lock bij hernoemen.
* CSV-import van nieuwe attachments zet nu expliciet het gekoppelde uploadbestand voor betrouwbaardere media-URL's en metadata.
* REST-response behoudt naast `file_extension` ook `filename_extension` als backward-compatible extensieveld.

= 1.5.54 =
* Bestandshernoeming beperkt tot de naam vóór de extensie; de bestaande extensie blijft zichtbaar in de interface en server-side afgedwongen.
* REST-update en CSV-import normaliseren nieuwe bestandsnamen zodat extensiewijzigingen niet worden toegepast.

= 1.5.53 =
* Import, export en ZIP-downloads aangescherpt naar een filterbare beheer-capability met veilige UI-fallback.
* CSV-export gehard tegen spreadsheet formula injection.
* Linked/unlinked mediafilters verwerkt nu in batches in plaats van één volledige mediabibliotheek in geheugen te laden.
* Adminconfig uitgebreid met capabilitystatus voor veiliger clientgedrag.

= 1.5.52 =
* Activatie-fatal op PHP 8.1 opgelost door het PHP 8.2-only return type `true|WP_Error` te vervangen door `bool|WP_Error`.

= 1.5.51 =
* PHP 8.1 request-hardening voor admin asset routing, CSV-export en CSV-import.
* Asset-fallback dependencies aangescherpt zodat de React-admin UI ook bij ontbrekende assetmetadata de juiste WordPress packages vraagt.
* Gerepareerd pakket opnieuw opgebouwd met de canonieke pluginmap `modern-image-manager`.

= 1.5.50 =
* Versie opgehoogd en release-metadata gelijkgetrokken.
* Laatste statische controle uitgevoerd op headers, CSS/JS, beveiligde adminflows en packaging.
* Import-uploadcontrol, full-width schermen en moderne toolbar bevestigd als huidige releasebasis.

= 1.5.48 =
* Buttontekst in de toolbar gecentreerd en toolbar-knophoogte gelijkgetrokken met de moderne zoekvelden.

= 1.5.46 =
* De ongebruikte statusbadge uit de gebruiksscan verwijderd voor een rustiger scanpaneel.

= 1.5.45 =
* Beheren-scherm, metadata-paneel, exportinstellingen, gebruiksscan en media-zip visueel samengebracht tot één consistente adminstijl.
* Statuskolom compacter verwerkt in de mediarij en metadataformulier gegroepeerd in Bestand, SEO metadata en Beschrijving.
* Exportbereik, scanstatus en media-zip compacter gemaakt zonder losse overridebestanden of dubbele UI-logica.

= 1.5.42 =
* Gebruiksscan-resultaten gemoderniseerd met actiebar, resultaatkaarten, thumbnail, gebruiksbadge en bronlabels.

= 1.5.41 =
* De beheer-toolbar visueel verfijnd met betere groepering voor statistieken, paginering en bulkacties.
* Formuliervelden en knoppen consistenter en moderner gemaakt voor de beheerweergave.
* Adminmenu hernoemd naar Mediatools en het menu-icoon aangepast zodat dit niet wordt verward met de standaard upload-/mediabibliotheek.

= 1.5.36 =
* Admin-layout verfijnd: rechter metadata-paneel compacter gemaakt en tablet/mobiele breakpoints aangescherpt.

= 1.5.34 =
* Releaseverpakking en vertaalbestanden opgeschoond en gevalideerd.

= 1.5.32 =
* Metadata wordt pas opgeslagen nadat een eventuele bestandshernoeming succesvol is, zodat een mislukte rename geen gedeeltelijke update veroorzaakt.
* CSV-import blokkeert te grote bestanden en dubbele headers na normalisatie.
* CSV-import maakt nieuwe attachments alleen nog aan via een veilig relatief uploadpad of uploads-URL, niet meer op basis van alleen een losse bestandsnaam.
* Gebruiksscanresultaten tonen nu bronlabels en aantallen voor betere controle.
* Admin UI verbeterd met toetsenbordselectie, duidelijke focusstijl, verplichte CSV-keuze en detailregels voor overgeslagen importregels.
* Versiebadge komt nu uit de pluginconfig in plaats van uit een hardcoded UI-string.

= 1.5.29 =
* Improved admin labels, scan wording, import preview, ZIP availability messaging and safer delete confirmations.
* Aligned duplicate scan REST permissions with the media manager UI capability.
* Changed attachment deletion to avoid forcing permanent deletion.

= 1.5.27 =
* Removed the media dashboard intro heading so the toolbar and media table start immediately below the tabs.

= 1.5.26 =
* Tightened the admin layout with a more compact header, tabbar and toolbar.
* Moved media counts into the toolbar and refined list/detail panel spacing, active rows and empty list surface.

= 1.5.25 =
* Restyled the admin app into a cleaner WordPress React and premium SaaS console layout.
* Improved header, tabs, cards, toolbar, media rows, detail panel, forms, status pills and responsive spacing.

= 1.5.24 =
* Fixed admin CSS loading by avoiding a hard failure when the WordPress components stylesheet handle is unavailable.
* Uses build file modification times for admin asset versions to prevent stale cached CSS/JS after reinstalling the plugin package.
* Repacked the release ZIP with one canonical plugin folder.

= 1.5.23 =
* Rebuilt the React admin UI with WordPress components for tabs, cards, controls, buttons, notices and loading states.
* Removed duplicate custom button, input, tab and hero styling in favor of scoped layout CSS.
* Added wp-components as an admin asset dependency.
* Fixed duplicate scan aggregation so attachment IDs are preserved across content, featured image, ACF and Elementor sources.
* Synchronized the compiled admin asset version with the plugin version to prevent stale cached React/CSS assets after updates.

= 1.5.5 =
* Fixed release packaging so the ZIP contains one canonical plugin folder and no nested work directory.
* Prevented unchanged filename saves/imports from generating unnecessary duplicate filenames.
* Hardened CSV-created attachment handling against WP_Error results.

= 1.5.4 =
* Switched REST update and bulk delete handlers to validated request parameters instead of raw JSON payloads.
* Hardened image file validation with WordPress MIME checks.
* Improved CSV import error handling for failed renames and failed metadata updates.
* Added GIF and BMP MIME support for existing uploaded image imports.

= 1.5.2 =
* Hardened CSV import streaming, row limits and filename matching.
* Moved ZIP generation to the server temp directory and tightened download cleanup.
* Restricted duplicate scan results to image attachments and reduced false positives.
* Added stricter REST body argument validation for media updates.

= 1.5.0 =
* Modernized admin asset loading with deferred WordPress script strategy metadata and JSON config injection.
* Tightened REST argument validation and pagination bounds.
* Improved React admin accessibility states, table semantics and status regions.
* Added modern scoped CSS features including cascade layers, container queries and motion/contrast preferences.

= 1.4.4 =
* Synchronized runtime asset version with the plugin version.
* Restricted REST update, delete and bulk delete actions to image attachments only.
* Hardened media service delete/update guards against non-image attachment IDs.

= 1.4.3 =
* Added shared uploads path guard for rename, import-created attachments and ZIP export.
* Tightened bulk-delete REST permission checks against every selected attachment.
* Normalized CSV headers to support BOM-prefixed CSV exports.

= 1.4.2 =
* Cleaned release package to runtime files only.
* Aligned scan submenu capability with the scan page and REST permission.
* Moved import result data to the enqueued admin script.
* Made the CSV empty-value overwrite option functional.
* Removed unnecessary scoped CSS important flags.
* Export and ZIP now process media in paged batches instead of stopping at the first 500 images.
* Linked/unlinked media filtering now checks attachment parent and plugin context consistently.
* CSV import now uses WordPress file type validation and stricter upload path checks.
* Duplicate scan now includes all public post types instead of only posts and pages.
* Download responses now send safer no-cache and nosniff headers.

= 1.4.0 =
* Moved PHP runtime to PSR-4-style namespaced classes under src/.
* Split admin React source into resources/js/admin screens, components and API modules.
* Moved admin SCSS source to resources/scss/admin.
* Added Composer, PHPCS, PHPStan and release packaging config.
* Removed legacy includes and global class bootstrapping.

= 1.2.0 =
* Replaced admin screens with a WordPress-native React admin app.
* Added REST endpoints for media listing, saving, deleting, bulk deletion and duplicate scanning.
* Added build/admin assets backed by modern React source files.
* Preserved CSV import/export, ZIP export and media metadata behavior.

= 1.1.3 =
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
