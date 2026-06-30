=== Entries & Media Exporter by Naren Jadav ===
Contributors: nikkjadav
Donate link: https://narenjadav.com/
Tags: gravity forms, export, file upload, csv, zip
Requires at least: 5.8
Tested up to: 7.0
Requires PHP: 8.0
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

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

== Frequently Asked Questions ==

= Does this plugin support multi-file upload fields? =
Yes, all multiple-file uploads are extracted, parsed, and downloaded together.

= Does this work with remote storage providers? =
It supports local webserver storage paths natively and falls back to HTTP stream downloads for remotely hosted uploaded files.

== Screenshots ==

1. The main dashboard showing form select options, date ranges, and Danger Zone settings.
2. The custom SweetAlert2 toast notification following a successful file compilation.

== Changelog ==

= 1.0.0 =
* Initial release of refactored and modernized OOP code.

== Upgrade Notice ==

= 1.0.0 =
Initial release.
