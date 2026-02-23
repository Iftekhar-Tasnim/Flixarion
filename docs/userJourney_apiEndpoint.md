Start → Explore homepage (trending/recent content loads) → Scroll through listings → Use filters (type/genre/year) → Search content (with/without filters) → Choose a movie → View movie details & available sources → Select a source → Play content (direct FTP stream) → Download content (optional) → Close player → End.

Start → Explore homepage → Browse TV series listings → Choose a TV series → View seasons → Select a season → Select an episode → View episode sources → Play episode → Click “Next Episode” → Continue playback → Close player → End.

Start → Click on Register → Fill registration form (name/email/password) → Submit → Account created & logged in → Redirected to homepage → Explore content → Go to profile → Logout → End.

Start → Click on Login → Enter credentials → Logged in → Visit watchlist → Visit favorites → Visit profile → Logout → End.

Start → Click on Login → Logged in → Search for a movie → Choose a movie → Add to watchlist → Add to favorites → Play movie (history auto-recorded) → Close player → View recently watched → Logout → End.

Start → Login → Go to watchlist → Remove an item → Go to favorites → Remove an item → Logout → End.

Start → Play content → Source fails (404) → Switch to next available source / Content not available message → Playback resumes → Continue watching → End.

Start → Admin login → View dashboard (users/content/source stats) → Go to sources → Add new source → Test connection → Save source → Logout → End.

Start → Admin login → Go to users list → Select a user → Ban user → Select another user → Reset password → Unban a user → Logout → End.

Start → Admin login → Go to sources → View source detail → Edit source configuration → Save changes → Delete inactive source → Logout → End.




API Documentation (v1)

Base URL: /api/v1

Authentication (/api/v1)

POST /register – User registration

POST /login – User login

POST /logout – User logout

GET /profile – Get current authenticated user profile

Content (/api/v1/contents)

GET / – Get all content

GET /{id} – Get content details with metadata

GET /{id}/source – Get available sources for content

GET /{id}/seasons – Get seasons of content

User Library (/api/v1/user)

Watchlist

GET /watchlist – Get user watchlist

POST /watchlist – Add content to watchlist

DELETE /watchlist/{content_id} – Remove content from watchlist

Favorites

GET /favorites – Get user favorites

POST /favorites – Add content to favorites

DELETE /favorites/{content_id} – Remove content from favorites

Recently Watched

GET /recently-watched – Get recently watched items

Admin (/api/v1/admin)

GET /dashboard – Dashboard overview

GET /users – Get all users

GET /users/{id} – Get user details

POST /users/{id}/ban – Ban user

POST /users/{id}/unban – Unban user

POST /users/{id}/reset-password – Reset user password

Sources (/api/v1)

GET /sources – List all sources

POST /sources – Create source

GET /sources/{id} – Get source details

PUT /sources/{id} – Update source

DELETE /sources/{id} – Delete source

POST /sources/{id}/test – Test source connection

POST /sources/scan – Scan sources

GET /admin/sources/logs – View scan logs