# User Interaction Matrix

| User Action | UI Screen | Endpoint Triggered | Method | Key Data / Logic |
|---|---|---|---|---|
| **Guest / Public** | | | | |
| Explore Homepage | Home / Dashboard | /api/v1/contents/ | GET | Loads trending/recent listings. |
| Apply Filters | Browse / Search | /api/v1/contents/ | GET | Pass query params: ?type=movie&genre=action. |
| Search Content | Search Bar | /api/v1/contents/ | GET | Pass ?q=keyword (can combine with filters). |
| View Movie Details | Content Detail | /api/v1/contents/{id} | GET | Fetches metadata, description, and rating. |
| Select Source/Play | Media Player | /api/v1/contents/{id}/source | GET | Retrieves FTP links/streams for the player. |
| Browse TV Seasons | Series Detail | /api/v1/contents/{id}/seasons | GET | Returns list of seasons and episodes. |
| Source Fails (404) | Media Player | /api/v1/contents/{id}/source | GET | Logic: Frontend catches 404, triggers next source in array. |
| **Authentication** | | | | |
| Register | Register Page | /api/v1/register | POST | Body: name, email, password. Creates account & returns token. |
| Login | Login Page | /api/v1/login | POST | Body: email, password. Returns auth token. |
| View Profile | Profile Page | /api/v1/profile | GET | Returns current user info (name, email, avatar). |
| Logout | Any (Header) | /api/v1/logout | POST | Invalidates token / clears session. |
| **User Library** | | | | |
| View Watchlist | Watchlist Page | /api/v1/user/watchlist | GET | Returns array of saved content items. |
| Add to Watchlist | Content Detail | /api/v1/user/watchlist | POST | Body: content_id. Adds item to watchlist. |
| Remove from Watchlist | Watchlist Page | /api/v1/user/watchlist/{content_id} | DELETE | Removes specific item from watchlist. |
| View Favorites | Favorites Page | /api/v1/user/favorites | GET | Returns array of favorited content. |
| Add to Favorites | Content Detail | /api/v1/user/favorites | POST | Body: content_id. Adds item to favorites. |
| Remove from Favorites | Favorites Page | /api/v1/user/favorites/{content_id} | DELETE | Removes specific item from favorites. |
| View Recently Watched | History Page | /api/v1/user/recently-watched | GET | Returns watch history sorted by last watched. |
| **Admin — Users** | | | | |
| View Dashboard | Admin Dashboard | /api/v1/admin/dashboard | GET | Returns user count, content count, source stats. |
| List All Users | Admin Users | /api/v1/admin/users | GET | Returns paginated user list. |
| View User Details | Admin User Detail | /api/v1/admin/users/{id} | GET | Returns full user profile + activity. |
| Ban User | Admin User Detail | /api/v1/admin/users/{id}/ban | POST | Sets user status to banned. Blocks login. |
| Unban User | Admin User Detail | /api/v1/admin/users/{id}/unban | POST | Restores user access. |
| Reset User Password | Admin User Detail | /api/v1/admin/users/{id}/reset-password | POST | Generates temp password or sends reset link. |
| **Admin — Sources** | | | | |
| List All Sources | Admin Sources | /api/v1/sources | GET | Returns all FTP/BDIX sources with status. |
| Add New Source | Admin Sources | /api/v1/sources | POST | Body: name, url, type. Creates new source entry. |
| View Source Detail | Admin Source Detail | /api/v1/sources/{id} | GET | Returns source config, status, last scan time. |
| Edit Source Config | Admin Source Detail | /api/v1/sources/{id} | PUT | Body: updated fields. Saves new config. |
| Delete Source | Admin Source Detail | /api/v1/sources/{id} | DELETE | Removes source and its associated content links. |
| Test Source Connection | Admin Source Detail | /api/v1/sources/{id}/test | POST | Pings source URL, returns connection status. |
| Trigger Scan | Admin Sources | /api/v1/sources/scan | POST | Initiates content scan across active sources. |
| View Scan Logs | Admin Logs | /api/v1/admin/sources/logs | GET | Returns scan history with errors and results. |
