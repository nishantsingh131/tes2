# Hostinger Deployment

## 1. Create the database

In Hostinger hPanel:

1. Open **Databases > MySQL databases**.
2. Create a database, username, and password.
3. Note the exact database name, username, password, and host shown by Hostinger. Hostinger often prefixes database names with your account name.

## 2. Import the database

1. Open **phpMyAdmin** from hPanel.
2. Select the new database.
3. Open **Import**.
4. Upload `migrations/001_create_tables.sql` and click **Import**.
5. Run `migrations/004_add_series_price.sql`, `migrations/005_add_series_parent_group.sql`, and `migrations/006_add_series_image.sql` in that order. Skip any migration whose column is already present on an existing database.
6. Run `migrations/007_create_contact_messages.sql` to enable database-backed contact submissions.
7. Run `migrations/008_create_subscriptions.sql` to enable subscription plans, entitlements, and subscription-backed enrollments.
8. Run `migrations/009_add_enrollment_access_control.sql` to enable reversible per-user course deactivation and live access enforcement.
9. Run `migrations/010_create_blog_posts.sql` to enable MySQL-backed blog submissions and review.
10. Run `scripts/import_json_to_mysql.php` once with the Hostinger database credentials. This seeds the built-in banking series and starter questions, plus any custom exam series and tests present in `data/series.json` and `tests/`.
11. Run `migrations/011_activate_sbi_subscription.sql` after the import to publish the five SBI series and create the baseline premium plan in MySQL.
12. Run `migrations/012_add_question_sections_and_directions.sql` to store question section labels and shared directions.
13. Run `migrations/013_convert_bank_premium_to_all_courses.sql` after migration 011 to convert the existing ₹999/180-day plan to all published courses. This also grants the expanded course scope to existing active subscriptions without changing their payment or expiry history. If migration 011 is rerun later, rerun migration 013 afterward.
14. Run `migrations/014_set_all_courses_premium_price.sql` after migration 013 to set the All Government Exams Premium price to ₹149. If migration 013 is rerun later, rerun migration 014 afterward.
15. Run `migrations/015_refresh_bpsc_test_description.sql` to update the published BPSC listing to its current 150-question, 120-minute format.
16. Run `migrations/016_add_blog_pending_revision.sql` to let authors propose edits to published articles without removing the current live version while an administrator reviews the change. Skip this migration if the `pending_revision` column already exists.

The importer creates the schema, imports users and orders, and seeds the built-in banking catalog alongside custom government-exam series stored in JSON. `hostinger-import.sql` is a legacy snapshot and does not contain the latest catalog or test data.

## 3. Upload the application

Upload the PHP application files to `public_html`. Do not upload local runtime folders such as `.mysql-data`; it is not needed. Keep `hostinger-import.sql` outside `public_html` or delete it after importing.

## 4. Configure MySQL

Copy `hostinger-config.php.example` to `hostinger-config.php` and replace the placeholders:

```php
define('HOSTINGER_USE_DB', '1');
define('HOSTINGER_DB_DSN', 'mysql:host=localhost;port=3306;dbname=YOUR_DATABASE_NAME;charset=utf8mb4');
define('HOSTINGER_DB_USER', 'YOUR_DATABASE_USER');
define('HOSTINGER_DB_PASS', 'YOUR_DATABASE_PASSWORD');
define('HOSTINGER_APP_URL', 'https://www.example.com');
define('HOSTINGER_MAIL_FROM', 'no-reply@example.com');
define('HOSTINGER_MAIL_FROM_NAME', 'FullMockTestSeries.com');
define('HOSTINGER_FACEBOOK_URL', '');
define('HOSTINGER_LINKEDIN_URL', '');
define('HOSTINGER_TWITTER_URL', '');
define('HOSTINGER_INSTAGRAM_URL', '');
define('HOSTINGER_YOUTUBE_URL', '');
```

Add the official HTTPS profile URLs to these settings when the accounts are ready. The footer only enables links for the matching social platform domains; blank or invalid values stay non-clickable.

Keep `hostinger-config.php` private. It is ignored by Git when added to the server only.

For Razorpay, either set `RAZORPAY_KEY_ID` and `RAZORPAY_KEY_SECRET` in the hosting environment or set `HOSTINGER_RAZORPAY_KEY_ID` and `HOSTINGER_RAZORPAY_KEY_SECRET` in the server-only `hostinger-config.php`. Use the live Key ID beginning with `rzp_live_` and its matching Secret. Never put real credentials in the example file or commit them to Git.

Use an email address created on the same domain as the website for `HOSTINGER_MAIL_FROM`. Hostinger's PHP `mail()` service must be enabled for the account, and the domain should have valid SPF/DKIM records so reset emails are accepted reliably.

## 5. Test

Open your domain and verify:

- Request a nonexistent URL and confirm it returns the branded 404 page with HTTP 404, not the homepage with status 200.
- Open `/sitemap.php`; confirm it returns a valid sitemap index and that each `?part=` child returns valid XML. Submit the index URL in Search Console.
- Login works.
- The admin account can open `admin.php`.
- Contact submissions are stored in `contact_messages`; the JSON fallback directory is outside `public_html` and writable only by the PHP account.
- Direct requests to `public_html/data/` are denied by the web server. The included `.htaccess` covers Apache; add an equivalent Nginx rule if the host uses Nginx.
- Create a test series with a JPG, PNG, or WebP image and confirm it appears in the catalog, product page, and student dashboard. For image uploads, configure PHP `upload_max_filesize` to at least `3M` and `post_max_size` above `3M`; PHP must be able to create files under `public_html/uploads/series`.
- Series and questions appear.
- Student enrollments and attempts are saved.
- Orders and coupons appear in the database.
- Forgot-password creates a database reset token.
- Forgot-password sends a one-hour reset link by email; reset tokens are hashed in MySQL and are never written to a text file in production.
- Configure the matching live Razorpay Key ID and Key Secret in the hosting environment or private `hostinger-config.php`. This production checkout rejects test keys and requires a Key ID beginning with `rzp_live_`. Keep the secret out of source control and public files.
- Production enrollment and activation require a valid signature and Razorpay API confirmation of a captured payment; missing or invalid credentials fail closed. Verify the flow using a controlled low-value live purchase and refund before launch.
- Create plans from `/admin-subscriptions.php`, then verify eligible course enrollment and premium access after a successful Razorpay payment.
- Open `/subscription.php` and confirm All Government Exams Premium lists every eligible published series, including BPSC and banking courses. Confirm the included-course count matches the published catalog.
- Open `/admin-course-control.php` and verify that an admin can view premium status, course-level attempts/averages, and deactivate/restore access without deleting orders or history.

Do not accept production payments until the site is on HTTPS, Razorpay has approved the account for live payments, and the production Razorpay keys are configured. Make a low-value live transaction and verify the order, enrollment, invoice and refund process before announcing checkout.
