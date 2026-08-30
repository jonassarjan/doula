# Doula Registry — Project Context

## Application Overview
An online **death doula registry** that allows people to find a certified/practicing death doula near them. Discovery is via both **text search** (country / city) and an **interactive world map** (with radius search via geodata).

## Tech Stack
- **Backend:** PHP 8.3
- **Database:** MySQL — `doula` (local), `doula_prod` (production)
- **Webserver:** Laragon (local dev), Apache 2.4 on Ubuntu (production)
- **Frontend:** Bootstrap 5.3, Leaflet 1.9 (map), Tom Select (dropdowns)

---

## Environments

### Local (Laragon)
- URL: `http://localhost/doula/`
- DB: `doula` on localhost:3306, user `doulaDB`
- Apache vhost must set: `SetEnv APP_BASE_URL /doula`

### Production
- URL: `https://deathdoulamap.com`
- Alias: `https://deathdoulamap.poolside.se`
- Server: DigitalOcean droplet at `46.101.109.74`
- DB: `doula_prod` on localhost, user `doula_prod_user`
- Web root: `/var/www/deathdoulamap`
- SSL: Let's Encrypt via certbot, auto-renews, cert at `/etc/letsencrypt/live/deathdoulamap.poolside.se/`
- Apache vhost configs: `/etc/apache2/sites-available/deathdoulamap.poolside.se.conf` (HTTP→HTTPS redirect) and `deathdoulamap.poolside.se-le-ssl.conf` (HTTPS)

---

## Configuration

### `BASE_URL` / database credentials
`admin/config.php` reads `APP_BASE_URL` from the environment (falls back to `''`).
`Database.php` reads `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS` from environment — or from `config.server.php` if that file exists (production only, not in git).

### `config.server.php` (production only, gitignored)
Lives at `/var/www/deathdoulamap/config.server.php`. Never commit this file.
```php
<?php
define('DB_HOST', 'localhost');
define('DB_NAME', 'doula_prod');
define('DB_USER', 'doula_prod_user');
define('DB_PASS', '...');
```

---

## Deployment

### First-time setup (already done)
```bash
git clone https://github.com/jonassarjan/doula.git /var/www/deathdoulamap
mkdir /var/www/deathdoulamap/uploads
chown -R www-data:www-data /var/www/deathdoulamap
mysql doula_prod < schema.sql
# create config.server.php manually on server
```

### Routine deploy
```bash
cd /var/www/deathdoulamap
git pull origin master
```

### Uploads
Photo uploads live in `/var/www/deathdoulamap/uploads/` — this directory is gitignored and managed on the server only. Do not commit uploads.

---

## Requirements

### Roles & Access
- **Public:** Search/browse doulas — no account required
- **Admin:** One admin user; adds and manages all doula listings via an admin GUI
- No doula self-registration, no client accounts

### Doula Profile Fields
| Field | Type | Notes |
|---|---|---|
| Name | text | |
| Bio | long text | |
| Profile photo | single image | file path stored, served from `/uploads/` |
| Certifications | free text | e.g. "Certified by INELDA 2022" |
| Years of experience | integer | nullable |
| Languages spoken | structured list | multiselect in admin (pivot table) |
| Categories | structured list | multiselect in admin (pivot table) |
| Email | varchar | nullable |
| Phone | varchar | nullable |
| Website / homepage | varchar | nullable |
| City | varchar | text-searchable |
| Country | varchar | text-searchable |
| Latitude | decimal(10,7) | for map + radius search |
| Longitude | decimal(10,7) | for map + radius search |
| Active | boolean | admin can toggle visibility |
| Pending | boolean | internal flag |

### Location & Search
- Single city per doula
- Text search by **name**, **country**, and/or **city**, filterable by **category**
- Geodata (lat/lng) stored per doula for:
  - Plotting on a **world map**
  - **"Nearest doula"** button uses browser geolocation (requires HTTPS)

### Contact
- Homepage/website link
- Email address
- Phone number
- No on-site messaging or booking system

### Out of scope (v1)
- Reviews & ratings
- Pricing
- Doula specialties
- Client accounts
- Multi-admin roles
- Virtual/remote service flag

---

## Database Schema

### `doulas`
```sql
id               BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
name             VARCHAR(255) NOT NULL
bio              TEXT
photo            VARCHAR(500)
certifications   TEXT
years_experience TINYINT UNSIGNED
email            VARCHAR(255)
phone            VARCHAR(50)
website          VARCHAR(500)
city             VARCHAR(255) NOT NULL
country          VARCHAR(255) NOT NULL
latitude         DECIMAL(10,7) NOT NULL
longitude        DECIMAL(10,7) NOT NULL
is_active        TINYINT(1) NOT NULL DEFAULT 1
is_pending       TINYINT(1) NOT NULL DEFAULT 0
created_at       TIMESTAMP
updated_at       TIMESTAMP
```

### `languages`
```sql
id    SMALLINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
name  VARCHAR(100) NOT NULL
code  CHAR(2) NOT NULL        -- ISO 639-1 e.g. "en"
```

### `categories`
```sql
id    TINYINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
name  VARCHAR(255) NOT NULL
```

### `doula_language` (pivot)
```sql
doula_id     BIGINT UNSIGNED NOT NULL  FK -> doulas.id
language_id  SMALLINT UNSIGNED NOT NULL  FK -> languages.id
PRIMARY KEY (doula_id, language_id)
```

### `doula_category` (pivot)
```sql
doula_id     BIGINT UNSIGNED NOT NULL  FK -> doulas.id
category_id  TINYINT UNSIGNED NOT NULL  FK -> categories.id
PRIMARY KEY (doula_id, category_id)
```

### `admins`
```sql
id         BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
email      VARCHAR(255) NOT NULL UNIQUE
password   VARCHAR(255) NOT NULL
created_at TIMESTAMP
updated_at TIMESTAMP
```

---

## Key Queries

### Radius search (MySQL)
```sql
SELECT *, ST_Distance_Sphere(
    POINT(longitude, latitude),
    POINT(:lng, :lat)
) AS distance_m
FROM doulas
WHERE is_active = 1
HAVING distance_m <= :radius_m
ORDER BY distance_m;
```

### City / country text search
```sql
SELECT * FROM doulas
WHERE is_active = 1
  AND (city LIKE :q OR country LIKE :q);
```
