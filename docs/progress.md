# Flixarion — Jira Story Progress Tracker

**Last Updated**: 2026-02-24

| Status | Meaning |
|--------|---------|
| ✅ | Done & Tested |
| 🔧 | In Progress |
| ⬜ | Not Started |

---

## Epic: Authentication & User Management — 4/4 ✅

| # | Story | Priority | Status |
|---|-------|----------|--------|
| 1 | Register with name, email, password → Sanctum token | Critical | ✅ |
| 2 | Login with email/password → Sanctum token | Critical | ✅ |
| 3 | Logout → revoke Sanctum token | High | ✅ |
| 4 | GET `/me` → user profile | High | ✅ |

---

## Epic: Content Browsing & Search — 0/7

| # | Story | Priority | Status |
|---|-------|----------|--------|
| 5 | Paginated content list (20/page) | Critical | ⬜ |
| 6 | Filter by type, genre, year | High | ⬜ |
| 7 | Search by title + alternative titles | Critical | ⬜ |
| 8 | Trending, popular, recently added endpoints | High | ⬜ |
| 9 | Content detail with metadata + sources | Critical | ⬜ |
| 10 | Filter by user's accessible sources | Moderate | ⬜ |
| 11 | robots.txt blocks /play/ and /source/ | High | ⬜ |

---

## Epic: Video Streaming & Playback — 0/2

| # | Story | Priority | Status |
|---|-------|----------|--------|
| 12 | Silent re-scan on 404 errors | High | ⬜ |
| 13 | Auto-select best quality reachable source | Critical | ⬜ |

---

## Epic: User Library — 0/6

| # | Story | Priority | Status |
|---|-------|----------|--------|
| 14 | Add/remove watchlist | Critical | ⬜ |
| 15 | Add/remove favorites | Critical | ⬜ |
| 16 | Trigger-only watch history | Critical | ⬜ |
| 17 | Cache last 10 watched items | High | ⬜ |
| 18 | View full watch history | High | ⬜ |
| 19 | Rate & review (Post-MVP) | Low | ⬜ |

---

## Epic: ISP Source Availability — 0/2

| # | Story | Priority | Status |
|---|-------|----------|--------|
| 20 | Anonymous health reports endpoint | High | ⬜ |
| 21 | Aggregate crowdsourced health | High | ⬜ |

---

## Epic: Content Scanning — Phase 1 (Collector) — 0/9

| # | Story | Priority | Status |
|---|-------|----------|--------|
| 22 | Auto-scan all sources every 6h | Critical | ⬜ |
| 23 | Two-phase scanning architecture | Critical | ⬜ |
| 24 | Shadow table for Phase 1 crawl | Critical | ⬜ |
| 25 | Only index valid video extensions | Critical | ⬜ |
| 26 | Auto-detect character encoding | High | ⬜ |
| 27 | Discover & link subtitle files | High | ⬜ |
| 28 | Detect multi-part movies | High | ⬜ |
| 29 | Log scan results | High | ⬜ |
| 30 | Admin manual trigger scan | High | ⬜ |

---

## Epic: Content Scanning — Phase 2 (Enricher) — 0/10

| # | Story | Priority | Status |
|---|-------|----------|--------|
| 31 | Enricher background worker | Critical | ⬜ |
| 32 | Normalize filenames (PTN parser) | Critical | ⬜ |
| 33 | Fuzzy match with confidence scoring | High | ⬜ |
| 34 | TMDb ID dedup anchor | Critical | ⬜ |
| 35 | Enrich with TMDb/OMDb metadata | Critical | ⬜ |
| 36 | TV series hierarchy (Series→Season→Episode) | Critical | ⬜ |
| 37 | TMDb rate-limit with backoff | Critical | ⬜ |
| 38 | Priority-based enrichment (newest first) | Moderate | ⬜ |
| 39 | Re-verify early-release content | Low | ⬜ |
| 40 | Auto-prune dead links (30+ days) | Moderate | ⬜ |

---

## Epic: Source Scrapers — 0/8

| # | Story | Priority | Status |
|---|-------|----------|--------|
| 41 | BaseScraperInterface | Critical | ⬜ |
| 42 | Dflix scraper (HTTP + HTML) | Critical | ⬜ |
| 43 | DhakaFlix Movie scraper (JSON API) | Critical | ⬜ |
| 44 | DhakaFlix Series scraper (JSON API) | Critical | ⬜ |
| 45 | RoarZone scraper (Emby API) | Critical | ⬜ |
| 46 | FTPBD scraper (Emby API) | Critical | ⬜ |
| 47 | CircleFTP scraper (REST API) | Critical | ⬜ |
| 48 | ICC FTP scraper (AJAX multi-step) | High | ⬜ |

---

## Epic: Admin Panel API — 0/12

| # | Story | Priority | Status |
|---|-------|----------|--------|
| 49 | Dashboard stats endpoint | High | ⬜ |
| 50 | CRUD sources + connection test | Critical | ⬜ |
| 51 | View scan logs per source | High | ⬜ |
| 52 | View/search/filter/delete content | Critical | ⬜ |
| 53 | Force metadata re-sync | High | ⬜ |
| 54 | Mark content as featured/trending | Moderate | ⬜ |
| 55 | Review queue (approve/correct/reject) | High | ⬜ |
| 56 | Crowdsourced health dashboard + ISP breakdown | High | ⬜ |
| 57 | View/control enrichment worker | Moderate | ⬜ |
| 58 | User management (list, ban/unban, reset) | High | ⬜ |
| 59 | System settings CRUD | High | ⬜ |
| 60 | Analytics endpoint (Post-MVP) | Low | ⬜ |

---

## Epic: Deployment & Infrastructure — 0/5

| # | Story | Priority | Status |
|---|-------|----------|--------|
| 61 | PHP-FPM + Nginx deployment | Critical | ⬜ |
| 62 | PostgreSQL + Redis provisioned | Critical | ⬜ |
| 63 | Supervisor for queue workers | High | ⬜ |
| 64 | Laravel Scheduler via cron | High | ⬜ |
| 65 | Logging + error tracking | High | ⬜ |

---

## Epic: Non-Functional — Performance — 0/5

| # | Story | Priority | Status |
|---|-------|----------|--------|
| 66 | API response times <200ms cached / <500ms uncached | High | ⬜ |
| 67 | DB queries <100ms with indexing | High | ⬜ |
| 68 | 1,000+ concurrent users via Redis | High | ⬜ |
| 69 | Redis cache TTLs per data type | High | ⬜ |
| 70 | 10K+ files enriched in ~1 hour | Moderate | ⬜ |

---

## Epic: Non-Functional — Security — 3/5 ✅

| # | Story | Priority | Status |
|---|-------|----------|--------|
| 71 | Admin middleware (role check) | Critical | ✅ |
| 72 | Rate limiting (60 req/min) | High | ✅ |
| 73 | CORS restricted to frontend/admin origins | High | ✅ |
| 74 | API keys in .env only | Critical | ⬜ |
| 75 | Signed URLs for authenticated FTP sources | High | ⬜ |

---

## Epic: Non-Functional — Reliability — 0/3

| # | Story | Priority | Status |
|---|-------|----------|--------|
| 76 | Graceful BDIX downtime handling | Critical | ⬜ |
| 77 | Retry failed scans (3x, exp backoff) | High | ⬜ |
| 78 | Crowdsourced health score consensus | High | ⬜ |

---

## Epic: Non-Functional — Data Integrity — 1/1 ✅

| # | Story | Priority | Status |
|---|-------|----------|--------|
| 79 | DB constraints + versioned migrations | Critical | ✅ |

---

## Epic: Non-Functional — Maintainability — 2/2 ✅

| # | Story | Priority | Status |
|---|-------|----------|--------|
| 80 | All env values in .env | Critical | ✅ |
| 81 | Consistent JSON API response format | High | ✅ |

---

## Summary

| Status | Count |
|--------|-------|
| ✅ Done | **10** |
| ⬜ Not Started | **71** |
| **Total** | **81** |
