# FullMockTestSeries.com PHP application

## Run locally

1. Install PHP 8 or newer and confirm `php -v` works.
2. Open this folder in a terminal.
3. Run `php -S localhost:8000 -t . local-router.php`.
4. Open http://localhost:8000/index.php.

### MySQL database

Production should use MySQL for accounts, enrollments, series, tests, orders, attempts, coupons, password resets and contact submissions. Local development can use JSON fallback storage; contact messages are stored outside the web root.

1. Enable PHP's `pdo_mysql` extension. On XAMPP, uncomment `extension=pdo_mysql` in `php.ini` and restart the terminal/server. Confirm it appears in `php -m`.
2. Start MySQL and create a database:

```sql
CREATE DATABASE tes2 CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

3. Run the schema and import all existing JSON data:

```bash
php scripts/import_json_to_mysql.php
```

Before using the app, apply `migrations/004_add_series_price.sql`, `migrations/005_add_series_parent_group.sql`, `migrations/006_add_series_image.sql`, `migrations/007_create_contact_messages.sql`, `migrations/008_create_subscriptions.sql`, `migrations/009_add_enrollment_access_control.sql`, `migrations/010_create_blog_posts.sql`, `migrations/011_activate_sbi_subscription.sql`, and `migrations/012_add_question_sections_and_directions.sql` in order. Apply subsequent numbered migrations as documented in `HOSTINGER-DEPLOY.md`, including `migrations/016_add_blog_pending_revision.sql`; skip migrations whose columns or table already exist.

The importer reads `DB_DSN`, `DB_USER` and `DB_PASS`; defaults are `127.0.0.1`, database `tes2`, user `root`, and an empty password.

4. Start the app with DB mode enabled (PowerShell):

```powershell
$env:USE_DB = '1'
$env:DB_DSN = 'mysql:host=127.0.0.1;dbname=tes2;charset=utf8mb4'
$env:DB_USER = 'root'
$env:DB_PASS = ''
php -S localhost:8000 -t . local-router.php
```

When `USE_DB=1`, a database connection failure stops the request. The app does not silently write production data back to files.

For Hostinger, create the MySQL database/user in hPanel, import `migrations/001_create_tables.sql` in phpMyAdmin, run the importer once from a local machine pointed at the Hostinger database or import the exported SQL, then set the same variables in the hosting environment. Do not commit database passwords to this repository.

For production password resets, set `HOSTINGER_APP_URL`, `HOSTINGER_MAIL_FROM` and `HOSTINGER_MAIL_FROM_NAME` in `hostinger-config.php`. The application stores reset tokens hashed in MySQL and sends the reset link through PHP's Hostinger mail service; it does not write reset links to local text files.

## Current flow

- Each exam card opens its own `product.php?product=...` page.
- Visitors are sent to login/signup before seeing a product page.
- Enrollments are stored per user in `data/users.json` with hashed passwords.
- Enrolled series appear in `student.php`, where the user can start practice.
- `practice.php` contains the current practice-test placeholder; questions, scoring and history can be added next.

## Test attempts

- `instructions.php` shows the rules before every test and starts the timer only after the user chooses Start test.
- `attempt.php` runs the ten-question timed test and calculates the score on the server.
- `result.php` shows score, percentage, time and timeout status.
- `history.php` preserves submitted attempts for enrolled users and displays score bars by series.
- `instructions.php?sample=1` starts the public free sample. Guest results are shown in the current session only and are not written to user history.

## Admin panel

- Open `admin.php` while signed in as an administrator.
- Signed-in users submit articles from `blog-submit.php`; submissions stay pending until an administrator approves them in `blog-admin.php`. Only published posts appear on the blog and homepage, and the submitter chooses the displayed author name.
- The existing development account is bootstrapped with the `admin` role.
- Create, update, activate/deactivate and delete custom test series.
- Add, edit and delete questions through `admin-question.php`; tests are limited to ten questions in this starter.
- Review registered users, active status, enrollments, recorded orders, paid revenue and average performance.
- Toggle student access to deactivate or reactivate an account. Administrators cannot deactivate themselves.
- Manage subscription plans at `admin-subscriptions.php`; plans support explicit course slugs, category names, and all-access coverage.
- Manage users, premium status, course enrollments and per-course performance at `admin-course-control.php`. Course access is revoked reversibly; orders and submitted attempts are retained for audit.
- From `admin-user-detail.php`, administrators can deactivate a student account and remove login credentials. The account is anonymized, premium is suspended, and all course access is revoked; financial and performance history is retained instead of physically deleting records.
- Users browse plans at `subscription.php`; `course_entitlement()` is the shared server-side check used by course enrollment.
- Published custom series appear on the home page through `series-api.php` and use the same product, enrollment, checkout and practice flow as built-in series.

## Information pages

The shared `info.php` page supports these URLs:

- `info.php?page=about`
- `info.php?page=contact`
- `info.php?page=disclaimer`
- `info.php?page=faq`
- `info.php?page=privacy`
- `info.php?page=terms`
- `info.php?page=refund`

The Contact page validates and stores submissions in MySQL after applying `migrations/007_create_contact_messages.sql`. With database mode disabled, it uses a locked JSON fallback in a `ranksetu-private` directory beside (not inside) the web root. Production deployments should use MySQL and apply migration `007` before enabling the form. The included `data/.htaccess` blocks direct access on Apache; configure an equivalent deny rule on Nginx or any server that does not process `.htaccess` files.

The shared footer links to `https://fullmocktestseries.com`. Set `HOSTINGER_FACEBOOK_URL`, `HOSTINGER_LINKEDIN_URL`, `HOSTINGER_TWITTER_URL`, `HOSTINGER_INSTAGRAM_URL`, and `HOSTINGER_YOUTUBE_URL` in `hostinger-config.php` when the official profiles are available. Only HTTPS URLs on the corresponding platform domains are enabled.

For production, use MySQL and configure Razorpay server-side. Never trust a client-side payment success message.

## Razorpay checkout

Course checkout and premium subscriptions use Razorpay Orders API. Enrollment and subscription activation require both a valid checkout signature and a server-side Razorpay confirmation that the payment is captured for the expected order, amount and currency. There is no demo payment path. Set these environment variables on the server before accepting payments:

`RAZORPAY_KEY_ID` and `RAZORPAY_KEY_SECRET`

This production checkout accepts only a Key ID beginning with `rzp_live_` and its matching Key Secret, configured through server environment variables or the private Hostinger config. Razorpay test keys are rejected. The site must run over HTTPS; without both live credentials, checkout fails closed and no enrollment is granted. Never commit or expose the Key Secret.
