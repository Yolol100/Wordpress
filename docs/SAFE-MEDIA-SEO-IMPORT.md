# Veilige bulkimport van afbeeldingsmetadata

**Doel:** een betrouwbare, herhaalbare import via Modern Image Manager, zonder onbedoelde hernoemingen, kapotte URL's of foutieve lokale SEO-claims. Dit document vervangt geen site-back-up of stagingtest.

## Kernregels

1. **Exacte media-ID is leidend.** Exporteer eerst alle afbeeldingen en bewaar de originele CSV. Importeer alleen rijen die horen bij een bestaand media-ID in dezelfde website. Gebruik geen fuzzy naamkoppeling. Laat bestaande goed beoordeelde velden ongemoeid.
2. **Wereldwijde metadata is niet paginagebonden.** In de WordPress-mediabibliotheek heeft een attachment één standaardtitel/alt-tekst/bijschrift/beschrijving, ook als het beeld op meerdere stadspagina's wordt gebruikt. Gebruik daar alleen een onderwerpgerichte, **visueel bevestigde** neutrale beschrijving. Corrigeer eventuele afwijkende `alt`-waarden in Elementor/HTML per pagina apart; een bibliotheek-CSV corrigeert die niet automatisch.
3. **Niet elke herhaling is een probleem.** Hergebruik van één media-ID op meerdere pagina's is normaal. Verschillende ID's kunnen hetzelfde zichtbare beeld bevatten, maar verifieer dit aan de hand van het origineel voordat je ze samenvoegt. Het aantal `post_parent`-koppelingen is geen sluitende gebruikstelling; scan ook Elementor, uitgelichte afbeeldingen en ACF.
4. **Nooit alt-tekst verzinnen.** Een generieke alt-tekst is niet hetzelfde als zoekwoorden herhalen. Beschrijf de echte visuele inhoud; een foto van een lesauto bewijst niet dat die in een specifieke stad is gemaakt. Decoratieve afbeeldingen kunnen een lege alt-tekst nodig hebben. Een grijze placeholder-preview bewijst niet dat het onderliggende mediabestand ontbreekt.
5. **Verander uitsluitend bedoelde velden.** Voor metadata-only: importeer `ID`, `Titel`, `Alt-tag`, `Bijschrift`, `Beschrijving` (of Engelse equivalents). Laat `Nieuwe bestandsnaam` leeg en importeer geen gewijzigde `Slug`, `Bestandsnaam`, `Bestandspad` of `Bestands-URL`. De omschrijving van een attachment is **geen** Yoast-paginametabeschrijving.
6. **Fysieke hernoeming is een apart risicoproject.** De CSV-importer kan bestaande bestanden fysiek verplaatsen wanneer `new_filename` wordt ingevuld. Elementor-strings, vaste URLs en gegenereerde afbeeldingsformaten kunnen daardoor stukgaan. Controleer verwijzingen, bevoegdheden, caching, backup en herstelpad afzonderlijk. Meng dit niet met de metadata-import.
7. **Geen onverifieerde live-claim.** Een geslaagde CSV-preflight of import bewijst niets over het op de pagina gerenderde `alt`-attribuut. Controleer eerst op staging, neem een WordPress database-/uploads-backup, importeer in kleine batches, lees bijgewerkte attachmentvelden terug en controleer belangrijke pagina's daadwerkelijk.

## Preflight op de computer

```sh
python scripts/preflight_media_metadata_csv.py --reference media-export-before.csv --candidate metadata-wijzigingen.csv
```

De validator leest beide bestanden **alleen** en rapporteert:

- onbekende of dubbele IDs en onverwachte CSV-kolommen (blokkerend);
- een ingevulde nieuwe bestandsnaam of wijziging van beschermde pad-/slug-/URL-kolommen (blokkerend);
- aantal gewijzigde media en velden;
- verdachte identieke alt-teksten op veel verschillende media-ID's (waarschuwing, **geen** bewijs van fout);
- lege doelwaarden die met de standaardimport mogelijk niet daadwerkelijk overschreven worden (waarschuwing).

Na een geslaagde preflight zijn **visuele bewijscontrole, backup, staging, import en WordPress readback** nog steeds vereist. De validator doet geen netwerkverzoeken, bewerkt geen CSV en logt geen klantmetadata.

## Bronnen

- Google Search Central: https://developers.google.com/search/docs/appearance/google-images
- W3C/WAI: https://www.w3.org/WAI/tutorials/images/decorative/
- WordPress bronvelden: `post_title`, `_wp_attachment_image_alt`, `post_excerpt`, `post_content`.