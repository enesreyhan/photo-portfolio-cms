# Photo Portfolio CMS

**English** | [Türkçe](README.tr.md)

A multilingual portfolio website and admin panel for photographers, written in plain PHP with SQLite (MySQL optional). Installing it is as simple as copying one folder.

## Features

- **Portfolio:** Create your own categories (projects, series, books…); each work gets a visual story canvas, cover image, focal point, password-protected link and draft mode.
- **Homepage:** A curated slideshow or all works; 20+ ready-made themes and a live design editor.
- **Media library:** Bulk upload, folders, EXIF reading, automatic AVIF/WebP conversion, and a warning before deleting photos that are in use.
- **Multilingual:** 17 built-in languages for the site and the admin panel, language-specific URLs, automatic translation help.
- **Pages:** About, Contact (form + inbox), Blog, privacy notice and explicit consent texts (KVKK/GDPR-style).
- **More:** Sitemap, RSS/Atom, Schema.org, cookie consent, Google Analytics 4 statistics, maintenance mode, database backup download.

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

1. Upload the files to your domain's root folder (`public_html`).
2. Make sure PHP can write to that folder (`site.db`, `uploads/` and `processed_images/` are created there).
3. Open `https://yourdomain.com/admin/`.

### First run (setup wizard)

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
- **Themes:** pick a ready-made theme and customise it.
- **Languages & Translation:** add or turn off site languages; change page names and addresses under **Page Names & URLs**.

## Folder structure

| Folder | Contents |
| --- | --- |
| `admin/` | Admin panel |
| `includes/` | Shared PHP code, views (`views/`), language files |
| `api/` | Contact form endpoint |
| `assets/brand/` | Default symbol, favicon and portrait placeholder |
| `scripts/` | Command-line tools (image conversion, media sync, password reset) |
| `uploads/`, `processed_images/` | Uploaded and converted images (created automatically, not in the repository) |

The `includes/private/` folder is not part of the repository; you can use it for site-specific modules (such as old URL redirects). When it is missing, those features are simply off.

## Security notes

- `site.db`, `.env*` and backup files are blocked from web access via `.htaccess`; still, keep the database outside the web root if you can.
- The admin panel limits login attempts and uses CSRF protection.

## License

MIT — see [LICENSE](LICENSE). The fonts (JetBrains Mono, Space Grotesk, Zed) are distributed under their own open licenses (SIL OFL).
