# Flixarion — Jira Story Tables

---

## Epic: Authentication & User Management

| # | Story | Priority | SRS Ref | Acceptance Criteria | Labels |
|---|-------|----------|---------|-------------------|--------|
| 1 | As a guest user, I want to register with my name, email, and password so that I can access personalized features | Critical | FR-AUTH-01, NFR-SEC-09 | Given a guest user, when they submit valid name/email/password via FormRequest, then an account is created and Sanctum token is returned. Email must be unique (422 on duplicate). Password is hashed with bcrypt (cost 10+). | auth, backend |
| 2 | As a guest user, I want to log in with my email and password so that I can receive a Sanctum token | Critical | FR-AUTH-02, NFR-SEC-02 | Given valid credentials, when submitted, then a Sanctum API token is returned (non-expiring until logout) | auth, backend |
| 3 | As a registered user, I want to log out so that my session is securely terminated | High | FR-AUTH-04 | Given a logged-in user, when they logout, then the Sanctum token is revoked and cannot be reused | auth, backend |
| 4 | As a registered user, I want to retrieve my profile via `/me` so that I can see my account details | High | FR-AUTH-05 | Given a valid Sanctum token, when GET /api/auth/me is called, then the user's profile data is returned | auth, backend |



## Epic: Content Browsing & Search

| # | Story | Priority | SRS Ref | Acceptance Criteria | Labels |
|---|-------|----------|---------|-------------------|--------|
| 5 | As a user, I want to browse a paginated list of movies and series so that I can discover content | Critical | FR-BROWSE-01 | Given a browse request, when GET /api/contents is called, then paginated results with 20 items/page are returned using cursor/offset pagination. Response follows standard format: `{data, meta}` | browse, backend |
| 6 | As a user, I want to filter content by type, genre, and year so that I can narrow down results | High | FR-BROWSE-02 | Given filter params, when applied, then only matching content is returned | browse, backend |
| 7 | As a user, I want to search for content by title across TMDb and alternative titles so that I can find movies by any known name | Critical | FR-BROWSE-03, FR-BROWSE-04, FR-BROWSE-05, FR-BROWSE-06 | Given a search query, then both TMDb /movie/search and /tv/search are queried in parallel, alternative titles are also matched (e.g., "Hobbs and Shaw"), results are merged with Movie/TV Series badges | browse, search, backend |
| 8 | As a user, I want to see trending, popular, and recently added content so that I can find what's hot | High | FR-BROWSE-07, FR-BROWSE-08, FR-BROWSE-09 | Given GET /api/contents with `trending`, `popular`, or `recent` param, then content is sorted by watch count, rating, or created_at respectively | browse, backend |
| 9 | As a user, I want to view full content details including metadata and source links so that I can decide what to watch | Critical | FR-BROWSE-10, FR-BROWSE-11, FR-BROWSE-12 | Given a content ID, then all metadata (poster, backdrop, cast, genres, rating, runtime, trailer) is returned. For series: includes seasons with episode listings. Includes all source links with quality, file size, codec | browse, backend |
| 10 | As a user, I want to filter content by my reachable sources so that I only see what I can actually watch | Moderate | FR-BROWSE-13 | Given `?sources=1,3,7` with reachable source IDs from Race Strategy, then only content with active source_links on those specific sources is returned. Homepage is personalized per user's ISP connectivity | browse, backend |
| 11 | As the system, I want robots.txt to block /play/ and /source/ routes so that BDIX IPs are not indexed by search engines | High | FR-BROWSE-15, NFR-SEC-12 | Given a search engine crawler, then /play/ and /source/ routes return disallow in robots.txt | browse, security, seo |

---

## Epic: Video Streaming & Playback

| # | Story | Priority | SRS Ref | Acceptance Criteria | Labels |
|---|-------|----------|---------|-------------------|--------|
| 12 | As the system, I want to silently re-scan sources on 404 errors so that broken links are auto-corrected or marked | High | FR-PLAY-15, FR-PLAY-16, FR-PLAY-17, NFR-REL-07 | Given a 404 from a source during playback, then a background re-scan is triggered. If file found at new path, database is updated. If not found, source link is marked "broken" | playback, backend |
| 13 | As a user, I want the system to auto-select the best quality reachable source so that I get the best playback experience | Critical | FR-PLAY-11 | Given multiple sources, then system auto-selects based on reachability first, then quality ranking (4K > 1080p > 720p > 480p) | playback, backend |

---

## Epic: User Library

| # | Story | Priority | SRS Ref | Acceptance Criteria | Labels |
|---|-------|----------|---------|-------------------|--------|
| 14 | As a registered user, I want to add or remove content from my watchlist so that I can save content to watch later | Critical | FR-LIB-01 | Given a registered user, when they toggle watchlist, then content is added/removed via POST/DELETE /api/user/watchlist | library, backend |
| 15 | As a registered user, I want to add or remove content from my favorites so that I can bookmark content I love | Critical | FR-LIB-02 | Given a registered user, when they toggle favorites, then content is added/removed via POST/DELETE /api/user/favorites | library, backend |
| 16 | As a registered user, I want a single history entry recorded when I click Play so that my watch history is tracked without overhead | Critical | FR-LIB-05, FR-LIB-06 | Given a user clicks "Play", then exactly one history entry is created with content_id and timestamp only — no playback position or duration tracked | library, backend |
| 17 | As a registered user, I want my last 10 watched items cached so that Recently Watched loads instantly | High | FR-LIB-07 | Given a user's history, then the last 10 items are cached in a JSON column for fast "Recently Watched" display without JOIN queries | library, backend |
| 18 | As a registered user, I want to view my complete watch history so that I can revisit previously watched content | High | FR-LIB-10 | Given GET /api/user/history, then the full chronological history is returned | library, backend |
| 19 | As a registered user, I want to rate and review content so that I can share my opinions (Post-MVP) | Low | FR-LIB-11 | Given a registered user, then they can rate (1-10) and write a review — Post-MVP | library, backend |

---

## Epic: ISP Source Availability (Race Strategy)

| # | Story | Priority | SRS Ref | Acceptance Criteria | Labels |
|---|-------|----------|---------|-------------------|--------|
| 20 | As the frontend, I want to send anonymous health reports to the backend so that source reachability data is crowdsourced without exposing user IPs | High | FR-ISP-06, FR-ISP-07, NFR-SEC-13 | Given completed Race Strategy on frontend, then browser sends POST /api/sources/health-report with ISP name and source statuses — never full IP addresses | isp, backend, privacy |
| 21 | As the system, I want to aggregate crowdsourced health reports so that source availability is determined by consensus | High | FR-ISP-08 | Given multiple user health reports, then backend aggregates them to build consensus source health. Reports older than 30 days are pruned | isp, backend |

---

## Epic: Content Scanning — Phase 1 (Collector)

| # | Story | Priority | SRS Ref | Acceptance Criteria | Labels |
|---|-------|----------|---------|-------------------|--------|
| 22 | As the system, I want to scan sources via client-triggered requests and admin manual triggers so that new content is discovered without requiring direct server-to-FTP access | Critical | FR-SCAN-01, NFR-SCAL-03 | Given a user on a BDIX network, the frontend scans reachable FTP sources in the background (Web Worker) and POSTs new file listings to the backend. Admin can also trigger scans manually from a BDIX-connected machine. No server-side cron needed — the server cannot reach FTP directly | scan, backend, frontend |
| 23 | As a developer, I want scanning split into two phases (Collector + Enricher) so that fast indexing is decoupled from slow API enrichment | Critical | FR-SCAN-02 | Given the scanning system, then Phase 1 collects files without API calls and Phase 2 enriches separately. Phases run independently for scalability | scan, backend, architecture |
| 24 | As the system, I want Phase 1 to crawl sources and save raw paths to a shadow table so that the main table is never in an inconsistent state | Critical | FR-SCAN-03, FR-SCAN-06 | Given Phase 1 execution, then raw file paths and filenames are saved to a shadow table with status "pending" — zero external API calls. After crawl completes, a single batch sync moves data to the main table | scan, backend |
| 25 | As the system, I want to only index valid video extensions so that non-video files and empty directories are ignored | Critical | FR-SCAN-04 | Given crawled files, then only valid video extensions (.mp4, .mkv, .avi, .m3u8) create DB entries — empty directories are ignored | scan, backend |
| 26 | As the system, I want to auto-detect character encoding of FTP listings so that non-UTF-8 filenames are handled correctly | High | FR-SCAN-05 | Given FTP directory listings, then chardet library detects encoding and handles UTF-8 and Windows-1252 | scan, backend |
| 27 | As the system, I want to discover and auto-link subtitle files so that subtitles are available for playback | High | FR-SCAN-07, FR-SCAN-08 | Given Phase 1 crawl, then .srt and .vtt files in same directory as video files are discovered. Files with >60% similarity to video filename are auto-linked as subtitle tracks | scan, subtitles, backend |
| 28 | As the system, I want to detect and link multi-part movies so that CD1/CD2 files appear as a single title | High | FR-SCAN-09, FR-SCAN-10 | Given filenames with "CD" or "Part" keywords, then multi-part movies are detected and linked to a single content record with part ordering info | scan, backend |
| 29 | As the system, I want to log each scan's results so that admins can monitor scan health and troubleshoot issues | High | FR-SCAN-11 | Given each scan completion, then a log entry is inserted into source_scan_logs. Logs older than 90 days are pruned | scan, backend, logging |
| 30 | As an admin, I want to manually trigger a Phase 1 scan for any source so that I can force a rescan on demand | High | FR-SCAN-12, FR-ADMIN-05 | Given admin panel, then admin can trigger Phase 1 scan for any source on demand | scan, admin, backend |

---

## Epic: Content Scanning — Phase 2 (Enricher)

| # | Story | Priority | SRS Ref | Acceptance Criteria | Labels |
|---|-------|----------|---------|-------------------|--------|
| 31 | As the system, I want an Enricher worker that sequentially processes pending entries so that metadata is fetched reliably and resumably | Critical | FR-ENRICH-01, FR-ENRICH-23 | Given pending entries in DB, then Enricher processes them sequentially as a background worker managed by Supervisor. Worker is resumable — if interrupted, continues from next "pending" entry on restart | enricher, backend |
| 32 | As the system, I want to normalize filenames and extract title, year, quality, and season/episode info so that API queries are accurate | Critical | FR-ENRICH-02, FR-ENRICH-03, FR-ENRICH-04, FR-ENRICH-05 | Given a raw filename, then PTN extracts clean title, year, quality, codec. Noise tokens (BluRay, x264, YIFY) are stripped. For TV series, SxxExx season/episode numbers are extracted. Special/Extra/S00 → Season 0 | enricher, backend |
| 33 | As the system, I want to fuzzy-match titles with confidence scoring so that misspelled filenames still find correct metadata | High | FR-ENRICH-06, FR-ENRICH-07, FR-ENRICH-08, FR-ENRICH-24 | Given a normalized title, then fuzzy matching (Levenshtein) handles misspellings. Each match gets a confidence score (0–100%); below 80% is flagged "Low Confidence" and routed to Admin Review Queue. Unmatched content is logged with original filename and reason | enricher, backend |
| 34 | As the system, I want to use TMDb ID as the dedup anchor so that multiple source files map to a single unified content record | Critical | FR-ENRICH-09, FR-ENRICH-10, FR-ENRICH-11 | Given metadata matches, then TMDb ID is used as unique anchor for deduplication. Files from different sources matching same TMDb ID link to one content record with single metadata + multiple source links | enricher, backend |
| 35 | As the system, I want to enrich content with full metadata from TMDb/OMDb so that users see posters, cast, genres, and ratings | Critical | FR-ENRICH-14, FR-ENRICH-12 | Given a confident match, then poster, description, cast, genres, ratings are fetched from TMDb (primary) or OMDb (fallback). TMDb alternative titles are stored in a searchable field | enricher, backend |
| 36 | As the system, I want to build a hierarchical TV series structure so that episodes are properly organized under seasons | Critical | FR-ENRICH-15, FR-ENRICH-16, FR-ENRICH-17, FR-ENRICH-18 | Given a TV series, then database stores Series → Season → Episode hierarchy. Each episode has TMDb ID, SxxExx, title, and source links. Source links are filtered by composite key (TMDb ID + S + E). Each source link stores: source_id, path, quality, size, codec, subtitle_paths | enricher, backend |
| 37 | As the system, I want to rate-limit TMDb API calls with backoff and retry so that API quotas are respected | Critical | FR-ENRICH-19, FR-ENRICH-20, FR-ENRICH-21 | Given enrichment processing, then API requests are throttled to 3 req/s (configurable). On rate limit hit, exponential backoff with retry is applied. 429 responses honor the Retry-After header | enricher, backend, rate-limit |
| 38 | As the system, I want to prioritize enriching newest content first so that recently added files appear sooner | Moderate | FR-ENRICH-22 | Given pending queue, then recently added content is enriched before older entries | enricher, backend |
| 39 | As the system, I want to re-verify metadata for early-release content so that incomplete metadata gets updated once official data is available | Low | FR-ENRICH-13 | Given "In-Theater" or "Early Release" content, then metadata is re-verified every 7 days for the first month | enricher, backend |
| 40 | As the system, I want to auto-prune source links unreachable by all users for 30+ days so that dead links are cleaned up | Moderate | FR-ENRICH-25, NFR-REL-08 | Given source links unreachable by 100% of users for 30+ consecutive days, then they are automatically deleted | enricher, backend, cleanup |

---

## Epic: Source Scrapers

### Phase 4C: Scrapers Development (Priority 4)
| ID | Title | Phase | Description | SRS Ref | Component | Status |
|---|---|---|---|---|---|---|
| BDF-40 | Filename Parameter Configuration | Phase 4 | Configure dynamic rules for filename parsing | [12.1.2] | Backend | DONE |
| BDF-41 | Dflix Scraper Module | Phase 4 | Build scraper for Dflix format | [4.2] | Backend | DONE |
| BDF-42 | DhakaFlix Movie Scraper | Phase 4 | Build scraper for DhakaFlix movies | [4.3] | Backend | DONE |
| BDF-43 | DhakaFlix Series Scraper | Phase 4 | Build scraper for DhakaFlix series | [4.3] | Backend | DONE |
| BDF-44 | FTP Scraper (Emby/Jellyfin) | Phase 4 | Build scraper for RoarZone parsing (Emby) | [4.4] | Backend | DONE |
| BDF-45 | Standard FTP Scraper | Phase 4 | Build standard directory scraper (FTPBD, CircleFTP) | [4.5], [4.6], [4.7] | Backend | DONE |
| BDF-46 | Scraper Plugin Interface | Phase 4 | Define interface for adding custom scrapers | [11.5] | Backend | DONE |
| BDF-47 | Proxy/VPN Rotation Engine | Phase 4 | Implement request rotation to avoid bans | [11.2] | Backend | DONE |
| BDF-48 | Scraper Monitoring & Metrics | Phase 4 | Track success rates, errors, timeouts | [10.2] | Backend | DONE |

---

## Epic: Admin Panel API

| # | Story | Priority | SRS Ref | Acceptance Criteria | Labels |
|---|-------|----------|---------|-------------------|--------|
| 49 | As an admin, I want a dashboard stats endpoint so that I can see platform health at a glance | High | FR-ADMIN-01 | Given admin request, then API returns key metrics: total users, content, sources, review queue size, enrichment progress | admin, backend |
| 50 | As an admin, I want to CRUD sources with connection testing so that I can manage BDIX sources reliably | Critical | FR-ADMIN-02, FR-ADMIN-03 | Given admin sources API, then admin can create, read, update, delete sources. Test connection endpoint validates source before saving | admin, backend |
| 51 | As an admin, I want to view scan logs per source so that I can troubleshoot scan failures | High | FR-ADMIN-04 | Given a source detail, then admin can view scan history with status, items_found, errors | admin, backend |
| 52 | As an admin, I want to view, search, filter, and delete content so that I can manage the content library | Critical | FR-ADMIN-06, FR-ADMIN-08 | Given content management endpoints, then admin can browse, search, filter, and delete content | admin, backend |
| 53 | As an admin, I want to force a metadata re-sync for any content so that outdated or incorrect metadata is refreshed | High | FR-ADMIN-07 | Given a content item, then admin can force a metadata re-fetch from TMDb/OMDb | admin, backend |
| 54 | As an admin, I want to mark content as featured or trending so that I can curate the homepage | Moderate | FR-ADMIN-09 | Given a content item, then admin can toggle featured/trending flags | admin, backend |
| 55 | As an admin, I want a review queue to resolve low-confidence content so that unmatched files get correct metadata | High | FR-ADMIN-10, FR-ADMIN-11, FR-ADMIN-20 | Given the review queue endpoint, then all flagged content is listed with original filenames and match suggestions. Admin can approve, manually correct (search TMDb and apply), or reject items | admin, backend |
| 56 | As an admin, I want a crowdsourced source health dashboard with ISP breakdown so that I can identify outage patterns | High | FR-ADMIN-12, FR-ADMIN-13, FR-ADMIN-14 | Given admin health endpoint, then aggregated user health reports are displayed per source with ISP breakdown. Distinguishes "Globally Offline" vs "ISP-Specific Outage" | admin, backend |
| 57 | As an admin, I want to view and control the enrichment worker so that I can pause/resume processing | Moderate | FR-ADMIN-15, FR-ADMIN-16 | Given enrichment endpoints, then admin sees worker status (running/paused), pending count, processing rate. Admin can pause or resume the worker | admin, backend |
| 58 | As an admin, I want to manage users (list, ban/unban, reset password) so that I can moderate the platform | High | FR-ADMIN-17, FR-ADMIN-18 | Given user management endpoints, then admin sees users with signup date, last active, watch count. Admin can ban, unban, or reset password | admin, backend |
| 59 | As an admin, I want to configure system settings so that API keys, scan schedules, and site name are manageable | High | FR-ADMIN-19 | Given settings endpoints, then admin can update API keys, scan intervals, and site name | admin, backend |
| 60 | As an admin, I want an analytics endpoint so that I can track platform usage and trends (Post-MVP) | Low | FR-ADMIN-21 | Given analytics endpoint, then admin sees most-watched, user trends, source usage, matching accuracy, ISP breakdown — Post-MVP | admin, backend |

---

## Epic: Deployment & Infrastructure

| # | Story | Priority | SRS Ref | Acceptance Criteria | Labels |
|---|-------|----------|---------|-------------------|--------|
| 61 | As a DevOps engineer, I want the backend deployed as PHP-FPM + Nginx so that the API is production-ready | Critical | SRS §7.1 | Given production, then Laravel API runs behind PHP-FPM with Nginx on port 8000 | deploy, backend, infra |
| 62 | As a DevOps engineer, I want PostgreSQL 15+ and Redis 7+ provisioned so that the database and cache are available | Critical | SRS §7.1 | Given deployment, then PostgreSQL 15+ is on port 5432 and Redis 7+ is on port 6379 for cache and job queue | deploy, database, infra |
| 63 | As a DevOps engineer, I want Queue and Enrichment Workers managed by Supervisor so that background jobs auto-restart on failure | High | SRS §7.1 | Given queue processing, then both Queue Worker and Enrichment Worker run under Supervisor with auto-restart. Enrichment Worker is resumable on restart | deploy, backend, infra |
| 64 | As a DevOps engineer, I want Laravel Scheduler running via system cron so that scheduled tasks execute reliably | High | SRS §7.1 | Given scheduled jobs, then system cron runs `* * * * * php artisan schedule:run` | deploy, backend, infra |
| 65 | As a DevOps engineer, I want application logging with daily rotation and error tracking so that issues are debuggable | High | SRS §7.2 | Given application events, then logs are written to daily rotating files. Telescope in dev, optional Sentry in production. Queue monitoring via Horizon or Telescope | deploy, backend, logging |

---

## Epic: Non-Functional — Performance

| # | Story | Priority | SRS Ref | Acceptance Criteria | Labels |
|---|-------|----------|---------|-------------------|--------|
| 66 | As a developer, I want API response times under 200ms (cached) and 500ms (uncached) so that the platform feels fast | High | NFR-PERF-02, NFR-PERF-03 | Given API endpoints, then cached responses return in < 200ms and uncached in < 500ms | nfr, performance, backend |
| 67 | As a developer, I want database queries averaging under 100ms with proper indexing so that performance is consistent | High | NFR-PERF-05, SRS §5.4 | Given any database query, then average execution time is under 100ms. Indexes exist on: contents.type, contents.year, contents.rating, contents.tmdb_id, contents.enrichment_status, source_links.linkable_type+linkable_id, source_health_reports.source_id/isp_name | nfr, performance, backend, database |
| 68 | As a developer, I want the system to support 1,000+ concurrent users via Redis so that the platform scales | High | NFR-PERF-06 | Given high traffic, then system supports 1,000+ concurrent users via Redis | nfr, performance, backend |
| 69 | As a developer, I want Redis cache TTLs configured per data type so that freshness and performance are balanced | High | NFR-PERF-07, NFR-PERF-08, NFR-PERF-09 | Given content endpoints, then Redis cache TTLs are: content list = 1 hour, content detail = 24 hours, user library = 5 minutes | nfr, performance, backend |
| 70 | As a developer, I want initial enrichment to process ~10,000+ files in ~1 hour so that first-time setup is practical | Moderate | NFR-PERF-11 | Given first-time enrichment run, then system processes ~10,000+ files within approximately 1 hour | nfr, performance, backend, enricher |

---

## Epic: Non-Functional — Security

| # | Story | Priority | SRS Ref | Acceptance Criteria | Labels |
|---|-------|----------|---------|-------------------|--------|
| 71 | As a developer, I want admin routes protected by role-check middleware so that only admins can access admin endpoints | Critical | NFR-SEC-03 | Given admin endpoints, then only users with admin role can access them | nfr, security, backend |
| 72 | As a developer, I want API rate limiting at 60 requests/minute per user so that abuse is prevented | High | NFR-SEC-04 | Given any client, then requests are throttled at 60/minute | nfr, security, backend |
| 73 | As a developer, I want CORS restricted to frontend and admin panel origins so that unauthorized domains are blocked | High | NFR-SEC-05 | Given API, then CORS only allows flixarion.com and admin.flixarion.com origins | nfr, security, backend |
| 74 | As a developer, I want API keys and FTP credentials stored only in .env so that secrets are never exposed to the frontend | Critical | NFR-SEC-06, NFR-SEC-07 | Given TMDb/OMDb keys and Emby tokens, they exist only in .env and are never sent to client | nfr, security, backend |
| 75 | As a developer, I want time-limited signed URLs for authenticated FTP sources so that direct access is temporary and secure | High | NFR-SEC-08 | Given authenticated FTP sources, then backend generates signed URLs with expiry | nfr, security, backend |

---

## Epic: Non-Functional — Reliability

| # | Story | Priority | SRS Ref | Acceptance Criteria | Labels |
|---|-------|----------|---------|-------------------|--------|
| 76 | As the system, I want to gracefully handle BDIX source downtime so that the platform remains operational | Critical | NFR-REL-01 | Given a BDIX source down, then system continues serving content from other sources without crash | nfr, reliability, backend |
| 77 | As the system, I want failed scan jobs to retry up to 3 times with exponential backoff so that transient failures are recovered | High | NFR-REL-03 | Given a scan job failure, then it retries up to 3 times with increasing delay | nfr, reliability, backend |
| 78 | As the system, I want source health scores updated via crowdsourced consensus so that availability data is accurate | High | NFR-REL-04 | Given user health reports, then source health scores are updated via consensus-based aggregation | nfr, reliability, backend |

---

## Epic: Non-Functional — Data Integrity

| # | Story | Priority | SRS Ref | Acceptance Criteria | Labels |
|---|-------|----------|---------|-------------------|--------|
| 79 | As a developer, I want proper database constraints and versioned migrations so that data integrity is enforced | Critical | SRS §5.4, NFR-MAIN-04 | Given the database, then: FK constraints with ON DELETE CASCADE on user-related tables. Unique constraints on users.email, contents.tmdb_id, sources.name, (user_id, content_id). All schema changes via versioned Laravel migrations | nfr, data, backend, database |

---

## Epic: Non-Functional — Maintainability

| # | Story | Priority | SRS Ref | Acceptance Criteria | Labels |
|---|-------|----------|---------|-------------------|--------|
| 80 | As a developer, I want all environment-specific values in .env files so that nothing is hardcoded | Critical | NFR-MAIN-03 | Given any config value, then it is in .env, never hardcoded | nfr, maintainability, backend |
| 81 | As a developer, I want a consistent JSON structure for all API responses so that consumers have a predictable contract | High | NFR-MAIN-05 | Given any API response, then it follows standard format: {data, meta} for success, {error, message, errors} for errors | nfr, maintainability, backend |

---

