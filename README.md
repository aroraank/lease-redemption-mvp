# Lease Redemption MVP

A clean, production-ready Laravel web application for lease redemption. Users enter a dealer code and ZIP code, the app fetches available leases via API, users select one or many leases, and trigger a redemption workflow. Built with best practices for scalability and real-world API integration.

## Features

- **Dealer Code + ZIP Lookup** — Simple form-based lease discovery with input validation
- **API Integration Layer** — Single service class (`LeaseApiService`) abstraction; ships with a mock, swaps to real API with one `.env` change
- **Persistent Redemptions** — Redeemed leases don't reappear in future lookups; data persists in a JSON file (demo-friendly, easily swappable for a database)
- **Single / Multiple / Select All** — Users tick individual leases, multiple, or a header checkbox to select all at once
- **Clean Responsive UI** — Mobile-first Blade views with inline CSS (no build step needed)
- **Error Handling** — Friendly validation messages and API error paths; server-side guards on all forms
- **CSRF Protection** — All POST endpoints include CSRF tokens and middleware
- **Demo Reset** — One-click reset of all redemptions so you can re-record the flow

## Tech Stack

| Layer | Technology |
|-------|-----------|
| **Backend** | PHP 8.1+, Laravel 11 |
| **Frontend** | Blade templating, HTML5, Vanilla JS, CSS3 |
| **Database** | None required (MVP uses in-memory session + JSON file for persistence) |
| **APIs** | Laravel Http client, bearer token auth, timeouts + error handling |
| **Package Manager** | Composer |

## Installation

### Prerequisites
- **PHP** 8.1+ (XAMPP or any local PHP setup)
- **Composer** https://getcomposer.org/download/

### On Windows + XAMPP (Recommended)

1. Clone the repository:
```bash
cd E:\xampp\htdocs
git clone https://github.com/yourusername/lease-redemption-mvp.git
cd lease-redemption-mvp
```

2. Install dependencies:
```bash
composer install
```

3. Set up environment:
```bash
copy .env.example .env
```
(Laravel auto-generates `.env` on project creation; if it doesn't exist, create it from `.env.example`)

4. Generate app key:
```bash
php artisan key:generate
```

5. Start the dev server:
```bash
php artisan serve
```

6. Open **http://127.0.0.1:8000** in your browser.

### On macOS / Linux

```bash
git clone https://github.com/yourusername/lease-redemption-mvp.git
cd lease-redemption-mvp
composer install
cp .env.example .env
php artisan key:generate
php artisan serve
```

## Quick Start

1. Home page loads with dealer code and ZIP input.
2. Enter a **demo dealer code** (see list below) + any 5-digit ZIP (e.g., `80229`).
3. Click **Find leases** → API returns available leases.
4. Tick one or more leases (or use the header checkbox to **select all**).
5. Click **Start redemption** → leases are marked as redeemed.
6. Go back to home → try the same dealer + ZIP again — you'll see fewer leases (the redeemed ones are gone).
7. Click **Reset demo data** to restore all leases and re-record the demo.

### Demo Dealer Codes

| Code | Available Leases | ZIP |
|------|------------------|-----|
| **DLR1024** | 5 | Any (e.g., 80229) |
| **DLR2048** | 4 | Any (e.g., 78701) |
| **DLR3072** | 4 | Any (e.g., 90210) |
| **DLR4096** | 3 | Any (e.g., 60601) |

Invalid codes (anything not starting with "DLR") trigger a friendly error message, which is great for testing error paths.

## Project Structure

```
.
├── app/
│   ├── Http/
│   │   ├── Controllers/LeaseController.php       # Request handling & flow
│   │   └── Requests/LeaseLookupRequest.php       # Input validation
│   ├── Services/LeaseApiService.php              # API integration (mock + real)
│   └── ...                                        # Standard Laravel app structure
├── config/
│   ├── lease.php                                 # Lease API config (reads from .env)
│   └── ...
├── routes/
│   └── web.php                                   # Web routes
├── resources/
│   └── views/
│       ├── layout.blade.php                      # Shared layout & CSS
│       ├── home.blade.php                        # Dealer code + ZIP form
│       └── leases.blade.php                      # Lease list & redemption
├── storage/
│   └── app/lease_redeemed.json                   # Persisted redemptions (auto-created)
├── .gitignore                                    # Git ignore rules
├── .env                                          # Environment (gitignored, local only)
├── .env.example                                  # Template for .env
├── composer.json                                 # PHP dependencies
└── README.md                                     # This file
```

## API Integration

### Current State (Mock)

The app ships with a **built-in mock API** that:
- Returns realistic sample leases from a fixed catalog (4 dealers × 3–5 leases each)
- Persists redemptions to `storage/app/lease_redeemed.json`
- Validates dealer codes and returns friendly errors

**To use the mock:** Keep `LEASE_API_MOCK=true` in `.env`.

### Connecting the Real API

To plug in the client's real lease provider:

1. Get from the client:
   - API base URL (e.g., `https://provider.example.com/api`)
   - Auth method (bearer token, API key, OAuth, etc.)
   - Sample request/response for lease lookup and redemption

2. In `.env`, set:
```
LEASE_API_MOCK=false
LEASE_API_BASE_URL=https://provider.example.com/api
LEASE_API_KEY=your-secret-key
```

3. In `app/Services/LeaseApiService.php`, adjust two methods:
   - `getLeases()` — tweak request params to match the real API
   - `fromRealApiLease()` — map the real response fields to our internal format

**No controller, route, or view changes needed.** The service is the integration point.

## Key Classes

### LeaseApiService
Handles all external API communication. Two main methods:
- `getLeases(dealerCode, zip)` — returns `{ok, message, leases[]}`
- `redeem(leaseIds)` — returns `{ok, message, redeemed}`

Each has a mock implementation (used when `LEASE_API_MOCK=true`) and a real HTTP-based version.

### LeaseController
Orchestrates the user flow:
- `home()` — renders the dealer-code form
- `lookup()` — validates form input, calls the API, saves leases to session
- `index()` — displays the lease list
- `redeem()` — processes selected leases, marks them as redeemed, redirects home
- `reset()` — clears all persisted redemptions (demo helper)

### LeaseLookupRequest
Laravel form request class; centralizes validation rules and messages for the home form.

## Configuration

All config lives in `.env` (never commit this):

```
LEASE_API_MOCK=true
LEASE_API_BASE_URL=
LEASE_API_KEY=
```

The `config/lease.php` file reads these at runtime and is **safe to commit** (no secrets hardcoded).

## Error Handling

- **Validation errors** — inline, form-level messages
- **API errors** — logged and returned as user-friendly banners
- **Session expiry** — if the lease list is cleared between pages, user is redirected home
- **Empty selections** — "Select at least one lease to redeem"

All errors preserve form input so users can re-try without re-entering data.

## Persistence (Demo)

Redeemed leases are stored in `storage/app/lease_redeemed.json` (auto-created on first redemption). This is **gitignored** and can be:
- Deleted manually to reset the demo
- Replaced with a database table when moving to production
- Cleared via the "Reset demo data" link in the UI

## Performance Notes

- No database queries (MVP uses session + JSON file)
- No external CDN dependencies (CSS is inline in Blade)
- API calls have 15s timeout (lookup) and 20s timeout (redemption)
- Validation is both client-side (form patterns) and server-side (Laravel validation)

## Testing / QA

See `docs/TEST_CHECKLIST.md` in the root for a full manual test plan covering happy paths, error paths, responsiveness, and selection behavior.

## Upwork Proposal

See `docs/UPWORK_PROPOSAL.md` for the pitch/proposal template used to land this job.

## Next Steps for Production

1. Replace mock with real API credentials and response mapping
2. Swap in-memory persistence for a `Redemptions` database table
3. Add user authentication (dealer login)
4. Add logging and monitoring (e.g., Sentry for error tracking)
5. Deploy to a production server (Laravel Forge, AWS, Heroku, etc.)

## License

Confidential — built for a specific client engagement.

## Contact

Ankit Arora
- Email: cankit087@gmail.com
- Phone: +91 9041771409
- Location: Chandigarh, India

---

*This MVP was built to spec and delivered with working code, comprehensive docs, and a demo video.*
