# Flixarion — Frontend API Reference

**Base URL:** `https://your-api.flixarion.com/api`  
**Last Updated:** 2026-02-25  
**Format:** All responses are JSON. Success → `{ "data": ... }`. Error → `{ "message": "...", "errors": {...} }`

---

## Authentication

- Public endpoints: no header needed
- Authenticated endpoints: `Authorization: Bearer {token}`
- Token is returned on login/register and valid until logout

---

## Response Envelope

```json
// Success
{ "data": { ... }, "meta": { ... } }

// Error
{ "message": "Unauthenticated.", "errors": {} }
```

---

## 1. Auth

### Register
```
POST /auth/register
```
**Body:**
```json
{ "name": "Rahim", "email": "rahim@example.com", "password": "secret123", "password_confirmation": "secret123" }
```
**Response `201`:**
```json
{ "data": { "token": "1|abc...", "user": { "id": 1, "name": "Rahim", "email": "rahim@example.com" } } }
```

---

### Login
```
POST /auth/login
```
**Body:**
```json
{ "email": "rahim@example.com", "password": "secret123" }
```
**Response `200`:**
```json
{ "data": { "token": "1|abc...", "user": { "id": 1, "name": "Rahim", "email": "rahim@example.com" } } }
```

---

### Logout
```
POST /auth/logout          🔒 Auth required
```
**Response `200`:** `{ "data": { "message": "Logged out successfully." } }`

---

### Get Current User
```
GET /auth/me               🔒 Auth required
```
**Response `200`:**
```json
{ "data": { "id": 1, "name": "Rahim", "email": "rahim@example.com", "role": "user", "created_at": "..." } }
```

---

## 2. Content Browsing

### Browse All Content
```
GET /contents
```

**Query Params:**

| Param | Type | Default | Description |
|---|---|---|---|
| `type` | `movie` \| `series` | — | Filter by content type |
| `genre` | string (slug) | — | e.g. `action`, `thriller` |
| `year` | integer | — | e.g. `2024` |
| `sort` | `recent` \| `trending` \| `popular` | `recent` | Sort order |
| `per_page` | integer (max 50) | `20` | Items per page |
| `page` | integer | `1` | Page number |
| `sources` | comma-separated IDs | — | e.g. `1,3,7` — annotates `is_reachable` per item |
| `only_available` | boolean | `false` | Combine with `sources` — hides unreachable content |

**Response `200`:**
```json
{
  "data": [
    {
      "id": 42,
      "type": "movie",
      "title": "Pushpa: The Rise",
      "original_title": "Pushpa",
      "year": 2021,
      "rating": "7.6",
      "poster_path": "https://image.tmdb.org/t/p/w500/abc.jpg",
      "backdrop_path": "https://image.tmdb.org/t/p/w1280/xyz.jpg",
      "language": "te",
      "genres": [{ "id": 1, "name": "Action", "slug": "action" }],
      "source_ids": [1, 2],
      "has_any_source": true,
      "is_reachable": true        // only present if ?sources= was sent
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 20,
    "total": 843,
    "last_page": 43,
    "filter_mode": "all"          // "all" | "available_only"
  }
}
```

> **Frontend tip:** Use `source_ids` + your Race Strategy reachable IDs to gray out unreachable content cards. Toggle `?only_available=true` for the "Show only what I can watch" filter.

---

### Search Content
```
GET /contents/search?q={query}
```

**Query Params:**

| Param | Type | Required | Description |
|---|---|---|---|
| `q` | string (min 2 chars) | ✅ | Search query |
| `per_page` | integer | — | Max 50, default 20 |

**Response `200`:**
```json
{
  "data": [ { "id": 1, "title": "...", "type": "movie", ... } ],
  "meta": { "query": "pushpa", "total": 3, "current_page": 1, "last_page": 1, "per_page": 20 }
}
```

---

### Content Detail
```
GET /contents/{id}
```

**Response `200`:**
```json
{
  "data": {
    "id": 42,
    "type": "movie",
    "title": "Pushpa: The Rise",
    "year": 2021,
    "description": "A laborer named Pushpa...",
    "poster_path": "https://image.tmdb.org/t/p/w500/abc.jpg",
    "backdrop_path": "https://image.tmdb.org/t/p/w1280/xyz.jpg",
    "cast": ["Allu Arjun", "Rashmika Mandanna"],
    "director": "Sukumar",
    "rating": "7.6",
    "runtime": 179,
    "trailer_url": "https://youtube.com/watch?v=abc",
    "language": "te",
    "genres": [{ "id": 1, "name": "Action", "slug": "action" }],
    "source_links": [
      {
        "id": 1,
        "file_path": "http://172.16.50.14/Hindi Movies/Pushpa.2021.1080p.mkv",
        "quality": "1080p",
        "file_size": 2147483648,
        "subtitle_paths": ["http://172.16.50.14/Hindi Movies/Pushpa.2021.srt"],
        "status": "active",
        "source": {
          "id": 2,
          "name": "DhakaFlix (Movie)",
          "base_url": "http://172.16.50.14",
          "scraper_type": "dhakaflix",
          "priority": 1
        }
      }
    ],
    "seasons": []   // for series: array of seasons with episodes
  }
}
```

**Series source_links structure (per episode):**
```json
"seasons": [
  {
    "season_number": 1,
    "episodes": [
      {
        "episode_number": 1,
        "title": "Pilot",
        "source_links": [
          { "file_path": "http://...", "quality": "1080p", "source": { ... } }
        ]
      }
    ]
  }
]
```

---

## 3. CORS Proxy

> Used by the frontend scanner to fetch BDIX directory listings without CORS errors.

```
GET /proxy?url={bdix_url}
```

| Param | Description |
|---|---|
| `url` | Full BDIX URL (must be from a registered source) |

**Response `200`:** Raw HTML or JSON from the BDIX server, same `Content-Type` as original.

**Error `403`:** URL is not from a registered BDIX source.  
**Error `502`:** Source is unreachable or timed out.

**Example:**
```
GET /proxy?url=http://172.16.50.14/DHAKA-FLIX-14/Hindi%20Movies/2025/
→ Returns h5ai directory listing HTML
```

---

## 4. Sources & Health

### List All Active Sources
```
GET /sources
```

**Response `200`:**
```json
{
  "data": [
    {
      "id": 1,
      "name": "Dflix",
      "base_url": "https://movies.discoveryftp.net",
      "scraper_type": "dflix",
      "health_score": 95,
      "priority": 1
    },
    {
      "id": 2,
      "name": "DhakaFlix (Movie)",
      "base_url": "http://172.16.50.14",
      "scraper_type": "dhakaflix",
      "health_score": 88,
      "priority": 2
    }
  ]
}
```

> **Frontend tip:** Use this list on app load to run Race Strategy pings and build your reachability map.

---

### Ping a Source (Test Connection)
```
GET /sources/{id}/ping
```

**Response `200`:**
```json
{ "data": { "source_id": 2, "name": "DhakaFlix (Movie)", "reachable": true, "latency_ms": 142 } }
```

**Response `404`:** Source not found or not active.

> **Use case:** "Test Connection" button next to each source in the UI.

---

### Report Source Health (Race Strategy result)
```
POST /sources/health-report
```

No auth required. Called automatically after Race Strategy ping.

**Body:**
```json
{
  "isp_name": "Carnival Internet",
  "sources": [
    { "source_id": 1, "is_reachable": true, "response_time_ms": 95 },
    { "source_id": 2, "is_reachable": true, "response_time_ms": 142 },
    { "source_id": 3, "is_reachable": false, "response_time_ms": 3000 }
  ]
}
```

**Response `201`:** `{ "data": { "reported": 3 } }`

---

### Push Scan Results (Client-Side Scan)
```
POST /sources/{id}/scan-results
```

No auth required. Called after the browser crawls a BDIX source.

**Body:**
```json
{
  "files": [
    {
      "path": "http://172.16.50.14/Hindi Movies/Pushpa2.2025.1080p.mkv",
      "filename": "Pushpa2.2025.1080p.mkv",
      "extension": "mkv",
      "size": null
    }
  ]
}
```

**Response `201`:**
```json
{ "data": { "inserted": 47, "skipped": 153, "invalid": 2, "batch_id": "uuid..." } }
```

> `skipped` = files already in the database (deduplication). `invalid` = non-video files ignored.

---

## 5. User Library (Auth Required 🔒)

All endpoints require: `Authorization: Bearer {token}`

### Full Library Overview
```
GET /user/library
```
Returns watchlist, favorites, recently watched (last 10), and user stats in one call.

---

### Watchlist

| Method | Endpoint | Description |
|---|---|---|
| `GET` | `/user/watchlist` | Get user's watchlist |
| `POST` | `/user/watchlist` | Add to watchlist |
| `DELETE` | `/user/watchlist/{content_id}` | Remove from watchlist |

**POST body:** `{ "content_id": 42 }`

---

### Favorites

| Method | Endpoint | Description |
|---|---|---|
| `GET` | `/user/favorites` | Get user's favorites |
| `POST` | `/user/favorites` | Add to favorites |
| `DELETE` | `/user/favorites/{content_id}` | Remove from favorites |

**POST body:** `{ "content_id": 42 }`

---

### Watch History

| Method | Endpoint | Description |
|---|---|---|
| `GET` | `/user/history` | Full watch history (chronological) |
| `POST` | `/user/history` | Record a play event |

**POST body:**
```json
{ "content_id": 42 }
```

> One entry per play session — only records when user clicks Play. No playback position tracked.

---

## 6. Frontend Playback Guide

### Step 1 — Get source links from content detail

```
GET /contents/{id}
→ source_links[].file_path = the raw FTP URL to play
→ source_links[].quality = "480p" | "720p" | "1080p" | "2160p"
→ source_links[].source.scraper_type = which server it's on
```

### Step 2 — Select best source (Story #13)

```js
const reachable = sourceLinks.filter(l => reachableSourceIds.includes(l.source.id));
const qualityRank = { '2160p': 4, '1080p': 3, '720p': 2, '480p': 1 };
const best = reachable.sort((a, b) => qualityRank[b.quality] - qualityRank[a.quality])[0];
```

### Step 3 — Pre-flight format check

```js
const ext = best.file_path.split('.').pop().toLowerCase();
if (ext === 'mkv' || ext === 'avi') {
    // → Redirect to Bridge page (VLC/PotPlayer)
} else {
    // → Play in <video> tag
}
```

### Step 4 — Bridge page (for MKV/incompatible)

```
vlc://http://172.16.50.14/Hindi%20Movies/Pushpa2.mkv
potplayer://http://172.16.50.14/Hindi%20Movies/Pushpa2.mkv
```

Direct download as fallback.

### Step 5 — Handle 404 during playback (Story #12)

```js
videoEl.addEventListener('error', async () => {
    if (videoEl.error.code === 404) {
        // Silently re-scan: POST /sources/{source_id}/scan-results with { files: [] }
        // (empty array just triggers a fresh scan for this source)
        // Then try next available source
    }
});
```

---

## 7. Race Strategy (On App Load)

```js
const sources = await fetch('/api/sources').then(r => r.json()); // GET /sources

const results = await Promise.allSettled(
    sources.data.map(src =>
        fetch(`/api/sources/${src.id}/ping`)
            .then(r => r.json())
            .catch(() => ({ data: { source_id: src.id, reachable: false, latency_ms: 0 } }))
    )
);

const reachableIds = results
    .filter(r => r.status === 'fulfilled' && r.value.data.reachable)
    .map(r => r.value.data.source_id);

localStorage.setItem('bdix_reachable', JSON.stringify({ ids: reachableIds, ts: Date.now() }));

// Report to backend
await fetch('/api/sources/health-report', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
        isp_name: 'Unknown', // or detect from IP geolocation
        sources: results.map(r => ({
            source_id: r.value.data.source_id,
            is_reachable: r.value.data.reachable,
            response_time_ms: r.value.data.latency_ms
        }))
    })
});
```

---

## 8. HTTP Status Codes

| Code | Meaning |
|---|---|
| `200` | Success |
| `201` | Resource created |
| `202` | Accepted (async job dispatched) |
| `401` | Unauthenticated — missing/invalid token |
| `403` | Forbidden — authenticated but not permitted |
| `404` | Resource not found |
| `422` | Validation error — check `errors` field |
| `429` | Rate limited — 60 requests/min |
| `502` | Upstream BDIX source unreachable (proxy only) |

---

## 9. CORS Configuration

The API allows requests from:
- `http://localhost:3000` (dev)
- Your production frontend domain (configured in `config/cors.php`)

All public endpoints return: `Access-Control-Allow-Origin: *`  
The `/proxy` endpoint **also** returns `Access-Control-Allow-Origin: *` explicitly.

---

## 10. Quick Reference — All Frontend Endpoints

| # | Method | Endpoint | Auth | Purpose |
|---|---|---|---|---|
| 1 | POST | `/auth/register` | No | Register |
| 2 | POST | `/auth/login` | No | Login, get token |
| 3 | POST | `/auth/logout` | 🔒 | Logout |
| 4 | GET | `/auth/me` | 🔒 | Current user profile |
| 5 | GET | `/contents` | No | Browse all content |
| 6 | GET | `/contents/search?q=` | No | Search |
| 7 | GET | `/contents/{id}` | No | Content detail + sources |
| 8 | GET | `/proxy?url=` | No | CORS proxy for BDIX URLs |
| 9 | GET | `/sources` | No | List all BDIX sources |
| 10 | GET | `/sources/{id}/ping` | No | Test FTP reachability |
| 11 | POST | `/sources/health-report` | No | Report Race Strategy results |
| 12 | POST | `/sources/{id}/scan-results` | No | Push crawled file list |
| 13 | GET | `/user/library` | 🔒 | Full library overview |
| 14 | GET | `/user/watchlist` | 🔒 | Get watchlist |
| 15 | POST | `/user/watchlist` | 🔒 | Add to watchlist |
| 16 | DELETE | `/user/watchlist/{id}` | 🔒 | Remove from watchlist |
| 17 | GET | `/user/favorites` | 🔒 | Get favorites |
| 18 | POST | `/user/favorites` | 🔒 | Add to favorites |
| 19 | DELETE | `/user/favorites/{id}` | 🔒 | Remove from favorites |
| 20 | GET | `/user/history` | 🔒 | Watch history |
| 21 | POST | `/user/history` | 🔒 | Record a play event |
