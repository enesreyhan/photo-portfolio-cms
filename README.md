# Photo Portfolio CMS

**English** | [Türkçe](README.tr.md)

A multilingual portfolio website and admin panel for photographers, written in plain PHP with SQLite (MySQL optional). No build step, no framework: copy one folder to your server and open `/admin/`.

![Homepage with a full-screen slideshow](docs/screenshots/en/site-home.webp)

> All screenshots come from a real site built with this CMS: [enesreyhan.com](https://www.enesreyhan.com). The photographs in them are © Enes Reyhan and are **not** covered by the MIT license.

---

## Contents

- [The website](#the-website)
- [The admin panel](#the-admin-panel)
- [Features at a glance](#features-at-a-glance)
- [Requirements](#requirements)
- [Installation](#installation)
- [First settings](#first-settings)
- [Folder structure](#folder-structure)
- [Security notes](#security-notes)
- [License](#license)

---

## The website

### Portfolio lists

Each category (Projects, Series, Single images, or anything you create) gets its own list page. Visitors scroll through full-screen chapters; the small counter on the right shows where they are.

![Projects list page](docs/screenshots/en/site-projects.webp)

### Project page

A project opens with its title, place and years, an introduction text and the cover photo. Visitors can switch between the **exhibition flow** and a **contact sheet**, and between a dark and a light background.

![Project page opening](docs/screenshots/en/site-project.webp)

<table>
  <tr>
    <td width="50%"><img src="docs/screenshots/en/site-project-story.webp" alt="Exhibition flow: one large photo at a time"></td>
    <td width="50%"><img src="docs/screenshots/en/site-contact-sheet.webp" alt="Contact sheet: all photos of the project as a numbered grid"></td>
  </tr>
  <tr>
    <td><b>Exhibition flow:</b> photos one by one, with captions, chapters and diptychs.</td>
    <td><b>Contact sheet:</b> the whole project as a numbered grid.</td>
  </tr>
</table>

### About and contact

<table>
  <tr>
    <td width="50%"><img src="docs/screenshots/en/site-about.webp" alt="About page with biography and portrait"></td>
    <td width="50%"><img src="docs/screenshots/en/site-contact.webp" alt="Contact form"></td>
  </tr>
  <tr>
    <td><b>About:</b> biography, portrait, exhibitions and awards timeline.</td>
    <td><b>Contact:</b> a form whose messages land in the admin inbox (and your email).</td>
  </tr>
</table>

### On the phone

<table>
  <tr>
    <td align="center"><img src="docs/screenshots/en/site-mobile.webp" alt="Homepage on a phone" width="260"></td>
    <td align="center"><img src="docs/screenshots/en/site-mobile-project.webp" alt="Project page on a phone" width="260"></td>
  </tr>
</table>

Every page is responsive; the menu turns into a full-screen drawer on small screens.

---

## The admin panel

### Setup wizard

The first time you open `/admin/`, the wizard asks for the **setup language** (17 languages), then for the first administrator account. The admin panel opens in the language you chose.

<table>
  <tr>
    <td align="center"><img src="docs/screenshots/en/setup-language.webp" alt="Choose the setup language" width="360"></td>
    <td align="center"><img src="docs/screenshots/en/setup-account.webp" alt="Create the administrator account" width="360"></td>
  </tr>
  <tr>
    <td align="center">1. Choose the language</td>
    <td align="center">2. Create your account</td>
  </tr>
</table>

### Overview

The dashboard shows your latest works, visitor numbers (when Google Analytics is connected), unread messages and the status of the site's modules, with warnings when something needs attention.

![Admin overview](docs/screenshots/en/admin-dashboard.webp)

### Pages

Every page of the site in one list: fixed pages, list pages and each work, with its address, status and whether it is in the menu. The **Menu and preview** tab lets you reorder the menu by dragging.

![Pages](docs/screenshots/en/admin-pages.webp)

### Work editor

Title, short description, place and year for each language; an introduction text with a rich-text editor; cover photo with a focal point; address, category, password protection and old addresses in the side panel.

![Work editor](docs/screenshots/en/admin-editor.webp)

Below it is the **story canvas**: drag photos to reorder them, add chapters, text blocks and quotes, mark the cover, pair two photos as a diptych and write a caption for each image.

![Story canvas](docs/screenshots/en/admin-story-canvas.webp)

### Media library

Bulk upload by drag and drop, folders, search and filters (country, city, year, camera, aperture, shutter, ISO). Every upload is converted to AVIF and WebP automatically and its EXIF data is read. Before you delete a photo, the library tells you where it is used.

![Media library](docs/screenshots/en/admin-media.webp)

### Themes and live design editor

26 ready-made themes, previewed with your own photos. **Customize** opens the live design editor: colours, fonts, buttons, logo, menu, footer and every page type, with a desktop/phone preview.

<table>
  <tr>
    <td width="50%"><img src="docs/screenshots/en/admin-themes.webp" alt="Theme gallery"></td>
    <td width="50%"><img src="docs/screenshots/en/admin-customize.webp" alt="Live design editor"></td>
  </tr>
  <tr>
    <td><b>Themes:</b> grid, single photo, horizontal, story and list layouts.</td>
    <td><b>Design editor:</b> changes show up in the preview immediately.</td>
  </tr>
</table>

### Languages

Add a language with one click from 17 ready-made ones, publish or hide it, choose the default language and the admin panel language. A translation table and automatic translation help you fill in the content.

![Languages](docs/screenshots/en/admin-languages.webp)

### Site settings

Site title and description per language, site owner, contact email and phone, social media accounts, logo and favicon, URL management, privacy, integrations, account and backup.

![Site settings](docs/screenshots/en/admin-settings.webp)

---

## Features at a glance

| Area | What you get |
| --- | --- |
| **Portfolio** | Your own categories, story canvas, cover and focal point, password-protected links, drafts, old-address redirects |
| **Homepage** | Curated slideshow or all works, 26 themes, live design editor |
| **Media** | Bulk upload, folders, EXIF, automatic AVIF/WebP, filters, "in use" warning |
| **Languages** | 17 languages for site and panel, language-specific URLs, translation table, automatic translation |
| **Pages** | About, Contact (form + inbox), Blog, privacy notice and explicit consent texts |
| **SEO** | Sitemap, RSS/Atom, Schema.org, Open Graph, clean URLs |
| **Other** | Cookie consent, Google Analytics 4 statistics, maintenance mode, database backup download, login rate limiting |

---

## Requirements

- PHP **8.1+** (8.2+ recommended)
- PHP extensions: `pdo_sqlite`, `gd` (with AVIF support for AVIF output), `mbstring`, `json`
- Optional: `curl` and `openssl` (Google Analytics, automatic translation), `exif`
- Apache/LiteSpeed (via `.htaccess`) or PHP's built-in server (for development)

## Installation

### Try it locally

```bash
git clone https://github.com/enesreyhan/photo-portfolio-cms.git portfolio
cd portfolio
./start.sh            # or: php -S localhost:8000 router.php
```

Open `http://localhost:8000/admin/` in your browser.

### Install on a server (cPanel / shared hosting)

1. Download the latest release from the [Releases page](https://github.com/enesreyhan/photo-portfolio-cms/releases/latest) (**Source code (zip)**) and unzip it.
2. Upload the **contents** of the unzipped folder to your domain's root folder (`public_html`).
3. Make sure PHP can write to that folder (`site.db`, `uploads/` and `processed_images/` are created there).
4. Open `https://yourdomain.com/admin/`.

### First run

1. **Choose the setup language.** The admin panel opens in this language.
2. **Create the administrator account:** username, your name (shown in copyright and legal notices), email, password.
3. The panel opens. The site already has About and Contact pages and a menu. Create your portfolio categories (e.g. Projects, Series) under **Content › Pages › New › New list page**, then add your works.

The database (`site.db`) is created automatically on the first request; no separate install step is needed.

### Forgot your password?

```bash
php scripts/reset-password.php <username>
```

### Using MySQL (optional)

SQLite is the default. For MySQL, set these server environment variables: `DB_DRIVER=mysql`, `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS` (see `config.php`).

## First settings

- **Site Settings › General:** site title, description, site owner, email, phone, social media.
- **Site Settings › Logo:** upload your symbol, full logo and favicon.
- **Themes:** pick a ready-made theme and customize it.
- **Site Settings › Languages:** add or turn off site languages; change page names and addresses under **URL Management**.

## Folder structure

| Folder | Contents |
| --- | --- |
| `admin/` | Admin panel |
| `includes/` | Shared PHP code, views (`views/`), language files |
| `api/` | Contact form endpoint |
| `assets/brand/` | Default symbol, favicon and portrait placeholder |
| `scripts/` | Command-line tools (image conversion, media sync, password reset) |
| `docs/screenshots/` | The images in this README |
| `uploads/`, `processed_images/` | Uploaded and converted images (created automatically, not in the repository) |

The `includes/private/` folder is not part of the repository; you can use it for site-specific modules (such as old URL redirects). When it is missing, those features are simply off.

## Security notes

- `site.db`, `.env*` and backup files are blocked from web access via `.htaccess`; still, keep the database outside the web root if you can.
- The admin panel limits login attempts and uses CSRF protection. Password-protected works share the same attempt limit.
- Passwords for protected works are stored hashed, so the admin cannot show them again. If you forget one, set a new password.
- HTML in page and work content is cleaned before it is shown: scripts, event handlers (`onclick` and the like) and `javascript:` links are removed, and only YouTube and Vimeo embeds are kept. Embed codes from other services (Instagram, Spotify, Google Maps…) are dropped.
- Set the domains your site answers to, so a forged `Host` header cannot leak into links, the sitemap or emails. Use the `ALLOWED_HOSTS` environment variable (`ALLOWED_HOSTS=example.com,www.example.com`) or an `includes/private/hosts.php` file that returns an array (`<?php return ['www.example.com', 'example.com'];`). The first name is used when an unknown host arrives. If neither is set, every host is accepted.

## License

The code is MIT licensed, see [LICENSE](LICENSE). The fonts (JetBrains Mono, Space Grotesk, Zed) are distributed under their own open licenses (SIL OFL). The photographs in `docs/screenshots/` are © Enes Reyhan, all rights reserved.
