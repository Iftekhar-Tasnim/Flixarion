# Frontend Scanner Blueprint

**Status:** Backend fully ready. Frontend implementation pending.  
**Last Updated:** 2026-02-25

---

## Why Client-Side Scanning?

The backend API is hosted on a cloud server that **cannot reach BDIX FTP servers**.  
BDIX FTPs (like `172.16.50.14`) are private intranet IPs only accessible from within Bangladesh ISP networks.

**Solution:** The user's browser is already on a BDIX connection.  
The browser does the scanning → POSTs file lists to the backend → Backend enriches with TMDb.

```
User (BDIX ISP)                   Cloud API Server
┌──────────────────────────┐      ┌──────────────────────────┐
│  Browser                 │      │  Laravel Backend          │
│                          │      │                           │
│  1. Ping all BDIX servers│      │  4. Receive file list     │
│  2. Detect which are up  │      │  5. Deduplicate           │
│  3. Crawl accessible ones│─────►│  6. TMDb enrichment       │
│     (fetch h5ai HTML)    │ POST │  7. Save to contents      │
│                          │/scan-│                           │
│  (BDIX access ✅)         │results  (cloud, no BDIX needed)│
└──────────────────────────┘      └──────────────────────────┘
```

---

## Step 1 — Fetch the Source List

On app load, fetch the list of all BDIX sources from the backend:

```javascript
const API_BASE = 'https://your-api.flixarion.com/api';

async function fetchSources() {
    const res = await fetch(`${API_BASE}/sources`);
    const { data } = await res.json();
    return data; // array of { id, name, base_url, scraper_type }
}
```

**Endpoint:** `GET /api/sources` (public, no auth needed)

**Response shape:**
```json
{
  "data": [
    { "id": 1, "name": "Dflix", "base_url": "https://movies.discoveryftp.net", "scraper_type": "dflix" },
    { "id": 2, "name": "DhakaFlix (Movie)", "base_url": "http://172.16.50.14", "scraper_type": "dhakaflix" },
    ...
  ]
}
```

---

## Step 2 — Race Strategy: Detect Which Servers Are Reachable

Ping all sources **simultaneously** (Promise.all). Use a tiny resource (favicon or small image) with a 3s timeout. Only crawl the ones that respond.

```javascript
async function testReachability(sources) {
    const results = await Promise.allSettled(
        sources.map(async (source) => {
            const controller = new AbortController();
            const timeout = setTimeout(() => controller.abort(), 3000);

            try {
                await fetch(`${source.base_url}/favicon.ico`, {
                    signal: controller.signal,
                    mode: 'no-cors', // BDIX servers don't send CORS headers
                    cache: 'no-store',
                });
                clearTimeout(timeout);
                return { ...source, reachable: true };
            } catch {
                clearTimeout(timeout);
                return { ...source, reachable: false };
            }
        })
    );

    return results
        .filter(r => r.status === 'fulfilled')
        .map(r => r.value)
        .filter(s => s.reachable);
}
```

> **Note:** `mode: 'no-cors'` means you can't read the response body, but a non-aborted fetch = server is up. This is enough for the reachability check.

**Also POST the health check result to the backend** (anonymous, no auth):

```javascript
async function reportHealth(sourceId, reachable, latencyMs) {
    await fetch(`${API_BASE}/sources/health`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ source_id: sourceId, is_reachable: reachable, latency_ms: latencyMs })
    });
}
```

---

## Step 3 — Crawl Accessible Sources

For each reachable source, crawl the appropriate way based on `scraper_type`.

### 3a — `dflix` (HTML scraping)

```javascript
async function crawlDflix(source) {
    const html = await fetchText(source.base_url);
    const doc = new DOMParser().parseFromString(html, 'text/html');
    const ids = [...doc.querySelectorAll('a[href*="/m/"]')]
        .map(a => a.href.match(/\/m\/([^/]+)/)?.[1])
        .filter(Boolean)
        .slice(0, 50); // limit

    const files = [];
    for (const id of ids) {
        const page = await fetchText(`${source.base_url}/m/view/${id}`);
        const match = page.match(/https?:\/\/[^"'\s]+\.(mp4|mkv)/i);
        if (match) {
            files.push({ path: match[0], filename: match[0].split('/').pop(), extension: match[0].split('.').pop() });
        }
    }
    return files;
}
```

### 3b — `dhakaflix` (h5ai directory walk)

```javascript
async function crawlH5ai(baseUrl, dirPath = '/', depth = 0, maxDepth = 3) {
    if (depth > maxDepth) return [];
    const url = baseUrl + dirPath;
    const html = await fetchText(url);
    const doc = new DOMParser().parseFromString(html, 'text/html');
    const links = [...doc.querySelectorAll('a[href]')].map(a => a.getAttribute('href'));

    const files = [];

    for (const href of links) {
        if (href === '/' || href.startsWith('/_h5ai') || href.startsWith('http') || href === '../') continue;

        if (href.endsWith('/')) {
            // It's a subdirectory — recurse
            const subFiles = await crawlH5ai(baseUrl, href, depth + 1, maxDepth);
            files.push(...subFiles);
        } else {
            const ext = href.split('.').pop().toLowerCase();
            if (['mp4', 'mkv', 'avi', 'mov'].includes(ext)) {
                files.push({
                    path: baseUrl + href,
                    filename: decodeURIComponent(href.split('/').pop()),
                    extension: ext
                });
            }
        }

        if (files.length >= 200) break; // safety cap
    }

    return files;
}

// For DhakaFlix Movie (source_id=2): start from homepage navlinks
// For DhakaFlix Series (source_id=3): start from /DHAKA-FLIX-12/TV-WEB-Series/
```

### 3c — `roarzone` / `ftpbd` (Emby API — needs api_key stored in source config)

```javascript
async function crawlEmby(source) {
    const apiKey = source.config?.api_key;
    if (!apiKey) return [];

    const res = await fetch(`${source.base_url}/Items?api_key=${apiKey}&Recursive=true&IncludeItemTypes=Movie&Fields=Path&Limit=500`);
    const { Items = [] } = await res.json();

    return Items.map(item => ({
        path: item.Path,
        filename: item.Path?.split('/').pop() || item.Name,
        extension: item.Path?.split('.').pop().toLowerCase() || 'mkv'
    })).filter(f => f.path);
}
```

---

## Step 4 — POST Scan Results to Backend

After crawling, send the file list to the backend. **No authentication required** — this is a public endpoint by design.

```javascript
async function pushScanResults(sourceId, files) {
    if (!files.length) return;

    const res = await fetch(`${API_BASE}/sources/${sourceId}/scan-results`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ files })
    });

    return res.json();
}
```

**Endpoint:** `POST /api/sources/{id}/scan-results` (public, no auth)

**Request body:**
```json
{
    "files": [
        {
            "path": "http://172.16.50.14/DHAKA-FLIX-14/Hindi%20Movies/2025/Pushpa2.2025.mkv",
            "filename": "Pushpa2.2025.mkv",
            "extension": "mkv",
            "size": null
        }
    ]
}
```

**What the backend does with it:**
1. Deduplicates by `file_path` — won't insert files already in DB
2. Inserts new files into `shadow_content_sources` with `enrichment_status = pending`
3. Automatically dispatches `EnrichBatchJob` (runs TMDb lookup, saves to `contents` table)

---

## Step 5 — Full Orchestration

Put it all together in a background scanner that runs silently on app load:

```javascript
async function runBackgroundScanner() {
    console.log('[Scanner] Starting BDIX scan...');
    const sources = await fetchSources();
    const reachable = await testReachability(sources);

    console.log(`[Scanner] ${reachable.length}/${sources.length} sources reachable`);

    for (const source of reachable) {
        try {
            let files = [];

            if (source.scraper_type === 'dflix') {
                files = await crawlDflix(source);
            } else if (source.scraper_type === 'dhakaflix') {
                // DhakaFlix Movie: start from homepage nav categories
                // DhakaFlix Series: start from /DHAKA-FLIX-12/TV-WEB-Series/
                files = await crawlH5ai(source.base_url);
            } else if (['roarzone', 'ftpbd'].includes(source.scraper_type)) {
                files = await crawlEmby(source);
            } else if (source.scraper_type === 'circleftp') {
                files = await crawlCircleFtp(source);
            }
            // iccftp, ihub: similar patterns

            await pushScanResults(source.id, files);
            console.log(`[Scanner] ${source.name}: ${files.length} files pushed`);

        } catch (err) {
            console.warn(`[Scanner] ${source.name} failed:`, err.message);
        }
    }

    console.log('[Scanner] Done.');
}

// Run on app startup (non-blocking)
runBackgroundScanner().catch(console.warn);
```

---

## CORS Consideration

> ⚠️ BDIX servers will not send `Access-Control-Allow-Origin` headers.  
> Use `mode: 'no-cors'` for the **reachability ping** only.  
> For **crawling** (reading HTML), you need CORS headers from the FTP server OR use a local proxy.

**Option A (Simplest):** Add a thin CORS proxy on your backend:

```
GET /api/proxy?url=http://172.16.50.14/DHAKA-FLIX-14/Hindi%20Movies/
```

The backend fetches the URL and returns the HTML. Your frontend reads it from your own domain (no CORS issue).

**Option B:** Use a browser extension or Electron app — no CORS restriction.

---

## Cache Strategy (Service Worker)

Store reachability results for 30 minutes to avoid re-pinging on every page load:

```javascript
// In your Service Worker (sw.js)
const CACHE_KEY = 'bdix_reachability';
const TTL_MS = 30 * 60 * 1000; // 30 minutes

async function getCachedReachability() {
    const cached = localStorage.getItem(CACHE_KEY);
    if (cached) {
        const { data, timestamp } = JSON.parse(cached);
        if (Date.now() - timestamp < TTL_MS) return data;
    }
    return null;
}

function cacheReachability(data) {
    localStorage.setItem(CACHE_KEY, JSON.stringify({ data, timestamp: Date.now() }));
}
```

---

## Summary of Backend Endpoints Used

| Step | Endpoint | Auth | Purpose |
|---|---|---|---|
| 1 | `GET /api/sources` | None | Get list of all BDIX sources |
| 2 | `POST /api/sources/health` | None | Report reachability from user's ISP |
| 4 | `POST /api/sources/{id}/scan-results` | None | Push crawled file list |

All three endpoints are **public** — no login required by the user.

---

## What Happens After Push

```
Browser pushes files
        ↓
POST /api/sources/{id}/scan-results
        ↓
ScanResultController deduplicates
        ↓
shadow_content_sources (pending)
        ↓
EnrichBatchJob (queue)
        ↓
FilenameParser → TMDb → OmdbFallback
        ↓
contents table (published ✅)
```

The user who triggered the scan may see new content appear within minutes (as enrichment runs server-side).
