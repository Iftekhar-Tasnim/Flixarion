# Flixarion — Backend API

> Free BDIX streaming platform for Bangladesh. Aggregates content from BDIX FTP servers, enriches with TMDb metadata, and serves a clean REST API for the frontend.

**Stack:** Laravel 12 · PostgreSQL · Redis · Laravel Sanctum · Laravel Queues

---

## What This Repo Is

This is the **Laravel backend API only**. It handles:
- Content discovery and aggregation from 8 BDIX FTP sources
- Metadata enrichment via TMDb + OMDb
- User auth, library (watchlist/favorites/history)
- Admin panel API (sources, content, enrichment, users)
- CORS proxy for frontend BDIX directory scanning

The **frontend** (Next.js) and **admin panel** are separate repositories.

---

## Architecture

```
BDIX FTP Servers                    Flixarion Backend (this repo)
┌─────────────────┐    Client-side  ┌──────────────────────────────┐
│ DhakaFlix       │◄── browser ────►│ POST /sources/{id}/scan-results│
│ Dflix           │    scans        │ GET  /proxy?url=              │
│ RoarZone        │                 ├──────────────────────────────┤
│ FTPBD           │                 │ Shadow Table (pending)        │
│ CircleFTP       │                 │ → EnrichBatchJob              │
│ ICC FTP         │                 │ → TMDb / OMDb API             │
│ iHub            │                 │ → Contents Table              │
└─────────────────┘                 └──────────────────────────────┘
                                              ↑
                                    Frontend reads via REST API
```

Key insight: **The backend never scrapes BDIX directly when hosted on cloud** — the user's browser (already on BDIX) crawls and POSTs file lists. The backend enriches via TMDb (accessible from anywhere).

---

## Getting Started

### Requirements
- PHP 8.2+
- PostgreSQL 14+
- Redis
- Composer

### Setup

```bash
git clone https://github.com/your-username/flixarion.git
cd flixarion

composer install

cp .env.example .env
php artisan key:generate
```

### Configure `.env`

```env
APP_NAME=Flixarion
APP_URL=http://localhost:8000

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=flixarion
DB_USERNAME=postgres
DB_PASSWORD=secret

REDIS_HOST=127.0.0.1
REDIS_PORT=6379

QUEUE_CONNECTION=redis

TMDB_API_KEY=your_tmdb_api_key_here
OMDB_API_KEY=your_omdb_api_key_here

SANCTUM_STATEFUL_DOMAINS=localhost:3000
FRONTEND_URL=http://localhost:3000
```

### Run

```bash
php artisan migrate --seed    # creates tables + seeds 8 BDIX sources
php artisan serve             # API on http://localhost:8000

# In a separate terminal — run the queue worker (required for enrichment)
php artisan queue:work
```

---

## API Overview

Base URL: `http://localhost:8000/api`

All responses: `{ "data": ..., "meta": ... }` or `{ "message": "...", "errors": {} }`

### Public Endpoints (no auth)

| Method | Endpoint | Description |
|---|---|---|
| POST | `/auth/register` | Register |
| POST | `/auth/login` | Login → token |
| GET | `/contents` | Browse all content |
| GET | `/contents/search?q=` | Search |
| GET | `/contents/{id}` | Content detail + source links |
| GET | `/proxy?url=` | CORS proxy for BDIX URLs |
| GET | `/sources` | List all BDIX sources |
| GET | `/sources/{id}/ping` | Test FTP reachability |
| POST | `/sources/health-report` | Report Race Strategy results |
| POST | `/sources/{id}/scan-results` | Push crawled file list |

### Authenticated (Bearer token)

| Method | Endpoint | Description |
|---|---|---|
| GET | `/auth/me` | Current user |
| POST | `/auth/logout` | Logout |
| GET | `/user/library` | Full library |
| POST/DELETE | `/user/watchlist` | Manage watchlist |
| POST/DELETE | `/user/favorites` | Manage favorites |
| GET/POST | `/user/history` | Watch history |

### Admin (admin role required)

`/admin/dashboard`, `/admin/sources`, `/admin/contents`, `/admin/enrichment`, `/admin/review-queue`, `/admin/users`, `/admin/settings`

→ Full API docs: [`docs/api_reference.md`](docs/api_reference.md)

---

## Content Pipeline

```
1. Scan      → Browser crawls BDIX FTP → POST /sources/{id}/scan-results
                OR Admin triggers: POST /admin/sources/{id}/scan

2. Shadow    → Files saved to shadow_content_sources (status: pending)

3. Enrich    → EnrichBatchJob runs via queue:
               FilenameParser → TMDb search → confidence score
               ≥80%: published to contents table
               <80%: admin review queue

4. Serve     → Frontend reads GET /contents
```

---

## Scrapers

8 BDIX source scrapers in `app/Scrapers/`:

| Scraper | Method |
|---|---|
| `DflixScraper` | HTTP + HTML parsing |
| `DhakaFlixMovieScraper` | h5ai recursive directory walk |
| `DhakaFlixSeriesScraper` | h5ai recursive directory walk |
| `RoarZoneScraper` | Emby API (`api_key` from `source.config`) |
| `FtpbdScraper` | Emby API |
| `CircleFtpScraper` | Node.js REST API multi-endpoint probe |
| `IccFtpScraper` | Auto-detect: h5ai / Emby / Apache autoindex |
| `IhubScraper` | HTML portal scraping |

---

## Key Directories

```
app/
├── Http/Controllers/       # Public API controllers
│   └── Admin/              # Admin API controllers
├── Jobs/                   # ScanSourceJob, EnrichBatchJob
├── Models/                 # Eloquent models
├── Scrapers/               # BDIX source scrapers
├── Services/               # ContentEnricher, FilenameParser, TmdbService
└── Traits/                 # ApiResponse trait

docs/
├── api_reference.md        # Complete frontend API docs
├── frontend_scanner_plan.md# Client-side scanner implementation guide
├── BRD.md                  # Business requirements
├── SRS.md                  # Software requirements
├── progress.md             # Story completion tracker (66/89 done)
└── Flixarion.postman_collection.json
```

---

## Admin Controls

| Action | Endpoint |
|---|---|
| Test all source connections | `GET /admin/sources/test-all` |
| Scan all active sources | `POST /admin/sources/scan-all` |
| Pause enrichment worker | `POST /admin/enrichment/pause` |
| Resume enrichment worker | `POST /admin/enrichment/resume` |
| Retry all pending records | `POST /admin/enrichment/retry-pending` |
| Retry all unmatched records | `POST /admin/enrichment/retry-unmatched` |
| Enrichment status + counts | `GET /admin/enrichment` |

---

## Project Status

**Backend: 66/89 stories complete — production ready**

| Epic | Status |
|---|---|
| Auth & User Management | ✅ Complete |
| Content Browsing & Search | ✅ Complete |
| User Library | ✅ Complete |
| ISP Source Availability | ✅ Complete |
| Content Scanning (Phase 1 + 2) | ✅ Complete |
| Source Scrapers (8 sources) | ✅ Complete |
| Admin Panel API | ✅ Complete |
| CORS Proxy (for frontend scanner) | ✅ Complete |
| Frontend (separate repo) | 🔧 Next phase |
| Deployment | ⬜ Pending |

---

## Postman Collection

Import [`docs/Flixarion.postman_collection.json`](docs/Flixarion.postman_collection.json) for all pre-configured requests. Set `base_url` and `admin_token` variables in your environment.

---

## License

MIT — Solo portfolio project by [Iftekhar Tasnim](https://github.com/Iftekhar-Tasnim).  
Content is streamed directly from publicly accessible BDIX FTP servers — Flixarion does not host any media.
