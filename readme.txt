=== Entries & Media Exporter by Naren Jadav ===
Contributors: narenjadav
Tags: gravity forms, export, file upload, csv, zip
Requires at least: 5.8
Tested up to: 7.0
Requires PHP: 8.0
Stable tag: 1.0.0
License: GPLv2 or later

Export Gravity Forms entries to CSV alongside all uploaded files packaged inside a single ZIP archive.

== Description ==

Gravity Forms default exporter outputs file upload fields as absolute URLs inside CSV reports. **Entries & Media Exporter by Naren Jadav** enhances this behavior by compiling all actual uploaded media files, images, documents, and multiple-file uploads into single ZIP packages downloadable directly in your web browser.

=== Key Features ===

* **Downloadable ZIP Packages:** Creates archives containing both an entries CSV spreadsheet and a dedicated `files/` folder storing all media.
* **Automatic De-duplication:** Automatically appends unique numbering sequences to files that share matching filenames.
* **Precise Date Filters:** Target specific windows of entries to download or delete.
* **Database & Storage Purges:** Includes a structured "Danger Zone" module to delete database records and associated media files simultaneously, saving server space.
* **Development Seeder:** Instantly generate mock submissions containing real media files to test configurations (enabled under WP_DEBUG).

== Installation ==

1. Upload the `entries-media-exporter-nj` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Access the controls through Gravity Forms -> Media Exporter (or Tools -> Media Exporter if active).

== Changelog ==

= 1.0.0 =
* Initial release of refactored and modernized OOP code.
