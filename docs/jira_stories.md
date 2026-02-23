# BDFlix — Jira Story Tables

> **How to use**: Copy each table into a Confluence page. Then connect Confluence to Jira and import.  
> Each section = **one Epic** in Jira. Each row = **one Story**.  
> **SRS Ref** column maps each story back to the SRS requirement ID for traceability.

---

## Epic: Authentication & User Management

| Summary | Issue Type | Priority | SRS Ref | Acceptance Criteria | Labels |
|---------|-----------|----------|---------|-------------------|--------|
| User registration with name, email, and password | Story | Critical | FR-AUTH-01 | Given a guest user, when they submit valid name/email/password, then an account is created and JWT is returned | auth, backend |
| User login with email and password returning JWT | Story | Critical | FR-AUTH-02 | Given valid credentials, when submitted, then access_token and refresh_token are returned | auth, backend |
| JWT token refresh without re-login | Story | Critical | FR-AUTH-03 | Given an expired access_token, when refresh_token is sent, then a new access_token is returned without re-login | auth, backend |
| User logout (invalidate token) | Story | High | FR-AUTH-04 | Given a logged-in user, when they logout, then the token is invalidated and cannot be reused | auth, backend |
| Get authenticated user profile (`/me` endpoint) | Story | High | FR-AUTH-05 | Given a valid JWT, when GET /api/auth/me is called, then the user's profile data is returned | auth, backend, api |
| Hash passwords with bcrypt before storage | Story | Critical | FR-AUTH-06 | Given a registration or password change, then the password is stored using bcrypt with cost factor 10+ | auth, security, backend |
| Enforce email uniqueness during registration | Story | Critical | FR-AUTH-07 | Given an email already in use, when registration is attempted, then a 422 validation error is returned | auth, backend |

---

## Epic: Content Browsing & Search

| Summary | Issue Type | Priority | SRS Ref | Acceptance Criteria | Labels |
|---------|-----------|----------|---------|-------------------|--------|
| Paginated content listing (movies + series, 20/page) | Story | Critical | FR-BROWSE-01 | Given a browse request, when GET /api/contents is called, then paginated results with 20 items/page are returned | browse, backend, frontend |
| Filter content by type (movie/series), genre, and year | Story | High | FR-BROWSE-02 | Given filter params, when applied, then only matching content is returned | browse, backend, frontend |
| Text search by content title | Story | Critical | FR-BROWSE-03 | Given a search query, when submitted, then matching content by title is returned | browse, search, backend |
| Dual TMDb search (movie + TV endpoints simultaneously) | Story | High | FR-BROWSE-04 | Given a search query, then both TMDb /movie/search and /tv/search are queried in parallel and results are combined | browse, search, backend |
| Search by Alternative Titles (local nicknames/aliases) | Story | Moderate | FR-BROWSE-05 | Given a search for "Hobbs and Shaw", then the full-title match "Fast & Furious Presents: Hobbs & Shaw" is returned | browse, search, backend |
| Merged search results with Movie/TV Series badges | Story | High | FR-BROWSE-06 | Given combined search results, then each result displays a clear Movie or TV Series badge | browse, search, frontend |
| Trending content endpoint (by watch count) | Story | High | FR-BROWSE-07 | Given GET /api/contents?trending=true, then content sorted by watch count is returned | browse, backend |
| Popular content endpoint (by rating) | Story | High | FR-BROWSE-08 | Given GET /api/contents?popular=true, then content sorted by rating is returned | browse, backend |
| Recently added content endpoint (by created_at) | Story | High | FR-BROWSE-09 | Given GET /api/contents?recent=true, then content sorted by newest first is returned | browse, backend |
| Full content detail page (poster, backdrop, cast, genres, rating, runtime, trailer) | Story | Critical | FR-BROWSE-10 | Given a content ID, then all metadata fields from JSONB are displayed on the detail page | browse, frontend |
| Series detail with season and episode listings | Story | Critical | FR-BROWSE-11 | Given a TV series, then seasons with episode titles and thumbnails are displayed | browse, frontend |
| Content detail includes all sources with quality, file size, codec | Story | Critical | FR-BROWSE-12 | Given a content detail, then all available source links with quality labels and file sizes are shown | browse, frontend, backend |
| Filter content by user's accessible sources (`available_only=true`) | Story | Moderate | FR-BROWSE-13 | Given available_only=true, then only content with reachable sources for the current user is returned | browse, backend |
| Generate BDIX source links via JavaScript after interaction | Story | High | FR-BROWSE-14 | Given a content page, then BDIX source URLs are not in SSR HTML — they are generated client-side after user clicks | browse, security, frontend |
| robots.txt blocks `/play/` and `/source/` routes | Story | High | FR-BROWSE-15 | Given a search engine crawler, then /play/ and /source/ routes return disallow in robots.txt | browse, security, seo |
| Serve poster images through image proxy (wsrv.nl/Statically) | Story | Moderate | FR-BROWSE-16 | Given a poster image, then it is served via image proxy with caching and resizing | browse, frontend, performance |
| Image proxy Referer validation to prevent hotlinking | Story | Low | FR-BROWSE-17 | Given a request from a non-platform domain, then the image proxy returns 403 | browse, security |

---

## Epic: Video Streaming & Playback

| Summary | Issue Type | Priority | SRS Ref | Acceptance Criteria | Labels |
|---------|-----------|----------|---------|-------------------|--------|
| Direct video streaming from BDIX FTP to browser (no proxy) | Story | Critical | FR-PLAY-01 | Given a play action, then the video tag sources directly from the BDIX FTP URL without backend proxy | playback, frontend |
| In-browser playback for MP4 and HLS (.m3u8) | Story | Critical | FR-PLAY-02 | Given a compatible MP4 or HLS file, then it plays in Plyr.js with hls.js support | playback, frontend |
| Pre-flight format detection (container + audio codec check) | Story | Critical | FR-PLAY-03 | Given a play action, then HTTP HEAD is sent to detect MP4/MKV container and AAC/DTS/AC3 audio codec | playback, frontend |
| Redirect to Playback Bridge for incompatible formats (MKV/DTS/AC3) | Story | Critical | FR-PLAY-04 | Given MKV or DTS/AC3 detected, then user is redirected to Bridge page instead of browser player | playback, bridge, frontend |
| Bridge page with VLC and PotPlayer protocol handler buttons | Story | Critical | FR-PLAY-05 | Given the Bridge page, then "Launch in VLC" (vlc://) and "Launch in PotPlayer" (potplayer://) buttons are shown with explanation | playback, bridge, frontend |
| 2-second timeout detection for protocol handler launch | Story | High | FR-PLAY-06 | Given a protocol handler click, then visibilitychange is monitored for 2 seconds to detect if app opened | playback, bridge, frontend |
| Fallback pop-up when external player doesn't open | Story | High | FR-PLAY-07 | Given no visibility change in 2 seconds, then pop-up shows "VLC isn't opening. [Download VLC] or [Direct Download]" | playback, bridge, frontend |
| Mobile device detection with VLC Mobile/MX Player priority | Story | High | FR-PLAY-08 | Given a mobile device (userAgent check), then "Open in VLC Mobile" or "Play in MX Player" buttons are prioritized | playback, bridge, mobile, frontend |
| Direct download link on Bridge page as fallback | Story | High | FR-PLAY-09 | Given the Bridge page, then a direct download link is always available | playback, bridge, frontend |
| Mixed content troubleshooting section on Bridge page | Story | Moderate | FR-PLAY-10 | Given HTTP sources on HTTPS site, then Bridge page includes instructions to allow mixed content | playback, bridge, frontend |
| Auto-select best quality source (reachability + quality ranking) | Story | Critical | FR-PLAY-11 | Given multiple sources, then system auto-selects based on reachability first, then quality (4K > 1080p > 720p > 480p) | playback, frontend |
| Manual source override via dropdown with quality labels | Story | High | FR-PLAY-12 | Given a "Select Source" dropdown, then user can manually choose any available source with quality labels | playback, frontend |
| Multi-part movie UI (single poster with Part 1/Part 2 toggles) | Story | High | FR-PLAY-13 | Given a multi-part movie (CD1/CD2), then a single poster is shown with part toggles in the source menu | playback, frontend |
| Source dropdown shows reachability indicators | Story | Moderate | FR-PLAY-14 | Given a source dropdown, then each source shows a reachable/unreachable badge | playback, frontend |
| Silent re-scan on 404 error during playback | Story | High | FR-PLAY-15 | Given a 404 from a source during playback, then a background re-scan is triggered for that source/filename | playback, backend |
| Auto-update database path if file found at new location | Story | High | FR-PLAY-16 | Given file found at new path during re-scan, then database is updated and playback retries | playback, backend |
| Mark broken links and fallback to next source | Story | High | FR-PLAY-17 | Given file not found during re-scan, then source link is marked "broken" and next source is attempted | playback, backend |
| Auto-fallback to next source on playback failure | Story | High | FR-PLAY-18 | Given a stream failure, then player automatically switches to next best available source | playback, frontend |
| Standard player controls (play/pause, seek, volume, fullscreen) | Story | Critical | FR-PLAY-19 | Given the video player, then all standard controls are functional | playback, frontend |
| Subtitle selector in player | Story | High | FR-PLAY-20 | Given available subtitles, then a subtitle picker is shown in the player UI | playback, subtitles, frontend |
| Load linked subtitle tracks (.srt, .vtt) from crawl phase | Story | High | FR-PLAY-21 | Given subtitles discovered during crawl, then they are loaded as tracks in the player | playback, subtitles, frontend |
| Subtitle encoding detection and ANSI/Bijoy to Unicode re-encoding | Story | Moderate | FR-PLAY-22 | Given non-UTF-8 subtitles, then chardet detects encoding and ANSI/Bijoy is re-encoded to Unicode | playback, subtitles, frontend |
| Auto-load subtitles for VLC/PotPlayer deep links | Story | Moderate | FR-PLAY-23 | Given VLC/PotPlayer deep links, then subtitle files in the same directory are auto-loaded | playback, subtitles, bridge |
| Display current source name and quality in player | Story | High | FR-PLAY-24 | Given active playback, then source name and quality (e.g., "DhakaFlix · 1080p") are shown | playback, frontend |
| Episode navigation (prev/next) for TV series | Story | High | FR-PLAY-25 | Given a series episode playing, then prev/next episode buttons are available | playback, frontend |
| Direct download links for all content | Story | Moderate | FR-PLAY-26 | Given any content, then a download link with the source URL is available | playback, frontend |

---

## Epic: User Library

| Summary | Issue Type | Priority | SRS Ref | Acceptance Criteria | Labels |
|---------|-----------|----------|---------|-------------------|--------|
| Add/remove content from watchlist | Story | Critical | FR-LIB-01 | Given a registered user, when they toggle watchlist, then content is added/removed via POST/DELETE /api/user/watchlist | library, backend, frontend |
| Add/remove content from favorites/bookmarks | Story | Critical | FR-LIB-02 | Given a registered user, when they toggle favorites, then content is added/removed via POST/DELETE /api/user/favorites | library, backend, frontend |
| Debounce watchlist/favorites toggle (1-second delay) | Story | High | FR-LIB-03 | Given rapid toggling, then only 1 database write occurs after 1 second of inactivity | library, frontend, performance |
| Last-write-wins within debounce window | Story | High | FR-LIB-04 | Given multiple toggles within 1 second, then only the final state is saved | library, frontend |
| Trigger-only watch history (one entry per "Play" click) | Story | Critical | FR-LIB-05 | Given a user clicks "Play", then exactly one history entry is created — no playback position or duration tracked | library, backend |
| No playback position tracking in history | Story | Critical | FR-LIB-06 | Given watch history entries, then they contain only content_id and timestamp — no progress_seconds | library, backend |
| Cache last 10 watched items (JSON column or localStorage) | Story | High | FR-LIB-07 | Given a user's history, then the last 10 items are cached for fast "Recently Watched" display without JOIN queries | library, backend, frontend |
| Manual "Mark as Completed" on movie card | Story | Moderate | FR-LIB-08 | Given a movie card, then a checkmark icon lets the user manually mark content as completed | library, frontend |
| Move completed items from "Recently Watched" to "Finished" archive | Story | Moderate | FR-LIB-09 | Given content marked as completed, then it moves from Recently Watched to a Finished section | library, frontend |
| View complete watch history | Story | High | FR-LIB-10 | Given GET /api/user/history, then the full chronological history is returned | library, backend |
| Rate and review content | Story | Low | FR-LIB-11 | Given a registered user, then they can rate (1-10) and write a review — Post-MVP | library, backend, frontend |

---

## Epic: ISP Source Availability (Race Strategy)

| Summary | Issue Type | Priority | SRS Ref | Acceptance Criteria | Labels |
|---------|-----------|----------|---------|-------------------|--------|
| Race Strategy: simultaneous ping of all BDIX sources on app load | Story | Critical | FR-ISP-01 | Given app load, then all BDIX sources are pinged simultaneously via fetch() to a small health file | isp, frontend |
| 1.5-second timeout for source reachability | Story | Critical | FR-ISP-02 | Given a source ping, then sources responding within 1.5s are "Online", others are "Unreachable" | isp, frontend |
| Auto-hide/deprioritize content on unreachable sources | Story | High | FR-ISP-03 | Given unreachable sources, then content exclusively on those sources is hidden or deprioritized | isp, frontend |
| Prioritize fastest-responding source for multi-source content | Story | High | FR-ISP-04 | Given multiple reachable sources, then the fastest-responding source (lowest ping) is prioritized | isp, frontend |
| Service Worker cache for reachability results (30 min TTL) | Story | High | FR-ISP-05 | Given completed Race Strategy, then results are cached in Service Worker for 30 minutes | isp, frontend, performance |
| Anonymous health report to backend (ISP + status, no IP) | Story | High | FR-ISP-06 | Given completed Race Strategy, then browser sends POST /api/sources/health-report with ISP name and source statuses — never full IP | isp, frontend, backend, privacy |
| Privacy: exclude full local IP from health reports | Story | Critical | FR-ISP-07 | Given a health report, then only ISP name and reachability booleans are included — no IP addresses | isp, privacy, backend |
| Backend aggregation of crowdsourced health reports | Story | High | FR-ISP-08 | Given multiple user health reports, then backend aggregates them to build consensus source health | isp, backend |
| Manual re-test source accessibility button | Story | Moderate | FR-ISP-09 | Given a re-test button in Settings, then fresh Race Strategy is triggered and Service Worker cache is invalidated | isp, frontend |
| View reachable sources from current network | Story | Moderate | FR-ISP-10 | Given a source status page, then user can see which sources are reachable | isp, frontend |
| Manual source preference override | Story | Low | FR-ISP-11 | Given source settings, then user can set a preferred source for playback | isp, frontend |

---

## Epic: Content Scanning — Phase 1 (Collector)

| Summary | Issue Type | Priority | SRS Ref | Acceptance Criteria | Labels |
|---------|-----------|----------|---------|-------------------|--------|
| Automated source scan every 6 hours | Story | Critical | FR-SCAN-01 | Given Laravel scheduler, then ScanSourceJob is dispatched for all active sources every 6 hours | scan, backend, scheduler |
| Two-phase scanning architecture (Collector + Enricher split) | Story | Critical | FR-SCAN-02 | Given scanning system, then Phase 1 collects files without API calls and Phase 2 enriches separately | scan, backend, architecture |
| Phase 1: crawl sources and save raw paths with "pending" status | Story | Critical | FR-SCAN-03 | Given Phase 1 execution, then raw file paths and filenames are saved to DB with status "pending" — zero external API calls | scan, backend |
| Validate video extensions only (.mp4, .mkv, .avi, .m3u8) | Story | Critical | FR-SCAN-04 | Given crawled files, then only valid video extensions create DB entries — empty directories are ignored | scan, backend |
| Character encoding auto-detection (UTF-8 / Windows-1252) | Story | High | FR-SCAN-05 | Given FTP directory listings, then chardet library detects encoding and handles UTF-8 and Windows-1252 | scan, backend |
| Shadow table: write to temp table, then batch sync to main | Story | High | FR-SCAN-06 | Given Phase 1 scan, then entries write to shadow table first, then sync to main table in one batch to prevent UI lag | scan, backend, performance |
| Scan for subtitle files (.srt, .vtt) in same directory | Story | High | FR-SCAN-07 | Given Phase 1 crawl, then .srt and .vtt files in same directory as video files are discovered | scan, subtitles, backend |
| Auto-link subtitles with >60% filename similarity | Story | High | FR-SCAN-08 | Given discovered subtitles, then files with >60% similarity to video filename are auto-linked as subtitle tracks | scan, subtitles, backend |
| Detect multi-part movies (CD1/CD2, Part1/Part2) | Story | High | FR-SCAN-09 | Given filenames with "CD" or "Part" keywords, then multi-part movies are detected | scan, backend |
| Link multi-part files to single content record with sequencing | Story | High | FR-SCAN-10 | Given detected multi-part files, then they are linked to one content record with part ordering info | scan, backend |
| Scan log entry (status, items_found, items_matched, errors) | Story | High | FR-SCAN-11 | Given each scan completion, then a log entry is inserted into source_scan_logs | scan, backend, logging |
| Admin manual trigger for Phase 1 scans | Story | High | FR-SCAN-12 | Given admin panel, then admin can trigger Phase 1 scan for any source on demand | scan, admin, backend |

---

## Epic: Content Scanning — Phase 2 (Enricher)

| Summary | Issue Type | Priority | SRS Ref | Acceptance Criteria | Labels |
|---------|-----------|----------|---------|-------------------|--------|
| Enricher background worker processing "pending" entries one at a time | Story | Critical | FR-ENRICH-01 | Given pending entries in DB, then Enricher processes them sequentially as a background worker | enricher, backend |
| Filename normalization with parser library (PTN) | Story | Critical | FR-ENRICH-02 | Given a raw filename, then PTN extracts clean title, year, quality, and codec information | enricher, backend |
| Strip noise tokens (resolution, codec, release group, extension) | Story | Critical | FR-ENRICH-03 | Given a filename, then noise tokens like "BluRay", "x264", "YIFY" are stripped before API query | enricher, backend |
| Extract SxxExx season/episode numbers for TV series | Story | Critical | FR-ENRICH-04 | Given a TV series filename, then season and episode numbers are extracted via regex | enricher, backend |
| Map "Special" / "Extra" / "S00" to Season 0 | Story | High | FR-ENRICH-05 | Given folders/files with "Special", "Extra", or "S00", then they are mapped to Season 0 | enricher, backend |
| Fuzzy matching via Levenshtein distance for TMDb/OMDb search | Story | High | FR-ENRICH-06 | Given a normalized title, then fuzzy matching handles misspellings and variations when searching APIs | enricher, backend |
| Confidence scoring (0–100%) with 80% threshold | Story | High | FR-ENRICH-07 | Given a metadata match, then a confidence score is assigned — below 80% is flagged as "Low Confidence" | enricher, backend |
| Route low-confidence / unmatched content to Admin Review Queue | Story | High | FR-ENRICH-08 | Given confidence < 80% or no match, then content is placed in Admin Review Queue | enricher, backend, admin |
| TMDb ID as unique anchor for deduplication | Story | Critical | FR-ENRICH-09 | Given a metadata match, then TMDb ID is used as the unique identifier — prevents duplicate entries | enricher, backend |
| Link multiple source files to single unified content record | Story | Critical | FR-ENRICH-10 | Given files from different sources matching same TMDb ID, then they link to one content record | enricher, backend |
| Unified content record: single metadata + multiple source links | Story | Critical | FR-ENRICH-11 | Given unified content, then one set of poster/description/cast/genres with multiple source links | enricher, backend |
| Store TMDb "Alternative Titles" in searchable field | Story | Moderate | FR-ENRICH-12 | Given metadata fetch, then TMDb's Alternative Titles are stored for search support | enricher, backend |
| Lazy enrichment re-trigger for early releases (every 7 days for 1 month) | Story | Low | FR-ENRICH-13 | Given "In-Theater" or "Early Release" content, then metadata is re-verified every 7 days for the first month | enricher, backend |
| Full metadata enrichment (poster, cast, genres, ratings) from TMDb/OMDb | Story | Critical | FR-ENRICH-14 | Given a confident match, then poster, description, cast, genres, and ratings are fetched and stored | enricher, backend |
| Hierarchical TV series structure: Series > Season > Episode | Story | Critical | FR-ENRICH-15 | Given a TV series, then database stores Series → Season → Episode hierarchy | enricher, backend, database |
| Episode records with TMDb ID, season/episode numbers, title, source links | Story | Critical | FR-ENRICH-16 | Given an episode, then TMDb ID, SxxExx, episode title, and source links are stored | enricher, backend |
| Filter episode source links by TMDb ID + Season + Episode | Story | Critical | FR-ENRICH-17 | Given episode playback, then source links are filtered by composite key: TMDb ID + S + E | enricher, backend |
| Source link stores: source_id, path, quality, size, codec, subtitle_paths | Story | Critical | FR-ENRICH-18 | Given a source link, then it stores source_id, file_path, quality, file_size, codec_info, subtitle_paths | enricher, backend, database |
| Rate limit: 3 requests/second to TMDb API | Story | Critical | FR-ENRICH-19 | Given enrichment processing, then API requests are throttled to 3 req/s (configurable) | enricher, backend, rate-limit |
| Exponential backoff and retry on API rate limits | Story | High | FR-ENRICH-20 | Given API rate limit hit, then exponential backoff with retry is applied | enricher, backend |
| Detect and honor 429 "Too Many Requests" + Retry-After header | Story | High | FR-ENRICH-21 | Given a 429 response, then Enricher pauses and respects the Retry-After header | enricher, backend |
| Priority-based processing (newest content first) | Story | Moderate | FR-ENRICH-22 | Given pending queue, then recently added content is enriched before older entries | enricher, backend |
| Resumable worker (continue from next "pending" on restart) | Story | High | FR-ENRICH-23 | Given worker interruption, then it resumes from the next "pending" entry without re-processing | enricher, backend |
| Log unmatched/low-confidence content with original filenames | Story | High | FR-ENRICH-24 | Given unmatched content, then original filename, source, and reason are logged for admin review | enricher, backend, logging |
| Auto-prune source links unreachable by 100% of users for 30+ days | Story | Moderate | FR-ENRICH-25 | Given source links unreachable by all users for 30+ consecutive days, then they are automatically deleted | enricher, backend, cleanup |

---

## Epic: Source Scrapers

| Summary | Issue Type | Priority | SRS Ref | Acceptance Criteria | Labels |
|---------|-----------|----------|---------|-------------------|--------|
| Dflix scraper (HTTP POST search + HTML parsing) | Story | Critical | FR-SCRP-01 | Given Dflix source, then scraper sends HTTP POST and parses HTML results | scraper, backend |
| DhakaFlix Movie scraper (JSON API POST) | Story | Critical | FR-SCRP-02 | Given DhakaFlix Movie source, then scraper sends JSON POST and parses response | scraper, backend |
| DhakaFlix Series scraper (JSON API POST) | Story | Critical | FR-SCRP-03 | Given DhakaFlix Series source, then scraper sends JSON POST and parses response | scraper, backend |
| RoarZone scraper (Emby API with guest auth) | Story | Critical | FR-SCRP-04 | Given RoarZone source, then scraper authenticates via Emby guest and fetches items | scraper, backend |
| FTPBD scraper (Emby API) | Story | Critical | FR-SCRP-05 | Given FTPBD source, then scraper fetches items via Emby API | scraper, backend |
| CircleFTP scraper (REST API GET) | Story | Critical | FR-SCRP-06 | Given CircleFTP source, then scraper sends GET requests and parses REST response | scraper, backend |
| ICC FTP scraper (multi-step AJAX: token → search → player) | Story | High | FR-SCRP-07 | Given ICC FTP source, then scraper follows multi-step AJAX flow to extract streams | scraper, backend |
| BaseScraperInterface common interface for all scrapers | Story | Critical | FR-SCRP-08 | Given any scraper, then it implements BaseScraperInterface with getName, testConnection, crawl methods | scraper, backend, architecture |

---

## Epic: Admin Panel

| Summary | Issue Type | Priority | SRS Ref | Acceptance Criteria | Labels |
|---------|-----------|----------|---------|-------------------|--------|
| Admin dashboard (users, content, sources, queue size, enrichment progress) | Story | High | FR-ADMIN-01 | Given admin login, then dashboard shows key metrics: total users, content, sources, review queue, enrichment progress | admin, frontend |
| CRUD sources (name, type, URL, config, priority) | Story | Critical | FR-ADMIN-02 | Given admin sources page, then admin can create, read, update, and delete sources | admin, frontend, backend |
| Test source connection before saving | Story | High | FR-ADMIN-03 | Given a source form, then admin can test connection and see result before saving | admin, frontend, backend |
| View scan logs per source | Story | High | FR-ADMIN-04 | Given a source detail, then admin can view scan history with status, items_found, errors | admin, frontend |
| Trigger manual Phase 1 scan | Story | Critical | FR-ADMIN-05 | Given a source in admin, then admin can trigger Phase 1 scan on demand | admin, frontend, backend |
| View/search/filter all content | Story | Critical | FR-ADMIN-06 | Given content management page, then admin can browse, search, and filter all content | admin, frontend |
| Force metadata re-sync for any content | Story | High | FR-ADMIN-07 | Given a content item, then admin can force a metadata re-fetch from TMDb/OMDb | admin, frontend, backend |
| Delete content | Story | Critical | FR-ADMIN-08 | Given a content item, then admin can delete it from the database | admin, frontend, backend |
| Mark content as featured/trending | Story | Moderate | FR-ADMIN-09 | Given a content item, then admin can toggle featured/trending flags | admin, frontend, backend |
| Admin Review Queue for low-confidence/unmatched content | Story | High | FR-ADMIN-10 | Given the review queue page, then all flagged content is listed with original filenames and match suggestions | admin, frontend |
| One-click approve/correct/reject from review queue | Story | High | FR-ADMIN-11 | Given a queued item, then admin can approve, correct metadata, or reject with one click | admin, frontend, backend |
| Crowdsourced source health dashboard | Story | High | FR-ADMIN-12 | Given admin health page, then aggregated user health reports are displayed per source | admin, frontend, backend |
| Source health broken down by ISP | Story | Moderate | FR-ADMIN-13 | Given a source, then admin sees per-ISP reachability (e.g., "95% on Carnival, 10% on Dot") | admin, frontend, backend |
| Distinguish "Globally Offline" vs "ISP-Specific Outage" | Story | Moderate | FR-ADMIN-14 | Given health reports, then admin can see whether a source is down for all users or specific ISPs only | admin, frontend |
| View enrichment worker status (running/paused, queue size, rate) | Story | Moderate | FR-ADMIN-15 | Given the enrichment page, then admin sees worker status, pending count, and processing rate | admin, frontend, backend |
| Pause/resume enrichment worker | Story | Moderate | FR-ADMIN-16 | Given the enrichment controls, then admin can pause or resume the worker | admin, frontend, backend |
| View user list with activity stats | Story | Moderate | FR-ADMIN-17 | Given user management page, then admin sees users with signup date, last active, watch count | admin, frontend, backend |
| Ban/unban users and reset passwords | Story | High | FR-ADMIN-18 | Given a user record, then admin can ban, unban, or reset password | admin, frontend, backend |
| Configure system settings (API keys, scan schedule, site name) | Story | High | FR-ADMIN-19 | Given settings page, then admin can update API keys, scan intervals, and site name | admin, frontend, backend |
| Manual metadata correction from review queue (single-click) | Story | High | FR-ADMIN-20 | Given a flagged item, then admin can manually search TMDb and apply correct metadata with one click | admin, frontend, backend |
| Analytics dashboard (most watched, trends, source usage, ISP distribution) | Story | Low | FR-ADMIN-21 | Given analytics page, then admin sees most-watched, user trends, source usage, matching accuracy, ISP breakdown — Post-MVP | admin, frontend, backend |

---

## Epic: Data Architecture & Schema

| Summary | Issue Type | Priority | SRS Ref | Acceptance Criteria | Labels |
|---------|-----------|----------|---------|-------------------|--------|
| TMDb ID as unique content anchor for deduplication | Story | Critical | SRS §5.2 | Given content records, then `contents.tmdb_id` is the unique identifier — not IMDb ID | data, backend, database |
| Source links store path, quality, size, codec, and subtitle paths | Story | Critical | SRS §5.2 | Given a source link, then each record includes: source_id, file_path, quality, file_size, codec_info, linked_subtitle_paths | data, backend, database |
| Watch history is trigger-only (no playback position) | Story | Critical | SRS §5.2 | Given watch history schema, then only content_id and timestamp are stored — no progress_seconds or duration | data, backend, database |
| Last 10 watched items cached in JSON column | Story | High | SRS §5.2 | Given recently watched display, then last 10 items are stored in a JSON column for fast retrieval | data, backend, database |
| TV series hierarchical schema (Series > Season > Episode) | Story | Critical | SRS §5.2 | Given TV series data, then database has separate tables: contents → seasons → episodes | data, backend, database |
| Shadow table for Phase 1 scan results before batch sync | Story | High | SRS §5.2 | Given Phase 1 scans, then results go to temp shadow table before single batch sync to main table | data, backend, database |
| Health reports store ISP name + source_id + reachability (no IPs) | Story | High | SRS §5.2 | Given source_health_reports table, then it stores ISP name and reachability — no IP addresses | data, backend, database, privacy |
| Alternative titles stored in searchable field on content records | Story | Moderate | SRS §5.2 | Given content records, then TMDb alternative titles are stored in a searchable JSONB field | data, backend, database |
| Confidence score (0–100%) stored on each metadata match | Story | High | SRS §5.2 | Given enrichment, then a confidence_score float field exists on content records — <80% flagged for review | data, backend, database |
| User account data retained indefinitely | Story | High | SRS §5.3 | Given user accounts, then data is retained until user deletes or admin removes | data, backend |
| Content metadata retained indefinitely with periodic sync | Story | High | SRS §5.3 | Given content metadata, then it is retained indefinitely and refreshed via periodic metadata sync | data, backend |
| Source scan logs retained for 90 days | Story | Moderate | SRS §5.3 | Given scan logs, then older than 90 days are archived or deleted | data, backend |
| Health reports retained for 30 days (rolling window) | Story | Moderate | SRS §5.3 | Given health reports, then aggregation uses a 30-day rolling window — older reports are pruned | data, backend |
| Foreign key constraints with ON DELETE CASCADE | Story | Critical | SRS §5.4 | Given user-related tables (watchlists, favorites, ratings), then ON DELETE CASCADE removes dependent rows | data, backend, database |
| Unique constraints on email, tmdb_id, source name, user-content pairs | Story | Critical | SRS §5.4 | Given data integrity, then unique constraints exist on: users.email, contents.tmdb_id, sources.name, (user_id, content_id) | data, backend, database |
| Database indexes on frequently queried columns | Story | High | SRS §5.4 | Given query performance, then indexes exist on: contents.type, contents.year, contents.rating, contents.tmdb_id, user_sources.user_id, source_health_reports.source_id/isp_name | data, backend, database |

---

## Epic: Interface & Integration

| Summary | Issue Type | Priority | SRS Ref | Acceptance Criteria | Labels |
|---------|-----------|----------|---------|-------------------|--------|
| RESTful JSON API with HTTPS (production) and HTTP (development) | Story | Critical | SRS §6.1 | Given API, then it uses RESTful JSON over HTTPS in production and HTTP in development | interface, backend |
| Bearer JWT authentication in Authorization header | Story | Critical | SRS §6.1 | Given authenticated requests, then JWT is sent in Authorization: Bearer {token} header | interface, backend, auth |
| Cursor/offset-based pagination (default 20 items/page) | Story | High | SRS §6.1 | Given list endpoints, then pagination is cursor or offset-based with default 20 items per page | interface, backend |
| Standard error response format (error, message, errors) | Story | High | SRS §6.1 | Given API errors, then response follows: { error: true, message: "...", errors: { field: [...] } } | interface, backend |
| Standard success response format (data, meta) | Story | High | SRS §6.1 | Given API success, then response follows: { data: {}, meta: { current_page, total, per_page } } | interface, backend |
| TMDb API integration (API key, 40 req/10s limit) | Story | Critical | SRS §6.2 | Given TMDb API, then requests use API key as query param and respect 40 req/10s rate limit | interface, backend, external |
| OMDb API integration (API key, 1,000 req/day limit) | Story | High | SRS §6.2 | Given OMDb API, then requests use API key as query param and respect 1,000 req/day limit | interface, backend, external |
| Frontend communicates with backend exclusively via REST API | Story | Critical | SRS §6.3 | Given frontend-backend communication, then all data exchange happens via REST API — no direct DB access | interface, frontend, backend |
| SSG/ISR for public pages (no backend dependency for rendering) | Story | High | SRS §6.3 | Given public pages, then they use SSG/ISR and do not require backend to be running for rendering | interface, frontend |
| Auth token stored in HttpOnly cookie or localStorage | Story | High | SRS §6.3 | Given authentication, then JWT token is stored client-side in HttpOnly cookie or localStorage | interface, frontend, auth |
| Video streaming direct from browser to BDIX FTP (no backend proxy) | Story | Critical | SRS §6.4 | Given video playback, then browser connects directly to FTP server URL — backend never proxies video | interface, frontend |
| Source URLs generated via JavaScript after user interaction | Story | High | SRS §6.4 | Given source URLs, then they are generated client-side via JS after interaction — never in SSR HTML | interface, frontend, security |
| Bridge page provides VLC/PotPlayer protocol handler URLs for incompatible formats | Story | High | SRS §6.4 | Given MKV/DTS/AC3 content, then Bridge page provides vlc:// and potplayer:// protocol handler URLs | interface, frontend, bridge |
| Service Worker caches ISP reachability results for 30 minutes | Story | High | SRS §6.5 | Given Race Strategy results, then Service Worker caches them for 30 min TTL | interface, frontend, sw |
| Service Worker cache invalidated on network change or manual re-test | Story | Moderate | SRS §6.5 | Given network change or manual re-test, then Service Worker cache is invalidated and Race Strategy re-runs | interface, frontend, sw |
| Service Worker prevents browser cache bloat from repeated health checks | Story | Moderate | SRS §6.5 | Given repeated source pings, then Service Worker handles caching to prevent browser cache bloat | interface, frontend, sw |

---

## Epic: Deployment & Infrastructure

| Summary | Issue Type | Priority | SRS Ref | Acceptance Criteria | Labels |
|---------|-----------|----------|---------|-------------------|--------|
| Backend API deployed as PHP-FPM + Nginx on port 8000 | Story | Critical | SRS §7.1 | Given production, then Laravel API runs behind PHP-FPM with Nginx on port 8000 | deploy, backend, infra |
| Frontend deployed as Node.js + PM2 on port 3000 | Story | Critical | SRS §7.1 | Given production, then Next.js frontend runs with PM2 on port 3000 | deploy, frontend, infra |
| Admin Panel deployed as static build + Nginx on port 8080 | Story | Critical | SRS §7.1 | Given production, then Vue.js admin panel is a static build served by Nginx on port 8080 | deploy, admin, infra |
| PostgreSQL 15+ database (managed or self-hosted) | Story | Critical | SRS §7.1 | Given database, then PostgreSQL 15+ is deployed on port 5432 | deploy, database, infra |
| Redis 7+ for cache and job queue | Story | Critical | SRS §7.1 | Given caching and queues, then Redis 7+ is deployed on port 6379 | deploy, cache, infra |
| Queue Worker managed by Supervisor | Story | High | SRS §7.1 | Given queue processing, then Laravel Queue Worker is managed by Supervisor for auto-restart | deploy, backend, infra |
| Enrichment Worker managed by Supervisor (resumable) | Story | High | SRS §7.1 | Given enrichment, then Enricher Worker runs under Supervisor and is resumable on restart | deploy, backend, infra |
| Laravel Scheduler via system cron | Story | High | SRS §7.1 | Given scheduled jobs, then system cron runs `* * * * * php artisan schedule:run` | deploy, backend, infra |
| Image Proxy via external free service (wsrv.nl / Statically) | Story | Moderate | SRS §7.1 | Given poster images, then they are served through wsrv.nl or Statically free tier | deploy, frontend, infra |
| Application logging with Laravel Log (daily rotating files) | Story | High | SRS §7.2 | Given application events, then logs are written to daily rotating log files | deploy, backend, logging |
| Error tracking with Telescope (dev) / Sentry (production) | Story | Moderate | SRS §7.2 | Given errors, then Telescope tracks them in dev and Sentry (optional) in production | deploy, backend, logging |
| Queue monitoring with Horizon or Telescope | Story | Moderate | SRS §7.2 | Given queue workers, then monitoring is available via Laravel Horizon or Telescope | deploy, backend, monitoring |
| External uptime monitoring (UptimeRobot or similar) | Story | Low | SRS §7.2 | Given production, then external monitoring checks uptime and alerts on downtime | deploy, infra, monitoring |
| Source health monitoring via crowdsourced reports in admin | Story | High | SRS §7.2 | Given source health, then admin dashboard displays aggregated crowdsourced health data | deploy, admin, monitoring |
| Enrichment progress visible in admin panel | Story | High | SRS §7.2 | Given enrichment worker, then admin panel shows worker status, queue size, and processing rate | deploy, admin, monitoring |

---

## Epic: Non-Functional — Performance

| Summary | Issue Type | Priority | SRS Ref | Acceptance Criteria | Labels |
|---------|-----------|----------|---------|-------------------|--------|
| Page load time < 2 seconds | Story | High | NFR-PERF-01 | Given Lighthouse testing, then initial page load is under 2 seconds | nfr, performance |
| API response time < 200ms (cached) | Story | High | NFR-PERF-02 | Given a cached endpoint, then response time is under 200ms | nfr, performance, backend |
| API response time < 500ms (uncached) | Story | High | NFR-PERF-03 | Given an uncached endpoint, then response time is under 500ms | nfr, performance, backend |
| Video playback start time < 3 seconds | Story | Critical | NFR-PERF-04 | Given a play action, then video starts within 3 seconds | nfr, performance, frontend |
| Database query average < 100ms | Story | High | NFR-PERF-05 | Given any database query, then average execution time is under 100ms | nfr, performance, backend, database |
| Support 1,000+ concurrent users with Redis caching | Story | High | NFR-PERF-06 | Given high traffic, then system supports 1,000+ concurrent users via Redis | nfr, performance, infrastructure |
| Content list cache TTL: 1 hour | Story | High | NFR-PERF-07 | Given content list endpoints, then responses are cached in Redis for 1 hour | nfr, performance, backend |
| Content detail cache TTL: 24 hours | Story | High | NFR-PERF-08 | Given content detail endpoints, then responses are cached in Redis for 24 hours | nfr, performance, backend |
| User library cache TTL: 5 minutes | Story | Moderate | NFR-PERF-09 | Given user library endpoints (watchlist, favorites), then responses are cached for 5 minutes | nfr, performance, backend |
| ISP source reachability cache TTL: 30 minutes (Service Worker) | Story | High | NFR-PERF-10 | Given Race Strategy results, then Service Worker stores them for 30 min | nfr, performance, frontend |
| Initial enrichment throughput: ~10,000+ files in ~1 hour | Story | Moderate | NFR-PERF-11 | Given first-time enrichment run, then system processes ~10,000+ files within approximately 1 hour | nfr, performance, backend, enricher |

---

## Epic: Non-Functional — Security

| Summary | Issue Type | Priority | SRS Ref | Acceptance Criteria | Labels |
|---------|-----------|----------|---------|-------------------|--------|
| Passwords hashed with bcrypt (cost factor 10+) | Story | Critical | NFR-SEC-01 | Given registration or password change, then password is stored using bcrypt with cost 10+ | nfr, security, backend |
| JWT with short-lived access tokens + refresh tokens | Story | Critical | NFR-SEC-02 | Given auth system, then access tokens are short-lived with long-lived refresh tokens | nfr, security, backend |
| Admin routes protected by admin middleware (role check) | Story | Critical | NFR-SEC-03 | Given admin endpoints, then only users with role=admin can access | nfr, security, backend |
| API rate limiting (60 requests/minute per user) | Story | High | NFR-SEC-04 | Given any client, then requests are throttled at 60/minute | nfr, security, backend |
| CORS allows only frontend and admin panel origins | Story | High | NFR-SEC-05 | Given API, then CORS only allows bdflix.com and admin.bdflix.com origins | nfr, security, backend |
| TMDb/OMDb API keys never exposed to frontend | Story | Critical | NFR-SEC-06 | Given TMDb/OMDb keys, then they exist only in .env and are never sent to client | nfr, security, backend |
| FTP source credentials (Emby tokens) never exposed to frontend | Story | Critical | NFR-SEC-07 | Given Emby API tokens, then they exist only on backend and are never sent to client | nfr, security, backend |
| Time-limited signed URLs for authenticated FTP sources | Story | High | NFR-SEC-08 | Given authenticated FTP sources, then backend generates signed URLs with expiry | nfr, security, backend |
| All user input validated via Laravel Form Requests | Story | Critical | NFR-SEC-09 | Given any API endpoint accepting input, then Laravel FormRequest validates all fields | nfr, security, backend |
| SQL injection prevention via Eloquent ORM parameterized queries | Story | Critical | NFR-SEC-10 | Given database queries, then all are executed via Eloquent ORM with parameterized queries | nfr, security, backend |
| BDIX source links generated via JavaScript only (never in SSR HTML) | Story | High | NFR-SEC-11 | Given content pages, then BDIX URLs are never in server-rendered HTML | nfr, security, frontend |
| robots.txt blocks /play/ and /source/ routes | Story | High | NFR-SEC-12 | Given robots.txt, then /play/ and /source/ are disallowed | nfr, security, seo |
| Health reports exclude user IP addresses (privacy) | Story | Critical | NFR-SEC-13 | Given health reports, then only ISP name and reachability status are sent — no IP | nfr, security, privacy |
| Referer validation on image proxy and backend | Story | Low | NFR-SEC-14 | Given requests from non-platform domains, then image proxy and backend return 403 | nfr, security |

---

## Epic: Non-Functional — Reliability & Availability

| Summary | Issue Type | Priority | SRS Ref | Acceptance Criteria | Labels |
|---------|-----------|----------|---------|-------------------|--------|
| Gracefully handle BDIX source downtime without crashing | Story | Critical | NFR-REL-01 | Given a BDIX source down, then system continues serving content from other sources without crash | nfr, reliability, backend |
| Video player auto-fallback to alternative source on failure | Story | High | NFR-REL-02 | Given a stream failure, then player automatically tries the next available source | nfr, reliability, frontend |
| Failed scan jobs retry up to 3 times with exponential backoff | Story | High | NFR-REL-03 | Given a scan job failure, then it retries up to 3 times with increasing delay | nfr, reliability, backend |
| Source health scores updated based on crowdsourced reports (consensus) | Story | High | NFR-REL-04 | Given user health reports, then source health scores are consensus-based | nfr, reliability, backend |
| Log all errors for debugging (Laravel Log / Telescope) | Story | High | NFR-REL-05 | Given any error, then it is logged to Laravel daily log and optionally Telescope | nfr, reliability, backend, logging |
| Enrichment worker is resumable on restart | Story | High | NFR-REL-06 | Given worker crash or restart, then it resumes from the next "pending" entry | nfr, reliability, backend, enricher |
| Silent re-scan and path auto-update on 404 during playback | Story | High | NFR-REL-07 | Given a 404 error during playback, then system re-scans and auto-updates the path | nfr, reliability, backend |
| Auto-prune source links unreachable by 100% of users for 30+ days | Story | Moderate | NFR-REL-08 | Given a source link unreachable by all users for 30+ consecutive days, then auto-deleted | nfr, reliability, backend, cleanup |

---

## Epic: Non-Functional — Scalability

| Summary | Issue Type | Priority | SRS Ref | Acceptance Criteria | Labels |
|---------|-----------|----------|---------|-------------------|--------|
| Database schema supports new sources without schema changes (JSONB) | Story | High | NFR-SCAL-01 | Given a new BDIX source, then it can be added without migration — config in JSONB | nfr, scalability, backend, database |
| Modular scraper architecture (BaseScraperInterface) | Story | Critical | NFR-SCAL-02 | Given a new source, then a scraper is added by implementing BaseScraperInterface | nfr, scalability, backend |
| Queue-based scanning (non-blocking) | Story | Critical | NFR-SCAL-03 | Given scan execution, then scanning runs in queue workers and never blocks the API | nfr, scalability, backend |
| Redis caching for read-heavy endpoints | Story | High | NFR-SCAL-04 | Given read-heavy endpoints, then all are cached in Redis | nfr, scalability, backend, performance |
| Two-phase scanning decouples collection from enrichment | Story | Critical | NFR-SCAL-05 | Given scanning, then Phase 1 and Phase 2 run independently | nfr, scalability, backend |

---

## Epic: Non-Functional — Usability

| Summary | Issue Type | Priority | SRS Ref | Acceptance Criteria | Labels |
|---------|-----------|----------|---------|-------------------|--------|
| Fully responsive frontend (mobile/tablet/desktop) | Story | Critical | NFR-USE-01 | Given any device, then layout adapts: mobile (0–640px), tablet (641–1024px), desktop (1025px+) | nfr, usability, frontend |
| Responsive content grid (2/3-4/5-6 columns) | Story | High | NFR-USE-02 | Given the content grid, then columns adjust per breakpoint | nfr, usability, frontend |
| Touch gesture support in video player on mobile | Story | Moderate | NFR-USE-03 | Given mobile playback, then player supports touch gestures | nfr, usability, frontend, mobile |
| Hover states and loading indicators on interactive elements | Story | High | NFR-USE-04 | Given any button/link/toggle, then hover states and spinners are present | nfr, usability, frontend |
| Skeleton loaders during data fetching | Story | High | NFR-USE-05 | Given data loading, then skeleton loaders are displayed | nfr, usability, frontend |
| User-friendly error messages (no raw error codes) | Story | High | NFR-USE-06 | Given any error, then a human-readable message is shown | nfr, usability, frontend |
| Bridge page with clear explanation and actionable alternatives | Story | High | NFR-USE-07 | Given Bridge page, then it explains why playback failed and offers alternatives | nfr, usability, frontend |
| Multi-part movies: single poster with Part toggles | Story | High | NFR-USE-08 | Given a multi-part movie, then one poster with part toggle buttons | nfr, usability, frontend |

---

## Epic: Non-Functional — Maintainability

| Summary | Issue Type | Priority | SRS Ref | Acceptance Criteria | Labels |
|---------|-----------|----------|---------|-------------------|--------|
| Code follows Laravel, Next.js, and Vue.js conventions | Story | High | NFR-MAIN-01 | Given code review, then code adheres to framework-specific best practices | nfr, maintainability |
| Each scraper is a separate, independently modifiable module | Story | Critical | NFR-MAIN-02 | Given a scraper change, then only that module is modified | nfr, maintainability, backend |
| Environment-specific values in .env files, not hardcoded | Story | Critical | NFR-MAIN-03 | Given any config value, then it is in .env, never hardcoded | nfr, maintainability |
| Database changes use versioned migrations | Story | Critical | NFR-MAIN-04 | Given schema changes, then versioned Laravel migrations are used | nfr, maintainability, backend, database |
| Consistent JSON structure for all API responses | Story | High | NFR-MAIN-05 | Given any response, then it follows standard format: {data, meta} or {error, message} | nfr, maintainability, backend |

---

## Epic: Non-Functional — Compatibility

| Summary | Issue Type | Priority | SRS Ref | Acceptance Criteria | Labels |
|---------|-----------|----------|---------|-------------------|--------|
| Frontend supports Chrome 90+, Firefox 90+, Safari 15+, Edge 90+ | Story | Critical | NFR-COMP-01 | Given supported browsers, then all features work correctly | nfr, compatibility, frontend |
| In-browser player supports MP4 (H.264 + AAC/MP3) and HLS | Story | Critical | NFR-COMP-02 | Given compatible formats, then in-browser player handles MP4 and HLS | nfr, compatibility, frontend |
| MKV/DTS/AC3 supported via Playback Bridge (VLC/PotPlayer) | Story | Critical | NFR-COMP-03 | Given incompatible formats, then Bridge page provides deep links | nfr, compatibility, frontend, bridge |
| Backend runs on PHP 8.2+, PostgreSQL 15+, Redis 7+ | Story | Critical | NFR-COMP-04 | Given server setup, then backend is compatible with required versions | nfr, compatibility, backend |
| Frontend uses Next.js 14 with App Router (TypeScript) | Story | Critical | NFR-COMP-05 | Given frontend, then it is built with Next.js 14 App Router and TypeScript | nfr, compatibility, frontend |

---

## Summary

**Total Stories: 241**

| Epic | Story Count |
|------|------------|
| Authentication & User Management | 7 |
| Content Browsing & Search | 17 |
| Video Streaming & Playback | 26 |
| User Library | 11 |
| ISP Source Availability (Race Strategy) | 11 |
| Content Scanning — Phase 1 (Collector) | 12 |
| Content Scanning — Phase 2 (Enricher) | 25 |
| Source Scrapers | 8 |
| Admin Panel | 21 |
| Data Architecture & Schema | 16 |
| Interface & Integration | 16 |
| Deployment & Infrastructure | 15 |
| Non-Functional — Performance | 11 |
| Non-Functional — Security | 14 |
| Non-Functional — Reliability & Availability | 8 |
| Non-Functional — Scalability | 5 |
| Non-Functional — Usability | 8 |
| Non-Functional — Maintainability | 5 |
| Non-Functional — Compatibility | 5 |
| **Total** | **241** |
