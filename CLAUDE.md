# Doula Registry — Project Context

## Application Overview
An online **death doula registry** that allows people to find a certified/practicing death doula near them. Discovery is via both **text search** (country / city) and an **interactive world map** (with radius search via geodata).

## Tech Stack
- **Backend:** PHP
- **Database:** MySQL (`doula` database on localhost:3306, user: `doulaDB`)
- **Webserver:** Laragon (local dev)
- **PHP version:** 8.3

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
| Profile photo | single image | file path / URL |
| Certifications | free text | e.g. "Certified by INELDA 2022" |
| Years of experience | integer | nullable |
| Languages spoken | structured list | multiselect in admin (pivot table) |
| Email | varchar | nullable |
| Phone | varchar | nullable |
| Website / homepage | varchar | nullable |
| City | varchar | text-searchable |
| Country | varchar | text-searchable |
| Latitude | decimal(10,7) | for map + radius search |
| Longitude | decimal(10,7) | for map + radius search |
| Active | boolean | admin can toggle visibility |

### Location & Search
- Single city per doula
- Text search by **country** and/or **city**
- Geodata (lat/lng) stored per doula for:
  - Plotting on a **world map**
  - **Radius search** (e.g. within 50 km) via `ST_Distance_Sphere`

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
created_at       TIMESTAMP
updated_at       TIMESTAMP
```

### `languages`
```sql
id    SMALLINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
name  VARCHAR(100) NOT NULL   -- e.g. "English"
code  CHAR(2) NOT NULL        -- ISO 639-1 e.g. "en"
```

### `doula_language` (pivot)
```sql
doula_id     BIGINT UNSIGNED NOT NULL  FK -> doulas.id
language_id  SMALLINT UNSIGNED NOT NULL  FK -> languages.id
PRIMARY KEY (doula_id, language_id)
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
