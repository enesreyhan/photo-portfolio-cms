# Fotoğraf Portfolyo CMS

[English](README.md) | **Türkçe**

Fotoğrafçılar için çok dilli, veritabanı olarak SQLite (isteğe bağlı MySQL) kullanan, kurulumu tek klasör kopyalamak kadar basit bir PHP portfolyo sitesi ve yönetim paneli.

## Özellikler

- **Portfolyo:** Kendi kategorilerinizi (projeler, seriler, kitaplar…) oluşturun; her çalışma için görsel hikâye tuvali, kapak, odak noktası, şifreli bağlantı, taslak.
- **Ana sayfa:** Slayt seçkisi veya tüm çalışmalar; 20+ hazır tema ve canlı tasarım editörü.
- **Medya kütüphanesi:** Toplu yükleme, klasörler, EXIF okuma, otomatik AVIF/WebP dönüştürme, kullanım uyarısı.
- **Çok dilli:** Site ve panel için 17 hazır dil, dile göre URL'ler, otomatik çeviri yardımı.
- **Sayfalar:** Hakkımda, İletişim (form + mesaj kutusu), Blog, KVKK aydınlatma ve açık rıza metinleri.
- **Diğer:** Sitemap, RSS/Atom, Schema.org, çerez onayı, Google Analytics 4 istatistikleri, bakım modu, yedek indirme.

## Gereksinimler

- PHP **8.1+** (8.2+ önerilir)
- PHP eklentileri: `pdo_sqlite`, `gd` (AVIF için GD'nin AVIF desteği), `mbstring`, `json`
- İsteğe bağlı: `curl` ve `openssl` (Google Analytics, otomatik çeviri), `exif`
- Apache/LiteSpeed (`.htaccess` ile) veya PHP'nin yerleşik sunucusu (geliştirme için)

## Kurulum

### Yerelde denemek

```bash
git clone https://github.com/enesreyhan/photo-portfolio-cms.git portfolyo
cd portfolyo
./start.sh            # ya da: php -S localhost:8000 router.php
```

Tarayıcıda `http://localhost:8000/admin/` adresini açın.

### Sunucuya kurmak (cPanel / paylaşımlı hosting)

1. Dosyaları alan adınızın kök klasörüne (`public_html`) yükleyin.
2. PHP'nin bu klasöre yazabildiğinden emin olun (`site.db`, `uploads/`, `processed_images/` burada oluşur).
3. `https://alanadiniz.com/admin/` adresini açın.

### İlk açılış (kurulum sihirbazı)

1. **Kurulum dilini seçin.** Yönetim paneli bu dilde açılır.
2. **Yönetici hesabını oluşturun:** kullanıcı adı, adınız (telif ve künye metinlerinde görünür), e-posta, parola.
3. Panel açılır. Sitede Hakkımda ve İletişim sayfaları ile menü hazırdır; portfolyo kategorilerinizi (ör. Projeler, Seriler) **İçerik › Sayfalar › Yeni › Yeni liste sayfası** ile ekleyin; ardından çalışmalarınızı yükleyin.

Veritabanı (`site.db`) ilk istekte otomatik oluşturulur; ayrıca bir kurulum adımı gerekmez.

### Parolayı unuttuysanız

```bash
php scripts/reset-password.php <kullanici_adi>
```

### MySQL kullanmak (isteğe bağlı)

Varsayılan SQLite'tır. MySQL için sunucu ortam değişkenlerini tanımlayın: `DB_DRIVER=mysql`, `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS` (bkz. `config.php`).

## İlk ayarlar

- **Site Ayarları › Genel:** site başlığı, açıklama, site sahibi, e-posta, telefon, sosyal medya.
- **Site Ayarları › Logo:** amblem, tam logo ve favicon yükleyin.
- **Temalar:** hazır temalardan birini seçip özelleştirin.
- **Ayarlar › Diller:** site dillerini ekleyin/kapatın; **URL Yönetimi**'nden sayfa adlarını ve adreslerini değiştirin.

## Klasör yapısı

| Klasör | İçerik |
| --- | --- |
| `admin/` | Yönetim paneli |
| `includes/` | Ortak PHP kodu, görünümler (`views/`), dil dosyaları |
| `api/` | İletişim formu uç noktası |
| `assets/brand/` | Varsayılan amblem, favicon ve portre yer tutucusu |
| `scripts/` | Komut satırı araçları (görsel dönüştürme, medya eşitleme, parola sıfırlama) |
| `uploads/`, `processed_images/` | Yüklenen ve dönüştürülen görseller (otomatik oluşur, depoda yok) |

`includes/private/` klasörü depoda yer almaz; siteye özel modüller (eski URL yönlendirmeleri gibi) için kullanılabilir. Bu klasör yoksa ilgili özellikler kapalıdır.

## Güvenlik notları

- `site.db`, `.env*` ve yedek dosyaları `.htaccess` ile web'den erişime kapalıdır; yine de veritabanını mümkünse web kökü dışında tutun.
- Yönetim paneli giriş denemelerini sınırlar ve CSRF koruması kullanır.

## Lisans

MIT — bkz. [LICENSE](LICENSE). Yazı tipleri (JetBrains Mono, Space Grotesk, Zed) kendi açık lisanslarıyla (SIL OFL) dağıtılır.
