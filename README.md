# Modern Image Manager

[![WordPress](https://img.shields.io/badge/WordPress-6.4%2B-blue.svg)](https://wordpress.org)
[![PHP](https://img.shields.io/badge/PHP-8.2%2B-green.svg)](https://www.php.net/)
[![License](https://img.shields.io/badge/license-GPL--2.0-orange.svg)](https://www.gnu.org/licenses/gpl-2.0.html)

**Modern Image Manager** is a powerful WordPress plugin for managing media items. Easily organize images and attachments, update metadata, and import/export media via CSV.

## 📌 Features

✅ **Export media to CSV** – Export filenames, titles, alt tags, and descriptions to a CSV file.  
✅ **Import media via CSV** – Add new media items or update existing ones using a CSV file.  
✅ **Rename files** – Rename media files and automatically update linked metadata.  
✅ **Edit metadata** – Bulk edit titles, alt tags, and descriptions.  
✅ **WordPress post linking** – Check if an image is linked to a post or ACF field.  
✅ **AJAX-based media management** – Perform quick updates without reloading the page.  

## 🚀 Installation

### Automatic Installation
1. Go to your **WordPress dashboard**.
2. Navigate to **Plugins > Add New**.
3. Search for _Modern Image Manager_ and click **Install Now**.
4. Activate the plugin.

### Manual Installation
1. Download the plugin files or clone this repository:
    ```bash
    git clone https://github.com/Yolol100/Wordpress.git
    ```
2. Upload the plugin folder to `/wp-content/plugins/`.
3. Activate the plugin via the **Plugins** menu in WordPress.

## 🔧 Usage

### 📤 Export Media
1. Go to **Modern Image > Export Media**.
2. Select which media to export (All, Linked, or Unlinked).
3. Click **Export to CSV** to download the file.

### 📥 Import Media
1. Go to **Modern Image > Import Media**.
2. Upload your CSV file with media data.
3. Click **Import** to update or add media.

### ✏️ Rename Files & Edit Metadata
- Rename files directly from the plugin interface.
- Bulk update titles, descriptions, and alt tags.

## 🛠️ Requirements
- **WordPress**: 6.0 or higher
- **PHP**: 8.2 or higher
- **MySQL**: 5.6 or higher

## 🛡️ Security
- Prevents direct file access (`defined('ABSPATH')` check).
- AJAX requests are processed with **nonce validation**.
- Uses WordPress' **sanitize and escape functions** to prevent XSS and SQL injections.

## 🎭 Translations
Supports multilingual translation via **Loco Translate** and `.pot` files.

## 🤝 Contributing
Want to contribute? Follow these steps:
1. Fork this repository.
2. Create a new branch (`feature/new-functionality`).
3. Commit your changes and push to GitHub.
4. Open a pull request!

## 📜 License
This project is licensed under **GPL-2.0**.