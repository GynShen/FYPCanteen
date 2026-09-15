# FYPCanteen

FYPCanteen is a centralised school-canteen meal-ordering web application. It provides separate functions for canteen owners, canteen operators and customers, including school and menu management, advance ordering, Stripe test payments, preparation planning, permanent student QR codes, meal collection and dashboard reports.

## Technology stack

- PHP 8.2 or later
- Laravel 12
- MySQL
- React 18 with Inertia.js
- Tailwind CSS and Vite
- Stripe Checkout
- Google Drive image storage
- `html5-qrcode` for camera-based QR scanning
- Pest for automated testing

## Requirements

Install the following software before setting up the project:

- PHP 8.2+
- Composer
- MySQL or WAMP
- Node.js and npm
- Git (optional)
- ngrok (optional, but recommended for mobile QR-scanner testing)

## 1. Install the project

Open PowerShell or a terminal in the project directory and install the backend and frontend dependencies:

```bash
composer install
npm install
```

On Windows, use `npm.cmd` if PowerShell blocks `npm.ps1`:

```bash
npm.cmd install
```

## 2. Create the environment file

Create `.env` from `.env.example`.

PowerShell:

```powershell
Copy-Item .env.example .env
```

Command Prompt:

```bat
copy .env.example .env
```

Generate the Laravel application key:

```bash
php artisan key:generate
```

`APP_KEY` should no longer be empty after this command.

## 3. Configure MySQL

Create an empty MySQL database, for example `fypcanteen`, and update these values in `.env`:

```env
APP_NAME=FYPCanteen
APP_ENV=local
APP_DEBUG=true
APP_URL=http://127.0.0.1:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=fypcanteen
DB_USERNAME=root
DB_PASSWORD=
```

Change the username and password to match the local MySQL installation.

## 4. Configure Google Drive

Meal images are uploaded to Google Drive when `MenuSeeder` runs. Add valid Google Drive credentials to `.env`:

```env
GOOGLE_DRIVE_CLIENT_ID=your_client_id
GOOGLE_DRIVE_CLIENT_SECRET=your_client_secret
GOOGLE_DRIVE_REFRESH_TOKEN=your_refresh_token
GOOGLE_DRIVE_FOLDER=your_folder_id
```

Do not commit real credentials to Git.

## 5. Configure Stripe test mode

Add Stripe test credentials to `.env`:

```env
STRIPE_KEY=pk_test_your_publishable_key
STRIPE_SECRET=sk_test_your_secret_key
STRIPE_WEBHOOK_SECRET=whsec_your_webhook_secret
STRIPE_CURRENCY=myr
```

Use test keys only. A successful payment requires Stripe to send a signed webhook to:

```text
/stripe/webhook
```

## 6. Create and seed the database

For a new installation, run:

```bash
php artisan migrate:fresh --seed
```

Warning: `migrate:fresh` deletes all existing tables and data before recreating them. Do not run it when existing data must be preserved.

The seeders create:

- Owner and operator accounts
- Two demonstration schools
- Linked parents and students
- Unlinked sibling students
- Menu categories and meals
- July and August dashboard/report records
- A time-sensitive QR meal-collection demonstration

To rerun only the meal-collection demonstration data:

```bash
php artisan db:seed --class=MealCollectionSeeder
```

`MealCollectionSeeder` schedules its orders for the day it is executed. Rerunning it removes and recreates its demonstration order items, so existing demo collection and still-hungry results will be cleared.

## 7. Build and run the application

### Development mode

Run Laravel and Vite in separate terminals:

```bash
php artisan serve
```

```bash
npm run dev
```

Then open:

```text
http://127.0.0.1:8000
```

### Demonstration mode

Build the frontend once:

```bash
npm run build
```

Then start Laravel:

```bash
php artisan serve
```

When using the production build, `npm run dev` does not need to remain running.

## 8. Demonstration accounts

| Role | Login | Password |
|---|---|---|
| Canteen owner | `owner@example.com` | `password` |
| Sungai Long operator | `operator_sungai_long` | `password` |
| Batu 11 operator | `operator_batu_11` | `password` |
| QR-demo customer | `adrian.lim@example.com` | `password` |

The QR-demo student is **Aiden Lim**. His permanent QR value is:

```text
demo-meal-collection-batu-11
```

## 9. Mobile access and QR scanning with ngrok

Browser camera access requires HTTPS. Keep Laravel running, then start ngrok in another terminal:

```bash
ngrok http 8000
```

Open the generated HTTPS URL on the phone. The phone may use a different Wi-Fi network or mobile data because ngrok forwards requests to the Laravel server running on the computer.

Requirements during mobile testing:

- The computer, MySQL/WAMP, Laravel and ngrok must remain running.
- The computer must remain connected to the internet.
- Camera permission must be granted in the mobile browser.
- The free ngrok URL may change whenever ngrok restarts.
- Only demonstration data should be exposed through the temporary URL.

Phone requests update the same local MySQL database used by the computer. Meal-collection status is based on the Laravel server/computer time, not the phone time.

For Stripe webhook testing through ngrok, configure the current HTTPS endpoint in Stripe:

```text
https://your-ngrok-domain.ngrok-free.app/stripe/webhook
```

Then place the corresponding webhook signing secret in `STRIPE_WEBHOOK_SECRET` and clear cached configuration:

```bash
php artisan optimize:clear
```

## 10. Run automated tests

Run the report unit and integration tests:

```bash
php artisan test tests/Unit tests/Feature/Integration
```

Run the complete Laravel test suite:

```bash
php artisan test
```

## Common commands

```bash
# Clear cached Laravel configuration and routes
php artisan optimize:clear

# Rebuild frontend files
npm run build

# Rerun all seeders without deleting the current database structure
php artisan db:seed

# Rerun only the QR meal-collection demo
php artisan db:seed --class=MealCollectionSeeder
```

## Troubleshooting

### `APP_KEY` is empty

```bash
php artisan key:generate
```

### Database connection fails

Confirm that MySQL/WAMP is running and that the `DB_*` values in `.env` are correct.

### Environment changes are not applied

```bash
php artisan optimize:clear
```

### Frontend changes do not appear

During development, restart `npm run dev`. For demonstration mode, rebuild the frontend with `npm run build` and refresh the browser without cache.

### Camera does not open on the phone

Use the ngrok HTTPS URL, allow camera permission, and confirm that the operator is logged in through the mobile browser.

### Menu seeding fails during image upload

Verify the Google Drive client ID, client secret, refresh token and target folder ID. The folder and credentials must allow image uploads.
