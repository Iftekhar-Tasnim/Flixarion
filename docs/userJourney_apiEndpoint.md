# Flixarion — User Journeys & API Endpoints

---

## User Journeys

1. Start → Frontend pings all FTP sources (Race Strategy) → Determines reachable sources (e.g. Dflix, FTPBD) → Sends reachable source IDs to API → Homepage loads ONLY content available on user's reachable sources → Scroll through listings → Use filters (type/genre/year) → Search content → Choose a movie → View movie details & available sources → Select a source → Play content (direct FTP stream) → Close player → End.

2. Start → Explore homepage → Browse TV series listings → Choose a TV series → View seasons & episodes (all loaded in one call) → Select a season → Select an episode → View episode sources → Play episode → Click "Next Episode" → Continue playback → Close player → End.

3. Start → Click on Register → Fill registration form (name/email/password) → Submit → Account created & Sanctum token returned → Redirected to homepage → Explore content → Logout → End.

4. Start → Click on Login → Enter credentials → Sanctum token returned → Visit library (watchlist + favorites + history in one call) → Visit profile → Logout → End.

5. Start → Login → Search for a movie → Choose a movie → Add to watchlist → Add to favorites → Play movie (history auto-recorded) → Close player → View recently watched → Remove from watchlist → Remove from favorites → Logout → End.

6. Start → App loads → Frontend pings all BDIX sources (Race Strategy) → Determines which sources are reachable → Sends reachable source IDs as ?sources=1,3,7 to API calls → Send anonymous health report to backend (ISP name + reachability, no IPs) → Content is filtered server-side by reachable sources → Web Worker reads FTP directory listings in background (non-blocking, delta-only) → POSTs new files to backend → Backend caches in shadow table and triggers enrichment → End.

7. Start → Play content → Source fails (404) → Backend triggers silent re-scan → Auto-switch to next available source → Playback resumes → Continue watching → End.

8. Start → Admin login → View dashboard (users/content/source/enrichment stats) → Go to sources → Add new source → Test connection → Save source → Trigger scan → View scan logs → Logout → End.

9. Start → Admin login → Go to review queue → View low-confidence content → Approve/correct/reject items → Go to content list → Force metadata re-sync on a title → Toggle featured flag → Delete content → Logout → End.

10. Start → Admin login → Go to users list → Select a user → Ban user → Select another user → Reset password → Unban a user → Logout → End.

11. Start → Admin login → Go to enrichment status → View worker status → Pause enrichment → Resume enrichment → Go to settings → Update API keys and scan schedule → Logout → End.


---

## API Endpoints

Base URL: /api

Auth: Laravel Sanctum (token-based, non-expiring until logout)


Authentication (/api/auth)

1. POST /auth/register – Register with name, email, password. Returns Sanctum token

2. POST /auth/login – Login with email, password. Returns Sanctum token

3. POST /auth/logout – Revoke current token (Bearer)

4. GET /auth/me – Get authenticated user profile (Bearer)


Contents (/api/contents) — Public

5. GET /contents – Content from user's reachable sources (paginated). Params: ?sources=1,3&page=&per_page=&type=&genre=&year=&sort=trending|popular|recent

6. GET /contents/search?q= – Search by title (dual TMDb + alternative titles). Returns merged results

7. GET /contents/{id} – Full detail: metadata, genres, cast, trailer, seasons, episodes, all source links with quality/size/codec


User Library (/api/user) — Bearer Auth

8. GET /user/library – All-in-one: watchlist + favorites + recent history. Frontend separates them

9. POST /user/watchlist – Add content to watchlist. Body: {content_id}

10. DELETE /user/watchlist/{content_id} – Remove from watchlist

11. POST /user/favorites – Add content to favorites. Body: {content_id}

12. DELETE /user/favorites/{content_id} – Remove from favorites

13. POST /user/history – Record play event. Body: {content_id, episode_id?}

14. GET /user/history – Full watch history (paginated)


Source Health (/api/sources) — Public

15. POST /sources/health-report – Submit anonymous ISP reachability. Body: {isp_name, sources: [{source_id, is_reachable, response_time_ms}]}

16. POST /sources/{id}/scan-results – Client-triggered scan. Frontend Web Worker reads FTP directory listing in background (non-blocking, delta-only) and POSTs new file list to backend for caching and enrichment


Admin Dashboard (/api/admin) — Admin Auth

16. GET /admin/dashboard – All stats: users, content, sources, queue size, enrichment progress


Admin Sources (/api/admin/sources) — Admin Auth

17. GET /admin/sources – All sources with config, scan logs, health, ISP breakdown

18. POST /admin/sources – Create source. Body: {name, base_url, scraper_type, config, priority}

19. GET /admin/sources/{id} – Source detail with scan history

20. PUT /admin/sources/{id} – Update source

21. DELETE /admin/sources/{id} – Delete source

22. POST /admin/sources/{id}/test – Test source connection

23. POST /admin/sources/{id}/scan – Trigger Phase 1 scan


Admin Content (/api/admin/contents) — Admin Auth

24. GET /admin/contents – All content with enrichment status. Frontend filters/searches

25. PATCH /admin/contents/{id} – Update flags (featured, published). Body: {is_featured?, is_published?}

26. DELETE /admin/contents/{id} – Delete content

27. POST /admin/contents/{id}/resync – Force metadata re-fetch from TMDb/OMDb


Admin Review Queue (/api/admin/review-queue) — Admin Auth

28. GET /admin/review-queue – All low-confidence content with filenames and suggestions

29. POST /admin/review-queue/{id}/approve – Approve match

30. POST /admin/review-queue/{id}/correct – Apply corrected TMDb ID. Body: {tmdb_id}

31. POST /admin/review-queue/{id}/reject – Reject and remove


Admin Users (/api/admin/users) — Admin Auth

32. GET /admin/users – All users with signup date, last active, watch count

33. POST /admin/users/{id}/ban – Ban user

34. POST /admin/users/{id}/unban – Unban user

35. POST /admin/users/{id}/reset-password – Reset password


Admin Enrichment & Settings (/api/admin) — Admin Auth

36. GET /admin/enrichment – Worker status: running/paused, queue size, processing rate

37. POST /admin/enrichment/pause – Pause enrichment worker

38. POST /admin/enrichment/resume – Resume enrichment worker

39. GET /admin/settings – All system settings

40. PUT /admin/settings – Update settings. Body: {key: value, ...}

41. GET /admin/analytics – Analytics dashboard data (Post-MVP)