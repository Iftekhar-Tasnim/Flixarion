# Flixarion — Business Requirements Document (BRD)

> **SCOPE NOTE: This repository (Flixarion) is strictly the Laravel Backend API. All Frontend (Next.js) and Admin Panel (Vue.js) components have been moved to separate repositories and their implementation scope is excluded from this codebase.**


**Version**: 1.0  
**Date**: 2026-02-17  
**Author**: Iftekhar Tasnim  
**Status**: Draft

---

## 1. Executive Summary

Flixarion is a free, open-access movie and TV series streaming platform designed for Bangladesh users connected to BDIX (Bangladesh Internet Exchange) networks. The platform aggregates content from multiple BDIX FTP servers — which are legally and freely accessible through local ISPs — and presents them through a modern, Netflix-like interface with rich metadata, search, and personalization features.

This is a solo community-service and portfolio project with no monetization plans.

---

## 2. Business Objectives

| # | Objective | Success Indicator |
|---|-----------|-------------------|
| 1 | Provide a unified, user-friendly streaming experience from fragmented BDIX FTP sources | Users can discover, browse, and stream content from a single platform |
| 2 | Serve the Bangladesh BDIX community with free access to locally available content | Platform publicly accessible to all BDIX-connected users |
| 3 | Demonstrate full-stack engineering capabilities as a portfolio project | Complete, deployed, production-quality system |
| 4 | Eliminate the need for users to manually navigate individual FTP servers | Content is aggregated, searchable, and enriched with metadata |

---

## 3. Stakeholders

| Stakeholder | Role | Responsibilities |
|-------------|------|-----------------|
| **Iftekhar Tasnim** | Project Owner, Solo Developer | All design, development, deployment, and maintenance |
| **End Users** | BDIX-connected viewers in Bangladesh | Consume content, provide feedback |
| **BDIX FTP Server Operators** | Content source providers | Operate and maintain FTP servers (external, not managed by Flixarion) |
| **TMDb / OMDb** | Metadata providers | Supply movie/series metadata via public APIs |

---

## 4. Target Audience

### Primary Users
- **Bangladesh residents** on ISPs with BDIX peering (e.g., Carnival, Dot, Amber IT, Sam Online, etc.)
- Users who currently navigate individual FTP servers manually to find movies/series
- Age group: 16–40, tech-savvy, comfortable with web-based streaming

### User Personas

**Persona 1: Casual Viewer (Rahim)**
- 22-year-old college student
- Has BDIX access through his ISP
- Currently browses FTP servers manually, often can't find what he wants
- Wants a Netflix-like experience to browse and stream movies easily

**Persona 2: Binge Watcher (Nusrat)**
- 28-year-old professional
- Watches TV series regularly
- Wants to track her progress, maintain a watchlist, and continue watching from where she left off
- Will register for an account for personalization features

**Persona 3: Quick Browser (Kamal)**
- 35-year-old casual user
- Occasionally watches a movie on weekends
- Does not want to register — just wants to find a movie and watch it immediately
- Will use the platform as a guest

---

## 5. Scope

### 5.1 In Scope

| Area | Description |
|------|-------------|
| **Content aggregation** | Automated scanning and indexing of 7 BDIX FTP sources |
| **Content types** | Movies and TV series (with seasons and episodes) |
| **Metadata enrichment** | Automated enrichment via TMDb (primary) and OMDb (fallback) APIs |
| **User streaming** | Direct video streaming from FTP servers to browser |
| **Guest access** | Full browse and watch capabilities without registration |
| **Registered user features** | Watchlist, favorites, watch history, continue watching |
| **ISP-based source detection** | Client-side detection of which FTP sources are accessible |
| **Admin panel** | Source management, content management, user management, analytics |
| **Multi-quality support** | Quality selection (480p, 720p, 1080p, 2160p) per source |
| **Source fallback** | Automatic fallback to alternative source on stream failure |
| **Responsive web app** | Mobile, tablet, and desktop support |

### 5.2 Out of Scope (Current Version)

| Area | Rationale |
|------|-----------|
| Monetization / ads | Free platform, no revenue model planned |
| Mobile apps (iOS/Android) | Post-launch enhancement |
| User-generated content | Platform is a content aggregator, not a hosting service |
| Content hosting / storage | Flixarion does not host any content — streams directly from FTP |
| Live streaming | Only pre-recorded content (movies/series) |
| Social features | Sharing, comments, watch parties — future enhancements |
| Ratings & reviews | Post-MVP feature |
| Recommendation engine | Post-MVP, requires usage data first |
| Multi-language UI | English-only initially |
| Payment processing | No monetization |

---

## 6. Business Requirements

### BR-01: Content Discovery & Aggregation (Intelligent Parser & Matcher)

| ID | Requirement | Priority |
|----|-------------|----------|
| BR-01.1 | The system shall aggregate content from at least 5 BDIX FTP sources | Must Have |
| BR-01.2 | Content shall be automatically scanned and indexed on a scheduled basis (every 6 hours) | Must Have |
| BR-01.3 | Content scanning shall be split into two phases: Phase 1 (Collector) for fast file indexing, and Phase 2 (Enricher) for metadata enrichment | Must Have |
| BR-01.4 | Phase 1 (Collector) shall crawl all BDIX sources and save raw file paths and filenames to the database with status "pending" without making any API calls | Must Have |
| BR-01.5 | Phase 1 shall only create database entries for files with valid video extensions (.mp4, .mkv, .avi, .m3u8); empty directories shall be ignored | Must Have |
| BR-01.6 | Phase 1 shall use character encoding auto-detection (e.g., chardet library) to handle UTF-8 and Windows-1252 encoded FTP directory listings | Must Have |
| BR-01.7 | Phase 1 scans shall write to a temporary "shadow" table; once complete, perform a single batch sync with the main table to prevent UI sluggishness | Must Have |
| BR-01.8 | During Phase 1 crawl, the system shall also scan for subtitle files (.srt, .vtt) in the same directory as video files | Must Have |
| BR-01.9 | Subtitle files with filenames matching >60% similarity to the video filename shall be automatically linked as subtitle tracks | Must Have |
| BR-01.10 | Phase 1 shall detect multi-part movies (CD1/CD2, Part1/Part2) by identifying "CD" or "Part" keywords in filenames | Must Have |
| BR-01.11 | Multi-part movie files shall be linked to a single content record with part sequencing information | Must Have |
| BR-01.12 | Phase 2 (Enricher) shall run as a background worker that processes "pending" entries from the database one at a time | Must Have |
| BR-01.13 | Before querying metadata APIs, the Enricher shall normalize filenames using a parser library (e.g., PTN) to extract clean title, year, quality, and codec information | Must Have |
| BR-01.14 | For TV series files, the parser shall extract season and episode numbers (SxxExx format) from filenames | Must Have |
| BR-01.15 | The parser shall specifically treat folders/files containing "Special," "Extra," or "S00" as Season 0 to properly map TV series specials | Must Have |
| BR-01.16 | The normalization engine shall strip out "noise" tokens (resolution, codec, release group, file extension) from filenames | Must Have |
| BR-01.17 | The Enricher shall use fuzzy matching (e.g., Levenshtein distance) when searching TMDb/OMDb APIs to handle misspellings and variations | Must Have |
| BR-01.18 | Each metadata match shall be assigned a confidence score (0-100%); matches below 80% confidence shall be flagged as "Low Confidence" | Must Have |
| BR-01.19 | Content with confidence scores below 80% or no match found shall be placed in an "Admin Review Queue" | Must Have |
| BR-01.20 | The system shall use TMDb ID as the unique identifier (anchor) for each movie/series to prevent duplicate entries | Must Have |
| BR-01.21 | If multiple files from different sources match the same TMDb ID, they shall be linked to a single unified content record | Must Have |
| BR-01.22 | Each unified content record shall have one set of metadata (poster, description, cast, genres) and multiple source links | Must Have |
| BR-01.23 | When fetching metadata, the Enricher shall also store TMDb's "Alternative Titles" in a searchable field to support local nicknames and aliases | Should Have |
| BR-01.24 | Content with "In-Theater" or "Early Release" status shall be flagged for lazy enrichment re-trigger | Should Have |
| BR-01.25 | Flagged early release content shall have metadata re-verified every 7 days for the first month to update low-res placeholders with high-res assets | Should Have |
| BR-01.26 | For TV series, the database shall use a hierarchical structure: Series > Season > Episode | Must Have |
| BR-01.27 | Each episode record shall store: TMDb ID, season number, episode number, episode title, and multiple source links | Must Have |
| BR-01.28 | Source links for episodes shall be filtered by matching TMDb ID + Season Number + Episode Number | Must Have |
| BR-01.29 | Each source link shall store: source ID, file path, quality (480p/720p/1080p/4K), file size, codec information, and linked subtitle paths | Must Have |
| BR-01.30 | The Enricher shall enforce a rate limit of 3 requests per second (or configurable) to stay within TMDb API limits (40 requests per 10 seconds) | Must Have |
| BR-01.31 | The Enricher shall implement exponential backoff and retry logic when API rate limits are hit | Must Have |
| BR-01.32 | The Enricher shall detect and respect "429 Too Many Requests" responses and honor the "Retry-After" header from the API | Must Have |
| BR-01.33 | The Enricher shall support priority-based processing, enriching recently added content first before processing older entries | Should Have |
| BR-01.34 | The Enricher shall be resumable — if interrupted, it shall continue from the next "pending" entry on restart | Must Have |
| BR-01.35 | Content shall be enriched with metadata (poster, description, cast, genres, ratings) from external APIs for high-confidence matches | Must Have |
| BR-01.36 | Poster images shall be served through a lightweight image proxy (e.g., wsrv.nl, Statically) to cache and resize images | Should Have |
| BR-01.37 | The image proxy shall validate the HTTP Referer header to only serve requests from the platform's domain to prevent hotlinking | Should Have |
| BR-01.38 | Admin shall be able to manually trigger content scans (Phase 1 only, Phase 2 runs automatically) | Must Have |
| BR-01.39 | The system shall log all unmatched and low-confidence content with original filenames for admin review | Must Have |
| BR-01.40 | Admin shall be able to manually correct metadata matches from the review queue with a single-click approval | Should Have |
| BR-01.41 | Source links reported as "Unreachable" by 100% of users for more than 30 consecutive days shall be automatically pruned from the database | Should Have |

### BR-02: Content Browsing & Search

| ID | Requirement | Priority |
|----|-------------|----------|
| BR-02.1 | Users shall be able to browse all available movies and TV series | Must Have |
| BR-02.2 | Users shall be able to search content by title | Must Have |
| BR-02.3 | The search functionality shall query both TMDb movie and TV endpoints simultaneously to ensure comprehensive results | Must Have |
| BR-02.4 | Search shall also query the "Alternative Titles" field to support local nicknames and aliases (e.g., "Hobbs and Shaw" for "Fast & Furious Presents: Hobbs & Shaw") | Should Have |
| BR-02.5 | Search results shall be merged and displayed in a unified view with clear badges indicating content type (Movie vs. TV Series) | Must Have |
| BR-02.6 | Users shall be able to filter content by type (movie/series), genre, and year | Must Have |
| BR-02.7 | Homepage shall display trending, popular, and recently added content | Must Have |
| BR-02.8 | Content detail pages shall show poster, backdrop, description, cast, genres, rating, runtime, and trailer | Must Have |
| BR-02.9 | For TV series detail pages, the system shall display season and episode listings with episode titles and thumbnails | Should Have |
| BR-02.10 | BDIX source links shall be generated via JavaScript after user interaction to keep them hidden from search engine crawlers | Must Have |
| BR-02.11 | The robots.txt file shall prevent crawling of /play/ and /source/ routes to avoid indexing private BDIX IPs | Must Have |

### BR-03: Video Streaming

| ID | Requirement | Priority |
|----|-------------|----------|
| BR-03.1 | Users shall be able to stream content directly from BDIX FTP servers in-browser | Must Have |
| BR-03.2 | The system shall support multiple video formats (MKV, MP4, m3u8/HLS) | Must Have |
| BR-03.3 | The system shall perform a pre-flight check on video files to detect container format (MP4/MKV) and audio codec (AAC/MP3/DTS/AC3) before playback | Must Have |
| BR-03.4 | If a video file uses browser-incompatible formats (MKV container or DTS/AC3 audio), the system shall redirect users to a "Playback Bridge" page instead of attempting in-browser playback | Must Have |
| BR-03.5 | The Bridge page shall explain why the video cannot play in-browser and provide prominent "Launch in VLC" and "Launch in PotPlayer" buttons using protocol handlers (vlc:// and potplayer://) | Must Have |
| BR-03.6 | When a user clicks a protocol handler button (VLC/PotPlayer), the system shall use a 2-second timeout check to detect if the app opened | Must Have |
| BR-03.7 | If the page visibility doesn't change within 2 seconds (app didn't open), show a pop-up: "VLC isn't opening. [Download VLC] or try [Direct Download]" | Must Have |
| BR-03.8 | If a mobile device is detected, the system shall prioritize "Open in VLC Mobile" or "Play in MX Player" buttons over the browser player for battery efficiency | Must Have |
| BR-03.9 | The Bridge page shall include a direct download link as a fallback for users without external player support | Must Have |
| BR-03.10 | The Bridge page shall include a troubleshooting section explaining how to allow mixed content (HTTP sources on HTTPS site) if applicable | Must Have |
| BR-03.11 | When multiple sources are available for the same content, the system shall automatically select the "Best Quality" source based on: (1) reachability from user's ISP, (2) highest quality (4K > 1080p > 720p > 480p) | Must Have |
| BR-03.12 | Users shall be able to manually override the automatic source selection via a "Select Source" dropdown showing all available sources with quality labels | Must Have |
| BR-03.13 | For multi-part movies (CD1/CD2, Part1/Part2), the UI shall show a single poster with "Part 1" and "Part 2" toggles in the source selection menu | Must Have |
| BR-03.14 | The source dropdown shall indicate which sources are currently reachable from the user's network | Should Have |
| BR-03.15 | If a file returns a 404 Not Found error during playback attempt, the system shall trigger a silent re-scan of that specific source for that specific filename | Must Have |
| BR-03.16 | If the file is found at a new path during re-scan, the system shall automatically update the database path and retry playback | Must Have |
| BR-03.17 | If the file cannot be found during re-scan, the system shall mark that source link as "broken" and attempt the next available source | Must Have |
| BR-03.18 | If the selected source fails during playback, the player shall automatically switch to the next best available source | Must Have |
| BR-03.19 | Users shall be able to select subtitles when available | Must Have |
| BR-03.20 | The video player shall load linked subtitle tracks (.srt, .vtt) that were discovered during the crawl phase | Must Have |
| BR-03.21 | The player shall detect subtitle character encoding; if not UTF-8, use an encoding-converter library to re-encode ANSI/Bijoy subtitles to Unicode | Must Have |
| BR-03.22 | For VLC/PotPlayer deep links, subtitle files in the same directory as the video shall be automatically loaded by the external player | Should Have |

### BR-04: Guest Access

| ID | Requirement | Priority |
|----|-------------|----------|
| BR-04.1 | Unregistered (guest) users shall be able to browse all content | Must Have |
| BR-04.2 | Guest users shall be able to search and filter content | Must Have |
| BR-04.3 | Guest users shall be able to stream/watch any content | Must Have |
| BR-04.4 | Guest users shall NOT have access to personalization features (watchlist, favorites, history) | Must Have |

### BR-05: Registered User Features (Event-Driven Batching)

| ID | Requirement | Priority |
|----|-------------|----------|
| BR-05.1 | Users shall be able to register with name, email, and password | Must Have |
| BR-05.2 | Registered users shall be able to add/remove content from their watchlist | Must Have |
| BR-05.3 | Registered users shall be able to mark content as favorites/bookmarks | Must Have |
| BR-05.4 | Watchlist and favorites toggle actions shall be debounced with a 1-second delay before writing to the database | Must Have |
| BR-05.5 | If a user toggles the same action multiple times within the debounce window, only the final state shall be saved | Must Have |
| BR-05.6 | The system shall track watch history by recording a single entry when a user clicks "Play" (in-browser or external player) | Must Have |
| BR-05.7 | History entries shall be trigger-only (one entry per play session) without tracking playback position or duration | Must Have |
| BR-05.8 | The user's last 10 watched items shall be cached in a JSON column or localStorage for fast "Recently Watched" display | Must Have |
| BR-05.9 | Users shall be able to manually mark content as "Completed" using a checkmark icon on the movie card | Should Have |
| BR-05.10 | Completed items shall be moved from "Recently Watched" to a "Finished" archive to keep the library organized | Should Have |
| BR-05.11 | Registered users shall be able to view their complete watch history | Should Have |
| BR-05.12 | Registered users shall be able to rate and review content | Could Have (Post-MVP) |

### BR-06: ISP Source Availability (Dynamic Source Discovery)

| ID | Requirement | Priority |
|----|-------------|----------|
| BR-06.1 | On app load, the system shall perform a "Race Strategy" background check by simultaneously pinging a small health file (e.g., favicon or 1KB test file) on all BDIX sources | Must Have |
| BR-06.2 | Sources that respond within a strict timeout (1.5 seconds) shall be marked as "Online/Peered"; those that timeout or fail shall be marked as "Unreachable" | Must Have |
| BR-06.3 | The UI shall automatically hide or deprioritize content that only exists on unreachable sources | Must Have |
| BR-06.4 | For content available on multiple sources, the system shall prioritize the fastest-responding source based on ping results | Must Have |
| BR-06.5 | Source availability status shall be cached in the browser for the session duration to avoid repeated checks | Must Have |
| BR-06.6 | After performing source reachability checks, the browser shall send an anonymous report to the backend with: user's ISP (detected or self-reported), source IDs, and reachability status | Must Have |
| BR-06.7 | The backend shall aggregate reachability reports from all users to build a consensus view of source health | Must Have |
| BR-06.8 | Users shall be able to manually re-test source accessibility at any time, triggering a fresh "Race Strategy" check | Should Have |
| BR-06.9 | Users shall be able to view which sources are currently reachable from their network | Should Have |
| BR-06.10 | Users shall be able to manually override and set a preferred source for playback | Should Have |

### BR-07: Administration (Crowdsourced Health Monitoring)

| ID | Requirement | Priority |
|----|-------------|----------|
| BR-07.1 | Admin shall be able to manage (add/edit/delete/enable/disable) BDIX sources | Must Have |
| BR-07.2 | Admin shall be able to view aggregated source health status based on crowdsourced user reports | Must Have |
| BR-07.3 | Admin shall be able to view source health broken down by ISP (e.g., "Source X: 95% reachable on Carnival, 10% on Dot") | Must Have |
| BR-07.4 | Admin shall be able to distinguish between "Globally Offline" sources (all users report down) and "ISP-Specific Outages" (only certain ISPs report down) | Must Have |
| BR-07.5 | Admin shall be able to view scan logs and manually trigger content scans | Must Have |
| BR-07.6 | Admin shall be able to manage content (view, delete, force metadata sync) | Must Have |
| BR-07.7 | Admin shall have access to an "Admin Review Queue" showing all low-confidence and unmatched content | Must Have |
| BR-07.8 | Admin shall be able to approve, correct, or reject metadata matches from the review queue with one-click actions | Must Have |
| BR-07.9 | Admin shall be able to view the status of the metadata enrichment worker (running/paused, queue size, processing rate) | Should Have |
| BR-07.10 | Admin shall be able to manually pause/resume the enrichment worker | Should Have |
| BR-07.11 | Admin shall have a dashboard with key metrics (users, content count, source status, review queue size, enrichment progress) | Should Have |
| BR-07.12 | Admin shall be able to view user management (list, ban/unban) | Should Have |
| BR-07.13 | Admin shall have access to analytics (most watched, user trends, source usage, matching accuracy, API usage, ISP distribution) | Could Have |

---

## 7. Business Rules

| # | Rule |
|---|------|
| 1 | Guest users can browse and watch content without restrictions |
| 2 | Only registered users can save watchlists, favorites, and watch history |
| 3 | Registration requires a valid email and password |
| 4 | Admin role is manually assigned — no self-registration for admin access |
| 5 | Content is never hosted by Flixarion — all streaming is direct from BDIX FTP servers |
| 6 | If a source becomes unreachable, its health score decreases; if it drops below threshold, it is auto-disabled |
| 7 | Content metadata is synced from TMDb as primary source; OMDb is used as a fallback only when TMDb returns no results |
| 8 | Automated scans run every 6 hours; admin can trigger manual scans at any time |
| 9 | Watch history is recorded as a single trigger event when the user clicks "Play" without tracking playback position |
| 10 | All BDIX FTP sources are legally and freely accessible through Bangladesh ISPs |
| 11 | Before displaying a "Play" button, the system shall perform a pre-flight check to determine if the video format is browser-compatible |
| 12 | If a video uses MKV container or DTS/AC3 audio codecs, users shall be redirected to a Bridge page instead of attempting in-browser playback |
| 13 | The Bridge page shall provide VLC and PotPlayer deep links (protocol handlers) as the primary playback method for incompatible formats |
| 14 | Direct download links shall always be available as a fallback option for all content |
| 15 | On app initialization, the frontend shall perform a "Race Strategy" ping to all BDIX sources to determine reachability from the user's ISP |
| 16 | Only sources responding within 1.5 seconds shall be considered "peered" and used for content delivery |
| 17 | Content shall be automatically filtered or hidden if it only exists on unreachable sources for the current user |
| 18 | All filenames shall be normalized before metadata API queries to extract clean title and year information |
| 19 | Fuzzy matching shall be used for all metadata searches to handle filename variations and misspellings |
| 20 | Metadata matches with confidence scores below 80% shall be flagged for admin review rather than auto-published |
| 21 | The admin review queue shall allow one-click approval or correction of flagged content |
| 22 | Content scanning shall be decoupled into two phases: fast collection (no API calls) and slow enrichment (rate-limited API calls) |
| 23 | The metadata enrichment worker shall process entries sequentially with a 3-second delay between API requests |
| 24 | The enrichment worker shall be resumable and continue from the last "pending" entry after interruptions |
| 25 | The enrichment worker shall prioritize recently added content to ensure new movies appear with metadata quickly |
| 26 | Watchlist and favorites actions shall be debounced with a 1-second delay to reduce unnecessary database writes |
| 27 | Watch history shall be recorded only once per play session (trigger-only) when the user clicks "Play" |
| 28 | The user's last 10 watched items shall be cached for fast retrieval without complex JOIN queries |
| 29 | User browsers shall send anonymous reachability reports to the backend after performing source health checks |
| 30 | The admin panel shall use crowdsourced reports to determine source health, as the admin server cannot directly access BDIX IPs |
| 31 | Source health shall be determined by consensus: if all users report a source down, it is "Globally Offline"; if only specific ISPs report down, it is an "ISP-Specific Outage" |
| 32 | TMDb ID shall be used as the unique anchor to prevent duplicate content entries across multiple sources |
| 33 | Multiple files from different sources matching the same TMDb ID shall be linked to a single unified content record |
| 34 | The system shall automatically select the best quality source based on reachability and quality ranking (4K > 1080p > 720p > 480p) |
| 35 | For TV series, the parser shall extract season and episode numbers (SxxExx format) during the enrichment phase |
| 36 | TV series shall use a hierarchical database structure: Series > Season > Episode |
| 37 | When a user plays a specific episode, the system shall filter source links by TMDb ID + Season Number + Episode Number |
| 38 | Search queries shall simultaneously query both TMDb /movie and /tv endpoints to ensure comprehensive results |
| 39 | Search results shall be merged and displayed with clear content type badges (Movie vs. TV Series) to avoid user confusion |
| 40 | If a file link returns 404 Not Found, the system shall trigger a silent re-scan of that source for that filename |
| 41 | If a file is found at a new path during re-scan, the database shall be automatically updated for all users |
| 42 | Broken links that cannot be re-discovered shall be marked as "broken" and excluded from source selection |
| 43 | During Phase 1 crawl, the system shall scan for subtitle files (.srt, .vtt) in the same directory as video files |
| 44 | Subtitle files with >60% filename similarity to the video file shall be automatically linked as subtitle tracks |
| 45 | VLC and PotPlayer deep links automatically load subtitle files if they are in the same directory as the video |
| 46 | Phase 1 shall only create database entries for files with valid video extensions; empty directories shall be ignored |
| 47 | Phase 1 shall use character encoding auto-detection to handle UTF-8 and Windows-1252 encoded FTP listings |
| 48 | Multi-part movies (CD1/CD2, Part1/Part2) shall be linked to a single content record with part sequencing |
| 49 | Content with "In-Theater" or "Early Release" status shall have metadata re-verified every 7 days for the first month |
| 50 | Poster images shall be served through a lightweight image proxy to cache and resize images |
| 51 | Source accessibility results shall be cached in a Service Worker for 30 minutes to prevent browser cache bloat |
| 52 | Anonymous health reports shall NOT include user's full local IP address, only ISP name and reachability status |
| 53 | Mobile users shall be prioritized toward VLC Mobile/MX Player over browser playback for battery efficiency |
| 54 | TV series specials (interviews, behind-the-scenes) shall be mapped to Season 0 using "Special," "Extra," or "S00" keywords |
| 55 | TMDb's "Alternative Titles" shall be stored in a searchable field to support local nicknames and aliases |
| 56 | When a user clicks a VLC/PotPlayer protocol handler, a 2-second timeout shall detect if the app opened |
| 57 | If the protocol handler fails to open an app, a pop-up shall guide the user to download the player or use direct download |
| 58 | Subtitle character encoding shall be detected; ANSI/Bijoy subtitles shall be re-encoded to Unicode for browser display |
| 59 | Users shall manually mark content as "Completed" to organize their "Recently Watched" list |
| 60 | The image proxy and backend shall validate HTTP Referer headers to prevent hotlinking from copycat sites |
| 61 | Source links unreachable by 100% of users for 30+ consecutive days shall be automatically pruned |
| 62 | BDIX source links shall be generated via JavaScript after user interaction to hide them from search engine crawlers |
| 63 | robots.txt shall prevent crawling of /play/ and /source/ routes to avoid indexing private BDIX IPs |
| 64 | Phase 1 scans shall use shadow table indexing to prevent UI sluggishness during database upserts |

---

## 8. Assumptions

| # | Assumption |
|---|-----------|
| 1 | BDIX FTP servers will remain accessible and operational for the foreseeable future |
| 2 | TMDb and OMDb APIs will remain available with free-tier access |
| 3 | Users will primarily access the platform from Bangladesh ISPs with BDIX connectivity |
| 4 | Content on BDIX FTP servers is legally accessible within Bangladesh |
| 5 | BDIX FTP servers use consistent enough file naming for automated parsing |
| 6 | Most modern browsers (Chrome, Safari, Edge) do NOT natively support MKV containers or AC3/DTS audio codecs — external players (VLC, PotPlayer) are required for such content |
| 7 | Users have or are willing to install VLC Player or PotPlayer for high-fidelity content playback |
| 8 | The platform will be deployed on infrastructure accessible from Bangladesh |
| 9 | BDIX FTP servers typically serve content over HTTP (not HTTPS), which may trigger mixed content warnings on HTTPS sites |
| 10 | BDIX peering is fragmented — users on different ISPs will have access to different subsets of BDIX sources |
| 11 | Each BDIX source hosts a small, accessible health check file (favicon, robots.txt, or similar) for reachability testing |
| 12 | Network latency to reachable BDIX sources is typically under 1.5 seconds for successful connections |
| 13 | BDIX FTP filenames follow common naming conventions (title, year, quality, codec) that can be parsed with regex/parser libraries |
| 14 | TMDb and OMDb APIs support fuzzy/partial title searches that can handle minor variations |
| 15 | A confidence threshold of 80% provides a reasonable balance between automation and accuracy for metadata matching |
| 16 | TMDb API rate limit is 40 requests per 10 seconds; a 3-second delay between requests (3 req/s) stays safely within limits |
| 17 | The metadata enrichment process can run asynchronously in the background without blocking user access |
| 18 | A one-time enrichment of 10,000+ files completing in ~1 hour is acceptable for initial library setup |
| 19 | Users typically do not toggle watchlist/favorites rapidly; a 1-second debounce window is sufficient to catch accidental double-clicks |
| 20 | Tracking "play" events (not playback position) provides sufficient history functionality for most users |
| 21 | Caching the last 10 watched items covers the majority of "Recently Watched" use cases |
| 22 | Admin panel will be hosted on a global cloud server (Vercel, AWS) that cannot directly access private BDIX IPs (172.16.x.x) |
| 23 | User browsers can reach BDIX sources and serve as distributed probes for health monitoring |
| 24 | Aggregating reports from hundreds of users provides accurate, real-time source health data |
| 25 | Anonymous reporting (ISP + source reachability) does not compromise user privacy |
| 26 | The same movie/series will appear on multiple BDIX sources with different filenames and qualities |
| 27 | Users prefer a clean, unified library without duplicate entries for the same content |
| 28 | TMDb IDs are stable and reliable identifiers for deduplication across sources |
| 29 | BDIX FTP servers organize TV series in various folder structures and naming conventions |
| 30 | Parser libraries (e.g., PTN) can reliably extract SxxExx tags from TV series filenames |
| 31 | Users expect to select specific episodes and have the correct file play |
| 32 | Users may search for titles that exist as both movies and TV series (e.g., "The Last of Us") |
| 33 | TMDb provides separate /movie and /tv API endpoints that must be queried independently |
| 34 | BDIX FTP admins frequently move or delete files to make space for new releases |
| 35 | A source being "online" (health check passes) does not guarantee specific files are still at their original paths |
| 36 | Silent re-scans can locate moved files without requiring manual admin intervention |
| 37 | BDIX FTP servers often store subtitles (.srt, .vtt) in the same directory as video files or in separate subfolders |
| 38 | Subtitle filenames typically match or closely resemble the video filename |
| 39 | A 60% filename similarity threshold is sufficient to match most subtitle files to their corresponding videos |
| 40 | FTP admins often leave empty folders after deleting content, which should be ignored during crawling |
| 41 | Some FTP servers use UTF-8 encoding while others use Windows-1252 for folder names with special characters |
| 42 | Character encoding auto-detection libraries (e.g., chardet) can reliably identify encoding types |
| 43 | Older BDIX content is often split into multi-part files (CD1/CD2, Part1/Part2) |
| 44 | Users expect a unified UI for multi-part movies with clear part selection options |
| 45 | New releases may leak on BDIX before TMDb has high-resolution posters or complete metadata |
| 46 | Re-verifying metadata every 7 days for early releases will capture updated assets as they become available |
| 47 | TMDb may change CDN policies or user ISPs may block TMDb's image domain, causing broken poster images |
| 48 | Image proxy services (wsrv.nl, Statically) provide reliable caching and resizing for poster images |
| 49 | Mobile browsers consume excessive battery and overheat when playing high-bitrate video (1080p+) |
| 50 | Native mobile players (VLC Mobile, MX Player) are more optimized for battery efficiency than mobile browsers |
| 51 | Running "Race Strategy" health checks on every page refresh creates browser cache bloat and slows UI |
| 52 | Service Workers can cache source accessibility results for 30 minutes without compromising freshness |
| 53 | Logging user's full local IP addresses in health reports poses privacy risks |
| 54 | Sanitizing logs to include only ISP name and reachability status protects user privacy |
| 55 | TMDb categorizes TV series specials (interviews, behind-the-scenes) as "Season 0" |
| 56 | FTP admins often store specials in folders named "Specials," "Extras," or use "S00" notation |
| 57 | Users in Bangladesh often search using local nicknames (e.g., "Hobbs and Shaw" vs. full title) |
| 58 | TMDb provides "Alternative Titles" that can be stored to improve search accuracy |
| 59 | When a protocol handler (vlc://) is clicked, browsers may fail silently if the app isn't installed |
| 60 | A 2-second visibility timeout can detect if an external app opened successfully |
| 61 | Many older Bangla subtitles on BDIX use ANSI (Bijoy) encoding instead of Unicode |
| 62 | Browsers only render UTF-8 subtitles correctly; ANSI subtitles appear as gibberish |
| 63 | Encoding-converter libraries can re-encode ANSI to Unicode on-the-fly |
| 64 | Without playhead tracking, users cannot distinguish between started and finished content in "Recently Watched" |
| 65 | A manual "Mark as Completed" toggle provides user control over their watch list organization |
| 66 | Public sites may attempt to hotlink enriched database data or images from the platform |
| 67 | Referer validation prevents unauthorized use of zero-cost infrastructure by copycat sites |
| 68 | Over time, permanently offline sources will accumulate thousands of dead links in the database |
| 69 | Automatic pruning of consistently unreachable links keeps the database lean and queries fast |
| 70 | Google may crawl and index private BDIX IP links, flagging the site as malicious or broken |
| 71 | JavaScript-generated links and robots.txt exclusions keep BDIX IPs hidden from search engines |
| 72 | Automated 6-hour scans perform thousands of database upserts that can cause UI sluggishness |
| 73 | Shadow table indexing allows scans to complete without locking the user-facing database |

---

## 9. Constraints

| # | Constraint |
|---|-----------|
| 1 | Solo developer — all development, testing, and deployment by one person |
| 2 | No budget for paid services — must use free tiers (TMDb, OMDb, hosting); no transcoding server budget |
| 3 | BDIX FTP servers are external and uncontrolled — downtime or changes can break functionality |
| 4 | Platform accessibility is limited to users whose ISP has BDIX peering |
| 5 | TMDb free API rate limit: 40 requests per 10 seconds |
| 6 | Modern browsers (Chrome, Safari, Edge) do NOT support MKV containers or AC3/DTS audio codecs natively |
| 7 | BDIX FTP servers typically use HTTP (not HTTPS), causing mixed content warnings on HTTPS-served platforms |
| 8 | No server-side transcoding capability — content must be played in original format or via external players |
| 9 | Metadata enrichment must be rate-limited to avoid API key bans — cannot process all content simultaneously |
| 10 | Real-time playhead/progress tracking is removed to simplify database load and reduce I/O operations |
| 11 | Database connection limits and I/O caps must be respected even with 1,000+ active users |
| 12 | Admin panel server cannot directly access BDIX FTP sources due to geographic/network restrictions |
| 13 | Traditional server-side health checks for BDIX sources are not possible from cloud-hosted admin panels |

---

## 10. Dependencies

| Dependency | Type | Impact if Unavailable |
|------------|------|----------------------|
| BDIX FTP Servers (Dflix, DhakaFlix, etc.) | External | No content to stream — core functionality lost |
| TMDb API | External | No metadata enrichment — content appears without posters/descriptions |
| OMDb API | External | Fallback lost — minor, TMDb is primary |
| Internet / BDIX connectivity | Infrastructure | Users cannot access platform or stream content |
| PostgreSQL | Infrastructure | No data storage — platform non-functional |
| Redis | Infrastructure | No caching or queue — degraded performance |

---

## 11. Risks

| # | Risk | Probability | Impact | Mitigation |
|---|------|-------------|--------|------------|
| 1 | BDIX FTP server goes offline permanently | Low | High | Support multiple sources; admin can disable/replace sources |
| 2 | FTP server changes URL or API structure | Medium | Medium | Modular scraper design — each source is independently updateable |
| 3 | TMDb API rate limits exceeded during scanning | Medium | Medium | Queue-based scanning with rate limiting and backoff |
| 4 | Filename parsing fails for edge cases | High | Low | Log unmatched items for admin review; improve parser over time |
| 5 | Browser can't play certain video formats (e.g., MKV/HEVC, DTS/AC3 audio) | High | High | Implement pre-flight format detection; redirect to Bridge page with VLC/PotPlayer deep links |
| 6 | Mixed content blocking (HTTPS site loading HTTP video sources) | High | Medium | Provide clear troubleshooting instructions on Bridge page; VLC/PotPlayer bypass browser security |
| 7 | Users don't have VLC or PotPlayer installed | Medium | Medium | Provide download links for players on Bridge page; offer direct file download as fallback |
| 8 | ISP-source mismatch (user's ISP cannot reach certain BDIX sources) | High | High | Implement "Race Strategy" with automatic source reachability detection; hide/filter unreachable content |
| 9 | User sees "dead links" or infinite loading for unreachable sources | Medium | Medium | Dynamic source discovery filters content based on real-time ping results; prioritize fastest sources |
| 10 | Filename parsing fails for non-standard or heavily modified filenames | High | Medium | Use intelligent parser (PTN) with fuzzy matching; flag low-confidence matches for admin review |
| 11 | TMDb API returns wrong movie due to ambiguous or misspelled filename | Medium | Medium | Implement confidence scoring; matches below 80% go to admin review queue for manual verification |
| 12 | Large volume of unmatched content overwhelms admin review queue | Medium | Low | Prioritize queue by source reliability; improve parser rules iteratively based on common patterns |
| 13 | TMDb/OMDb API rate limits exceeded during massive scans | High | High | Implement two-phase scanning: fast collector (no API) + slow enricher (3 req/s with retry-after detection) |
| 14 | Enrichment worker crashes or is interrupted mid-process | Medium | Low | Design worker to be resumable; processes "pending" entries sequentially from database |
| 15 | Users see content without metadata during initial enrichment | Medium | Low | Prioritize recently added content; hide unenriched content or show "Processing" badge |
| 16 | Database connection limits exceeded with many concurrent users | Medium | Medium | Implement debounced writes (1s delay) for watchlist/favorites; trigger-only history tracking |
| 17 | Excessive database I/O from frequent user interactions | Medium | Low | Cache last 10 watched items; debounce toggle actions; avoid real-time playback tracking |
| 18 | Admin panel cannot reach BDIX sources for health checks (hosted outside Bangladesh) | High | High | Implement crowdsourced "User-as-Probe" monitoring; users report source reachability to admin |
| 19 | Inaccurate source health status without direct admin access | Medium | Medium | Aggregate reports from hundreds of users for consensus-based health monitoring |
| 20 | Cannot distinguish between global outages and ISP-specific issues | Medium | Low | Track reachability by ISP; admin can see per-ISP health breakdown |
| 21 | Duplicate content entries (same movie from multiple sources) cluttering the library | High | High | Implement one-to-many metadata mapping using TMDb ID as anchor; multiple source links per content |
| 22 | Users confused by multiple posters for the same movie | Medium | Medium | Unified content records with single metadata entry and multiple source links |
| 23 | Difficulty selecting best quality when multiple sources available | Medium | Low | Automatic best quality selection based on reachability + quality ranking; manual override available |
| 24 | TV series episode mapping fails (wrong episode plays or no episode found) | High | High | Implement hierarchical Series > Season > Episode structure; extract SxxExx tags; filter sources by TMDb ID + S + E |
| 25 | Parser cannot extract season/episode numbers from non-standard filenames | Medium | Medium | Use robust parser (PTN) with regex fallbacks; flag unparseable files for admin review |
| 26 | Search misses content (e.g., searching "The Last of Us" only returns TV series, not movie) | Medium | Medium | Query both TMDb /movie and /tv endpoints simultaneously; merge results with content type badges |
| 27 | BDIX admins move or delete files, causing broken links ("link rot") | High | High | Implement path re-discovery: on 404 error, trigger silent re-scan; auto-update database if file found at new path |
| 28 | Users encounter 404 errors even when source is online | Medium | Medium | Silent re-scan on 404; mark links as "broken" if file not found; fallback to next available source |
| 29 | Subtitle files stored in separate folders or with different filenames cannot be found by browser | Medium | Medium | Implement fuzzy sidecar loading: scan for .srt/.vtt files during Phase 1 crawl; link files with >60% name match |
| 30 | Empty folders indexed by scraper clutter the database with "ghost" content | Medium | Low | Implement leaf-node validation: only create database entries for files with valid video extensions |
| 31 | Special characters (Bangla, Unicode) appear as gibberish due to encoding mismatch | Medium | Medium | Use character encoding auto-detection (chardet) during crawl phase to handle UTF-8 and Windows-1252 |
| 32 | Multi-part movies (CD1/CD2) create confusing UX with multiple "Play" buttons | Medium | Medium | Implement part-sequencing logic: detect "CD"/"Part" keywords; show single poster with part toggles |
| 33 | Repeated "Race Strategy" health checks on page refresh cause browser cache bloat and slow UI | Medium | Medium | Implement Service Worker peering cache: store accessibility results for 30 minutes; only re-run on network change |
| 34 | New releases have low-res posters or missing metadata when leaked before official release | Low | Low | Implement lazy enrichment re-trigger: flag early releases; re-verify metadata every 7 days for first month |
| 35 | TMDb CDN changes or ISP blocks cause broken poster images | Medium | Medium | Use lightweight image proxy (wsrv.nl, Statically) to cache and resize posters |
| 36 | Mobile browsers overheat and drain battery playing high-bitrate video (1080p+) | High | Medium | Detect mobile devices; prioritize "Open in VLC Mobile"/"MX Player" buttons over browser player |
| 37 | User privacy compromised if full local IP addresses are logged in health reports | High | High | Implement log sanitization: only send ISP name and reachability status, never full local IP |
| 38 | TV series specials appear as "Unmatched" in admin queue (not mapped to Season 0) | Medium | Low | Implement S00 mapping logic: treat "Special," "Extra," or "S00" folders/files as Season 0 |
| 39 | Users cannot find content when searching with local nicknames (e.g., "Hobbs and Shaw") | Medium | Medium | Store TMDb "Alternative Titles" in searchable field to support aliases and local variations |
| 40 | VLC/PotPlayer protocol handlers fail silently when app not installed, users think button is broken | Medium | Medium | Implement 2-second timeout check; show pop-up with download link if app doesn't open |
| 41 | Bangla subtitles in ANSI/Bijoy encoding appear as gibberish in browser player | Medium | Medium | Detect subtitle encoding; use converter library to re-encode ANSI to Unicode on-the-fly |
| 42 | Users cannot distinguish between started and finished content in "Recently Watched" without playhead tracking | Low | Low | Provide manual "Mark as Completed" toggle to move items to "Finished" archive |
| 43 | Copycat sites hotlink to platform's enriched database and images, abusing zero-cost infrastructure | Medium | Medium | Implement Referer validation on backend and image proxy to only serve requests from platform domain |
| 44 | Dead links from permanently offline sources accumulate over time, slowing search queries | Medium | Medium | Automatic pruning: delete source links unreachable by 100% of users for 30+ consecutive days |
| 45 | Google indexes private BDIX IPs, flagging site as malicious or broken | Medium | Medium | Generate BDIX links via JavaScript after user interaction; use robots.txt to block /play/ and /source/ routes |
| 46 | Automated scans cause UI sluggishness during thousands of database upserts | Medium | Low | Use shadow table indexing: run scans in temp table, perform single batch sync with main table |
| 47 | Solo developer burnout / bandwidth | Medium | High | Phased approach — prioritize MVP features first |
| 48 | ISP removes BDIX peering | Low | High | Out of control; document in limitations |

---

## 12. Success Metrics

| Metric | Target | Measurement Method |
|--------|--------|-------------------|
| Content Library Size | 1,000+ movies/series indexed | Database query |
| Source Availability | ≥ 80% of sources online at any time | Source health monitoring |
| Metadata Coverage | ≥ 90% of scanned content matched with metadata | Matched vs. unmatched ratio |
| Video Start Time | < 3 seconds | Frontend performance monitoring |
| Page Load Time | < 2 seconds | Lighthouse / Web Vitals |
| Registered Users | 100+ within first 3 months | User count |
| User Satisfaction | Positive community feedback | Direct feedback |

---

## 13. Glossary

| Term | Definition |
|------|-----------|
| **BDIX** | Bangladesh Internet Exchange — a local peering exchange providing high-speed, free/low-cost data transfer between connected ISPs |
| **FTP** | File Transfer Protocol — used by BDIX servers to host and serve content files |
| **Emby** | Open-source media server software — some BDIX sources run Emby and expose its API |
| **TMDb** | The Movie Database — free API for movie/TV series metadata |
| **OMDb** | Open Movie Database — fallback metadata API |
| **ISP** | Internet Service Provider |
| **HLS** | HTTP Live Streaming — adaptive streaming protocol using .m3u8 playlists |
| **Scraper** | Automated module that fetches content listings from a specific BDIX source |
| **Source** | A BDIX FTP server or media server that hosts video content |
| **Content Matching** | The process of identifying what movie/series a filename represents |

---

**Document Version**: 1.0  
**Project Name**: Flixarion  
**Approved By**: *Pending*  
**Date**: 2026-02-17
