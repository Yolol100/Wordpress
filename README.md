# Modern Image Manager

[![WordPress](https://img.shields.io/badge/WordPress-6.4%2B-blue.svg)](https://wordpress.org)  
[![PHP](https://img.shields.io/badge/PHP-8.2%2B-green.svg)](https://www.php.net/)  
[![License](https://img.shields.io/badge/license-GPL--2.0-orange.svg)](https://www.gnu.org/licenses/gpl-2.0.html)  

**Modern Image Manager** is een krachtige en gebruiksvriendelijke WordPress-plugin voor professioneel mediabeheer. Organiseer en beheer media-items, bewerk metadata in bulk, importeer en exporteer media via CSV, detecteer dubbele media en archiveer media in ZIP-bestanden — alles zonder pagina-herlaad dankzij een moderne AJAX-interface.

---

## 📌 Kernfeatures

- ✅ **CSV export**: Exporteer media metadata zoals bestandsnaam, titel, alt-tekst en beschrijving naar CSV-bestanden.  
- ✅ **CSV import**: Importeer nieuwe media of update bestaande media metadata in bulk via CSV-upload.  
- ✅ **Bestanden hernoemen**: Hernoem mediabestanden veilig met automatische metadata- en linkupdates.  
- ✅ **Bulk metadata bewerken**: Pas titels, alt-teksten en beschrijvingen van media-items in bulk aan.  
- ✅ **Koppelingen controleren**: Bekijk of en waar media-items gekoppeld zijn aan WordPress-berichten of ACF-velden.  
- ✅ **Dubbele media detectie**: Scan op en beheer dubbele mediabestanden.  
- ✅ **ZIP-archivering**: Maak ZIP-bestanden van geselecteerde media voor backup of download.  
- ✅ **AJAX-interface**: Alle acties verlopen snel en vloeiend zonder pagina-herlaad.  
- ✅ **Toegankelijk & Veilig**: Met nonce beveiliging, input sanitatie, ARIA-labels en keyboard navigatie.

---

## 🚀 Installatie

### Automatisch via WordPress

1. Ga naar je WordPress dashboard.  
2. Navigeer naar **Plugins > Nieuwe plugin**.  
3. Zoek op **Modern Image Manager** en klik op **Nu installeren**.  
4. Activeer de plugin.

### Handmatig

1. Clone de repository of download de bestanden:  
   ```bash
   git clone https://github.com/Yolol100/Wordpress.git
   ```  
2. Upload de pluginmap naar `/wp-content/plugins/`.  
3. Activeer via **Plugins** in WordPress.

---

## 🛠️ Gebruik

### Media Exporteren

- Navigeer naar **Modern Image > Export Media**.  
- Selecteer media om te exporteren (alle, gekoppeld, ongekoppeld).  
- Kies welke metadata kolommen mee moeten in de export.  
- Klik op **Export to CSV** om het bestand te downloaden.

### Media Importeren

- Ga naar **Modern Image > Import Media**.  
- Upload je CSV-bestand met media metadata.  
- Configureer opties zoals overschrijven van lege velden.  
- Klik op **Importeren** om media toe te voegen of bij te werken.

### Bestanden hernoemen & metadata bewerken

- Gebruik de plugin interface om bestandsnamen te wijzigen.  
- Bewerk bulkgewijs metadata (titel, alt, beschrijving).  
- Alle acties worden via AJAX verwerkt, zonder paginavernieuwing.

### Dubbele media beheren

- Ga naar **Modern Image > Scan Duplicates**.  
- Scan en bekijk dubbele mediabestanden.  
- Filter, sorteer en beheer duplicaten eenvoudig.

### ZIP-archivering

- Ga naar **Modern Image > ZIP Media**.  
- Selecteer bestanden om in een ZIP te archiveren.  
- Download het ZIP-bestand direct via je browser.

---

## 🔧 Systeemvereisten

- WordPress 6.0 of hoger  
- PHP 8.2 of hoger  
- MySQL 5.6 of hoger  

---

## 🔒 Beveiliging & Privacy

- Voorkomt directe toegang via `defined('ABSPATH')`.  
- AJAX-acties beveiligd met WordPress nonces.  
- Input/output wordt grondig gesanitized en geescaped.  
- Geen onnodige data opslag of deling, privacyvriendelijk.

---

## 🌍 Vertalingen & Toegankelijkheid

- Klaar voor vertalingen met `.pot` bestanden.  
- Compatibel met **Loco Translate**.  
- UI voorzien van ARIA-labels en keyboard-navigatie.

---

## 🤝 Bijdragen

Wil je bijdragen? Fork de repo, maak een feature branch, commit en open een pull request.

---

## 📜 Licentie

GPL-2.0 — vrij om te gebruiken, aan te passen en verspreiden.

---

**Modern Image Manager** bespaart je tijd en moeite met geavanceerd mediabeheer, ongeacht de grootte van je WordPress-bibliotheek.

---

Wil je dat ik ook voorbeeld CSV-sjablonen, FAQ of changelog toevoeg? Of een Engelse vertaling schrijf? Laat maar weten!
