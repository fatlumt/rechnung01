# Klarrechnung · Hostinger MVP

German-language PHP/MySQL invoice app for Swiss businesses. Includes separate business accounts, customer records, Offerten, Rechnungen, Zahlungserinnerungen, Mahnungen, partial payments, and the approved A4 invoice layout. Each business uploads its own logo and optional stamp. Built frontend assets are included; Hostinger does not need Node.js.

## First installation

1. Deploy this repository's `main` branch to **public_html/digital**. The target is `digital.krasniqi-plattenleger.ch`. Do not deploy to the main website's `public_html` root.
2. Use PHP 8.2 or later with `pdo_mysql`, `mbstring` and sessions. Enable SSL for the subdomain.
3. Open the **separate, empty invoice database** in Hostinger's phpMyAdmin. Choose **Import**, select `backend/schema.sql`, then **Go**. This creates the app's tables without test data.
4. In Hostinger File Manager, create `klarrechnung-config.php` in the domain folder **one level above public_html**. For this deployment its path is `/home/HOSTINGER_USER/domains/krasniqi-plattenleger.ch/klarrechnung-config.php`. Copy the contents of `backend/config.example.php` into it.
5. Replace `db_name`, `db_user` and `db_password` with the full values from your new database, including Hostinger's prefixes. Use the database host shown by Hostinger (usually `localhost`), and port 3306. Keep `app_url` as `https://digital.krasniqi-plattenleger.ch`.
6. Replace `setup_key` with a newly generated password-manager value of at least 32 characters (letters and digits are easiest to paste into PHP). Keep all values inside the single quotes. If a value contains a single quote or backslash, escape it as `\'` or `\\` respectively. Save this file only on the server, never in GitHub or chat. Keeping it above `public_html` also keeps it outside Git's deployment folder.
7. Open the app over HTTPS. Create your owner account using your email, a new login password of at least 12 characters, and the setup key. The first account becomes administrator; setup closes after it succeeds.
8. In **Einstellungen**, save your company address, VAT status, bank details, logo and optional stamp. Create your first customer and invoice draft. Review the PDF before choosing **Verbindlich ausstellen**.
9. To add a different business, use **Einstellungen → Ihr Konto → Unternehmen einladen**. Give the email-bound invitation link to that business. It creates an isolated workspace and expires after three days. No invitations or invoices are emailed automatically.

If your File Manager cannot open the domain folder above `public_html`, a fallback is `public_html/digital/backend/config.php`; that directory is denied by `.htaccess`. Keep a private backup outside the deployment folder and restore the file if a Git deployment removes it. Prefer the configuration above `public_html`.

Hostinger instructions: [import a database](https://www.hostinger.com/support/1884149-how-to-import-a-database-with-phpmyadmin-in-hostinger/) and [Git deployment](https://www.hostinger.com/support/1583302-how-to-deploy-a-git-repository-in-hostinger/).

## Updates and data

Enable **Auto-deployment** for `main` in Hostinger's Git panel. A pushed commit must contain updated `assets/app.js` and `assets/app.css`; this PHP deployment does not run an npm build. Business records and uploaded branding are stored in MySQL, not Git. Back up the database and private configuration before upgrades. Schema changes require explicit migration instructions; do not recreate the database for routine updates.

The schema uses InnoDB transactions, owner-scoped queries and business-level locking to protect numbering and payments. Optimistic versions prevent overwriting stale company or document edits. Issued documents keep company/customer snapshots and cannot be edited or deleted through the app. Each payment increases the invoice version; reminders keep the outstanding balance as of their creation date. Amounts are stored in CHF cents; VAT rounds per line.

Passwords use PHP password hashing. Sessions use HTTP-only, SameSite cookies and secure cookies outside localhost, CSRF tokens, an eight-hour inactivity limit and an account version to revoke other sessions after password changes. Authentication attempts are rate limited. Database credentials and setup keys are not part of this repository.

## Local development

Install Node.js and PHP 8.2+ with MySQL/MariaDB. Run `npm ci`, `npm run typecheck`, then `npm run build`. The browser runs compiled React; PHP is the only runtime needed on Hostinger.

For local use only, copy `backend/config.example.php` to the ignored `backend/config.php`, point it at a new local database and set `app_url` to `http://127.0.0.1:8097`. Import the schema, choose a fresh setup key, and run `php -S 127.0.0.1:8097`. PHP's development server does not enforce `.htaccess`; never expose it publicly. Use Apache/LiteSpeed for deployment and web-server access-rule verification.

`tests/api_test.py` needs Python 3, a fresh isolated local database and a running local server. Set `KR_TEST_SETUP_KEY` to that local configuration's key, optionally set `KR_TEST_URL`, then run `python tests/api_test.py`. It creates synthetic accounts and data and refuses remote server addresses. Do not run against real business data.

## Current scope

This is the first Swiss domestic-business MVP. It has one user per business, manual payment recording and direct PDF downloads plus optional browser printing. It does not yet include Swiss QR payment slips, bank sync, automatic email, password-reset emails, credit notes/cancellations, audit export, billing/subscriptions, or German e-invoices. Germany contacts can be stored, but billing is restricted to Swiss domestic customers. Keep issued mistakes outside normal editing; credit-note support is a next step. Configure hosting/database backups before using it for business records.

Print using A4, 100% scale and no browser headers/footers. Check long documents in print preview. The first template matches the approved layout; additional designs can be added later.

## October update

The customer dropdown inside an invoice or offer now includes **Neuer Kunde**. The customer form opens over the existing draft, preserves all entered values and selects the saved customer automatically. Escape closes the customer form first.

Use **Speichern & PDF** to save a draft and download it immediately, or **Als PDF herunterladen** on a saved document. **Drucken** remains separate. PDFs contain selectable text, company branding, payment deductions and automatic table pagination. Downloads stay in the browser; no third-party PDF service receives invoice data.

Under **Einstellungen → Firmenfarbe**, choose a colour or upload your logo and click **Farbe aus Logo übernehmen**. Save the company settings to apply it across your workspace and new documents. Colours are adjusted for readable text. Existing documents keep their saved company snapshot. No database migration is required for this update.

## Superadmin and user management

The original owner account (created with the setup code) is the Superadmin. Sign in and open **Einstellungen → Benutzerverwaltung** to create users, generate invitations, replace setup links, revoke unused invitations, deactivate accounts or reactivate them. The Superadmin account is protected from deactivation. Regular users cannot access these controls, including through direct API calls.

**Benutzer erstellen** reserves an account immediately. **Benutzer einladen** creates the account when the invitation is accepted. Share the generated link personally; no email is sent automatically. Links are email-bound, expire after three days and can be used once. Generating a replacement invalidates older links. Recipients choose their own passwords. Deactivation blocks sign-in and invalidates existing sessions, without deleting business records. Pending accounts need a new setup link after reactivation.

Every user has a separate company workspace. Superadmin management exposes account status and email addresses, not another company's customers, invoices or passwords. There is no impersonation or password-reset function.

This release adds only the `kr_user_access` table automatically on the first database connection. Existing accounts, roles and business data remain intact; existing accounts are active by default. The database user needs CREATE permission for this additive upgrade. Alternatively, run the final CREATE TABLE statement from `backend/schema.sql` once. Never recreate an existing database.

`tests/admin_test.py` uses the same local-only environment variables as the invoice API suite, with its own fresh isolated database. It checks authorization, account setup, invitation replacement/revocation, data isolation, owner protection and session invalidation.
