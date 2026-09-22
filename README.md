# RankSetu PHP starter

## Run locally

1. Install PHP 8 or newer and confirm `php -v` works.
2. Open this folder in a terminal.
3. Run `php -S localhost:8000`.
4. Open http://localhost:8000/index.php.

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
- The existing development account is bootstrapped with the `admin` role.
- Create, update, activate/deactivate and delete custom test series.
- Add, edit and delete questions through `admin-question.php`; tests are limited to ten questions in this starter.
- Review registered users, active status, enrollments, simulated paid orders, simulated revenue and average performance.
- Toggle student access to deactivate or reactivate an account. Administrators cannot deactivate themselves.
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

For production, move users to a database and connect a server-side payment provider such as Razorpay or Stripe. Never trust a client-side payment success message.

## Razorpay checkout

The checkout uses Razorpay Orders API and server-side signature verification. Set these environment variables before accepting payments:

```powershell
$env:RAZORPAY_KEY_ID = "rzp_test_your_key_id"
$env:RAZORPAY_KEY_SECRET = "your_key_secret"
php -S localhost:8000
```

Use Razorpay test keys while developing. Without both variables, checkout refuses to create an order and does not enroll the student. After a verified payment, the order is saved, the series is enrolled, the order appears in `orders.php`, and the student can download an invoice from the dashboard.

