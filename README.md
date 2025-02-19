## Modern Image Manager

**Modern Image Manager** is a WordPress plugin that allows users to easily manage media items, import and export media data, and update metadata. It provides a user-friendly interface to work with attachments and images, with features like renaming files, exporting media information to CSV, and importing media via CSV files.

### Features

- **Export Media to CSV**: Export media item information such as filename, title, caption, description, alt-tag, and more.
- **Import Media via CSV**: Import media items and update existing attachments based on a CSV file.
- **Rename Files**: Rename media files and update the associated metadata and post information.
- **Edit Metadata**: Update the title, caption, description, and alt-tag of media items.

### Installation

#### Manual Installation

1. Download the plugin files or clone this repository:
    ```bash
    git clone https://github.com/yourusername/modern-image-manager.git
    ```

2. Upload the plugin folder to the `/wp-content/plugins/` directory.
3. Activate the plugin through the **Plugins** menu in WordPress.

### Requirements

- WordPress 5.0 or higher
- PHP 7.4 or higher
- MySQL 5.6 or higher

### Usage

Once installed and activated, the plugin adds a new menu item called **Modern Image** in the WordPress dashboard. From here, you can access the following features:

#### Export Media

1. Navigate to the **Modern Image** menu in the dashboard.
2. Click on **Export Media**.
3. Select the export option (All, Linked, or Unlinked).
4. Click **Export to CSV** to download the media data in a CSV file.

#### Import Media

1. Go to the **Modern Image** menu and select **Import Media**.
2. Upload your CSV file containing the media data.
3. The plugin will update existing media items or create new ones based on the CSV data.

#### Renaming Files

- When importing or editing media, you can also rename files directly through the plugin.
- The new filenames will be applied to both the media file and its corresponding post metadata.

### Translation

The plugin supports translation into multiple languages. To translate the plugin to your preferred language, use a translation tool like **Loco Translate**. Simply load the `.pot` file, and you can begin translating the strings used in the plugin.

### Support

If you encounter any issues or need help with the plugin, feel free to reach out to the plugin's support team.

### License

This plugin is licensed under the **GPL-2.0** license. You can freely use, modify, and distribute the plugin according to the terms of the GPL.

### Contributing

Contributions to the plugin are welcome! If you'd like to contribute, feel free to submit pull requests or suggest improvements.

### Changelog

#### Version 1.0.0

- Initial release of **Modern Image Manager** plugin.
