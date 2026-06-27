# GF Media Exporter

[![WordPress Version](https://img.shields.io/badge/WordPress-5.8+-blue.svg)](https://wordpress.org)
[![PHP Version](https://img.shields.io/badge/PHP-8.0+-green.svg)](https://php.net)
[![Gravity Forms](https://img.shields.io/badge/Gravity%20Forms-2.5+-orange.svg)](https://gravityforms.com)
[![License](https://img.shields.io/badge/License-GPLv2%20or%20later-lightgrey.svg)](LICENSE.txt)

A refactored, modernized, and object-oriented companion plugin for **Gravity Forms** that exports form entries to standard CSV spreadsheets while packaging all uploaded media files, images, documents, and multiple-file uploads into a structured, downloadable ZIP archive.

---

## 🌟 Key Features

* **📦 Complete ZIP Archiving:** Packages form submissions alongside actual uploaded files. The exported ZIP contains an `entries.csv` summary sheet and a `files/` folder storing the corresponding media assets.
* **⚡ Single-Page AJAX Dashboard:** Load forms, seed mock entries, and purge files completely asynchronously. The browser URL remains clean (`/wp-admin/admin.php?page=gfme-exporter`) at all times.
* **🛡️ Traversing & Path Guards:** Enforces strict relative path resolution, matching upload directory boundaries to prevent directory traversal vulnerabilities.
* **📁 Smart De-duplication:** Automatically identifies matching filenames and appends incremental numbering suffixes (`-1`, `-2`, etc.) to prevent data loss in the ZIP compiler.
* **📅 Date Window Filtering:** Limit ZIP compile packages or database purges to entries submitted within specific calendar dates.
* **❌ Danger Zone (Storage Purges):** Clean up database submission tables and purge associated physical media files from the server simultaneously.
* **🛠️ Developer Seeder:** Generate mock entry records containing random text, emails, dates, and actual physical mock attachments (`.txt` and `.png` files) inside your uploads directory to verify configurations (visible only under `WP_DEBUG`).
* **👥 Modern UI/UX:** Styled using matching WordPress admin designs, featuring a Select2 searchable dropdown menu, Flatpickr calendars with accessibility overrides, custom disabled date hover tooltips, and SweetAlert2 toast alerts.

---

## 🛠️ System Requirements

* **WordPress:** 5.8 or higher (Tested up to `7.0`)
* **PHP:** 8.0 or higher
* **Gravity Forms:** 2.5 or higher
* **PHP Extensions:** `ZipArchive` (required for compiler packaging)

---

## 🚀 Installation & Setup

1. Copy the `gf-media-exporter` folder into your `/wp-content/plugins/` directory.
2. Log in to your WordPress dashboard and navigate to **Plugins** -> **Installed Plugins**.
3. Locate **GF Media Exporter** and click **Activate**.
4. Access the plugin dashboard via **Forms** -> **Media Exporter** (or fallback under **Tools** -> **Media Exporter**).

---

## 📐 Architecture & Structural Overview

The plugin follows clean **Object-Oriented Programming (OOP)** practices, separation of concerns, and WordPress Coding Standards:

```
gf-media-exporter/
├── assets/
│   ├── css/
│   │   ├── admin.css           # Premium layout overrides, tooltips, & Select2 styling
│   │   ├── flatpickr.min.css   # Self-hosted Flatpickr calendar style
│   │   ├── select2.min.css     # Self-hosted Select2 selector style
│   │   └── sweetalert2.min.css # Self-hosted SweetAlert2 dialog style
│   └── js/
│       ├── admin.js            # AJAX requests engine & cookie download tracker
│       ├── flatpickr.min.js    # Self-hosted Flatpickr calendar logic
│       ├── select2.min.js      # Self-hosted Select2 searchable dropdown logic
│       └── sweetalert2.all.min.js # Self-hosted SweetAlert2 alerts logic
├── includes/
│   ├── class-admin.php         # Menu registers, AJAX endpoints, & enqueuing
│   ├── class-dependencies.php  # Verifies core PHP version, Gravity Forms, & ZipArchive
│   ├── class-export.php        # Compiles CSV headers & constructs ZIP archive streams
│   ├── class-gravityforms.php  # Queries entry tables & handles developer seeding
│   ├── class-helpers.php       # Path traversal guards & URL path resolvers
│   ├── class-loader.php        # PSR-4 Autoloader mapping class files
│   ├── class-logger.php        # Conditional debug logger wrapper
│   ├── class-notices.php       # Admin notices framework
│   └── class-plugin.php        # Main orchestrator singleton container
├── languages/
│   └── gf-media-exporter.pot   # Translation catalog
├── templates/
│   ├── admin-page.php          # Skeleton layout and selector dropdown structure
│   └── form-details.php        # Dynamic card forms loaded asynchronously via AJAX
├── readme.txt                  # Official WordPress.org directory documentation
├── uninstall.php               # Purges data cleanly on plugin deletion
└── gf-media-exporter.php       # Main plugin bootstrap loader file
```

---

## 🛡️ Security & Privacy Compliance

* **100% Self-Hosted:** Enqueues all stylesheet overrides and script bundles locally, completely omitting external CDN requests. This respects strict privacy policies and allows the plugin to run fully offline.
* **Safe Output Escaping:** Restricts formatting outputs using native core handlers (`esc_html`, `esc_attr`, `esc_url`, `wp_kses`, and `absint`).
* **Input Sanitization:** Sanitzes all dynamic incoming requests using text filters (`sanitize_text_field` and `sanitize_key`).
* **Nonces & Capabilities:** Blocks administrative access using key checks (`current_user_can('manage_options')`) paired with custom cryptographic tokens (`check_ajax_referer`).

---

## 📝 License

This plugin is licensed under the GPLv2 or later. Feel free to modify, share, and distribute.
