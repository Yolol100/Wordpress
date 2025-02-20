# Modern Image Manager

[![WordPress](https://img.shields.io/badge/WordPress-6.4%2B-blue.svg)](https://wordpress.org)
[![PHP](https://img.shields.io/badge/PHP-7.4%2B-green.svg)](https://www.php.net/)
[![License](https://img.shields.io/badge/license-GPL--2.0-orange.svg)](https://www.gnu.org/licenses/gpl-2.0.html)

Modern Image Manager is een krachtige WordPress-plugin voor het beheren van media-items. Hiermee kun je eenvoudig afbeeldingen en bijlagen organiseren, metadata bijwerken en media importeren/exporteren via CSV.

## 📌 Functies

✅ **Media exporteren naar CSV** – Exporteer bestandsnamen, titels, alt-tags en beschrijvingen naar CSV.  
✅ **Media importeren via CSV** – Voeg nieuwe media-items toe of update bestaande met een CSV-bestand.  
✅ **Bestanden hernoemen** – Hernoem mediabestanden en update de gekoppelde metadata automatisch.  
✅ **Metagegevens bijwerken** – Bewerk titels, alt-tags en beschrijvingen in bulk.  
✅ **WordPress Post Koppeling** – Controleert of een afbeelding is gekoppeld aan een bericht of ACF-veld.  
✅ **AJAX-gebaseerde media beheer** – Snelle updates zonder pagina herladen.  

## 🚀 Installatie

### Automatische installatie
1. Ga naar het WordPress dashboard.
2. Navigeer naar **Plugins > Nieuwe Plugin**.
3. Zoek op _Modern Image Manager_ en klik op **Nu Installeren**.
4. Activeer de plugin.

### Handmatige installatie
1. Download de plugin of clone deze repository:
    ```bash
    git clone https://github.com/yourusername/modern-image-manager.git
    ```
2. Upload de map naar `/wp-content/plugins/`.
3. Activeer de plugin via het **Plugins** menu in WordPress.

## 🔧 Gebruik

### 📤 Media exporteren
1. Ga naar **Modern Image > Export Media**.
2. Selecteer welke media je wilt exporteren (alles, gekoppeld of niet-gekoppeld).
3. Klik op **Exporteren naar CSV** en download het bestand.

### 📥 Media importeren
1. Ga naar **Modern Image > Import Media**.
2. Upload je CSV-bestand met media-informatie.
3. Klik op **Importeren** om media te updaten of toe te voegen.

### ✏️ Bestanden hernoemen en metadata bewerken
- Hernoem bestanden rechtstreeks via de plugin-interface.
- Update titels, beschrijvingen en alt-tags van meerdere bestanden tegelijk.

## 📷 Screenshots
![Modern Image Manager](https://example.com/screenshot.png)

## 🛠️ Vereisten
- **WordPress**: 6.0 of hoger
- **PHP**: 7.4 of hoger
- **MySQL**: 5.6 of hoger

## 🛡️ Beveiliging
- Voorkomt directe toegang tot bestanden (`defined('ABSPATH')` check).
- AJAX-verzoeken worden verwerkt met **nonce-validatie**.
- WordPress' **sanitize- en escape-functies** worden gebruikt om XSS en SQL-injecties te voorkomen.

## 🎭 Vertalingen
Ondersteunt meertalige vertaling via **Loco Translate** en `.pot` bestanden.

## 🤝 Bijdragen
Wil je bijdragen? Volg deze stappen:
1. Fork deze repository.
2. Maak een nieuwe branch (`feature/nieuwe-functionaliteit`).
3. Commit je wijzigingen en push naar GitHub.
4. Start een pull request!

## 📜 Licentie
Dit project valt onder de **GPL-2.0** licentie.

---

🚀 **Modern Image Manager** – De slimste manier om je WordPress-media te beheren!
