# Klarrechnung · Hostinger MVP



German-language PHP/MySQL invoice app for Swiss and German domestic businesses. Includes separate business accounts, customer records, Offerten, Rechnungen, Zahlungserinnerungen, Mahnungen, partial payments, and the approved A4 invoice layout. Each business uploads its own logo and optional stamp. Built frontend assets are included; Hostinger does not need Node.js.



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



The schema uses InnoDB transactions, owner-scoped queries and business-level locking to protect numbering and payments. Optimistic versions prevent overwriting stale company or document edits. Issued documents keep company/customer snapshots and cannot be edited or deleted through the app. Each payment increases the invoice version; reminders keep the outstanding balance as of their creation date. Amounts are stored in the document currency's cents; VAT rounds per line.



Passwords use PHP password hashing. Sessions use HTTP-only, SameSite cookies and secure cookies outside localhost, CSRF tokens, an eight-hour inactivity limit and an account version to revoke other sessions after password changes. Authentication attempts are rate limited. Database credentials and setup keys are not part of this repository.



## Local development



Install Node.js and PHP 8.2+ with MySQL/MariaDB. Run `npm ci`, `npm run typecheck`, then `npm run build`. The browser runs compiled React; PHP is the only runtime needed on Hostinger.



For local use only, copy `backend/config.example.php` to the ignored `backend/config.php`, point it at a new local database and set `app_url` to `http://127.0.0.1:8097`. Import the schema, choose a fresh setup key, and run `php -S 127.0.0.1:8097`. PHP's development server does not enforce `.htaccess`; never expose it publicly. Use Apache/LiteSpeed for deployment and web-server access-rule verification.



`tests/api_test.py` needs Python 3, a fresh isolated local database and a running local server. Set `KR_TEST_SETUP_KEY` to that local configuration's key, optionally set `KR_TEST_URL`, then run `python tests/api_test.py`. It creates synthetic accounts and data and refuses remote server addresses. Do not run against real business data.



## Current scope



The app supports domestic businesses in Switzerland (CHF) and Germany (EUR), with one user and one company/country per workspace. Country cannot change after the first document. It supports manual payments and PDF downloads. German PDFs are not XRechnung or ZUGFeRD; use them only where permitted, with recipient consent where required. Cross-border billing, reverse charge, government e-invoices, bank sync, automatic email, credit notes and audit exports are not implemented.



Choose Vorlage 1, Modern or Kompakt in company settings, or override the design on a draft. Existing documents retain their saved company, currency and design. Legacy documents keep Vorlage 1. German profiles support 19%/7% VAT or a small-business exemption, tax number/USt-IdNr and optional legal company details. Required German number, tax ID and service date cannot be hidden. Switzerland retains its reusable bank QR upload and CHF 50 dunning fee; this fee is not applied to German invoices. German language is currently the only document language.



Print using A4, 100% scale and no browser headers/footers. Review long documents before issuing. The country/design update needs no database migration. `tests/country_templates_test.py` covers currency, tax validation, design defaults, inheritance and document snapshot preservation against an isolated local database.



## October update



The customer dropdown inside an invoice or offer now includes **Neuer Kunde**. The customer form opens over the existing draft, preserves all entered values and selects the saved customer automatically. Escape closes the customer form first.



Use **Speichern & PDF** to save a draft and download it immediately, or **Als PDF herunterladen** on a saved document. **Drucken** remains separate. PDFs contain selectable text, company branding, payment deductions and automatic table pagination. Downloads stay in the browser; no third-party PDF service receives invoice data.



Under **Einstellungen → Firmenfarbe**, choose a colour or upload your logo and click **Farbe aus Logo übernehmen**. Save the company settings to apply it across your workspace and new documents. Colours are adjusted for readable text. Existing documents keep their saved company snapshot. No database migration is required for this update.



## Superadmin and user management



The original owner account (created with the setup code) is the Superadmin. Sign in and open **Einstellungen → Benutzerverwaltung** to create users, generate invitations, replace setup links, revoke unused invitations, deactivate accounts or reactivate them. The Superadmin account is protected from deactivation. Regular users cannot access these controls, including through direct API calls.



**Benutzer erstellen** reserves an account immediately. **Benutzer einladen** creates the account when the invitation is accepted. Share the generated link personally; no email is sent automatically. Links are email-bound, expire after three days and can be used once. Generating a replacement invalidates older links. Recipients choose their own passwords. Deactivation blocks sign-in and invalidates existing sessions, without deleting business records. Pending accounts need a new setup link after reactivation.



Every user has a separate company workspace. Superadmin management exposes account status and email addresses, not another company's customers, invoices or passwords. There is no impersonation or password-reset function.



The account-management upgrade adds the `kr_user_access` table automatically on the first database connection. Existing accounts, roles and business data remain intact; existing accounts are active by default. The database user needs CREATE permission for this additive upgrade. Alternatively, run the corresponding CREATE TABLE statement from `backend/schema.sql` once. Never recreate an existing database.



`tests/admin_test.py` uses the same local-only environment variables as the invoice API suite, with its own fresh isolated database. It checks authorization, account setup, invitation replacement/revocation, data isolation, owner protection and session invalidation.



## Document layout and pricing options



In a new invoice/offer or an editable draft, open **Felder auf diesem Beleg anzeigen** to show or hide payment method, reference, UID, due date, document number and service date separately. Values remain stored internally. The preview, print view and downloaded PDF honour these choices; hidden document numbers are also omitted from PDF page footers and metadata. Issued document content remains immutable.



Choose **Nur Gesamtpreis** for one net price for the complete job (VAT is added at the selected rate). Descriptions and section titles remain visible, while item quantities/prices are hidden. Switching back to **Einzelpreise** preserves the entered item values. **Abschnitt hinzufügen** inserts a free-text heading; arrow buttons move headings and items into the desired order. Section headings do not affect totals. Options are saved with each document and carried through offer conversion and reminders. No database migration is needed.



Payment metadata uses larger bold text; the logo area is larger. PDF filenames use the customer's name, document type and document date, for example `Muster AG_Rechnung_15.10.2026.pdf`. Filename-unsafe characters are replaced.



Run `tests/document_options_test.py` with the same fresh local database setup as `tests/api_test.py` to check pricing, sections, visibility, VAT, payments, reminders and offer conversion.



## Inline status and document actions



Invoice rows now provide a status dropdown. **Offen – Rechnung ausstellen** opens the preview; **Verbindlich ausstellen** issues the draft after review. **Bezahlt** records the remaining balance as a payment dated today. Returning a paid/partially paid invoice to **Offen** requires confirmation and appends a negative payment correction; previous journal entries are retained. Overdue status is calculated from the due date and outstanding balance, and cannot be manually cleared while still overdue.



The action dropdown creates a **Zahlungserinnerung** or **Mahnung** for an issued invoice with an outstanding balance, using that balance, standard text and a deadline ten days from today. It immediately opens the saved document for preview/PDF, without sending email. Double submissions with a stale invoice version are rejected. Previously created reminders retain their balance snapshots after payment corrections.



For any offer, **In Rechnung umwandeln** opens an editable copy. Nothing is created until Save; cancelling leaves the offer alone. Saving preserves the original offer, its company snapshot and copied customer (unless changed in the form), and creates a separate invoice draft with the edited content. One invoice per offer is enforced; after conversion the dropdown offers **Rechnung öffnen** instead.



`tests/row_actions_test.py` runs on a fresh local test database and includes the base invoice suite plus payment correction, stale request, quick reminder, tenant isolation and offer conversion checks. No schema migration is needed.





## Mobile interface, payment notices and QR uploads



The first Mahnung adds a single CHF 50 fee to the invoice balance. Subsequent Mahnungen never stack this fee. A Zahlungserinnerung creates no new fee, but includes any unpaid fee already charged. Payments settle principal first, then the fee; marking paid settles the combined balance. The additive `kr_dunning_fees` table is created automatically (CREATE permission required), with existing document content retained.



New notices store a source-invoice snapshot. PDFs contain the notice, original invoice pages, then the optional payment-information page. Older notices use the available source invoice when exported from the app. Notice balances and new source snapshots remain unchanged by later payments or company edits. This is document creation only; nothing is emailed automatically.



In Einstellungen, upload a reusable bank-issued Swiss QR image (PNG/JPG/WebP, up to 12 MB). The browser decodes and crops it locally, rejects fixed amounts, debtors and references, and matches its IBAN to company settings. Save the company profile. New documents retain the QR with their company snapshot; old documents keep their saved settings. The separate payment-information page is not an automatically generated standardized Swiss QR-bill payment part. The payer enters the displayed amount in their banking app.



Phones use invoice cards, bottom navigation, touch-sized inputs and full-screen editors. Save opens a preview. Invoice/offer edits are recovered from local browser storage for seven days; this is device-local recovery, not cross-device draft sync. Save a draft to synchronize it with the account. Signing out removes local recovery entries for that account.



Run `tests/payment_bundle_test.py` on a fresh isolated local database with the same environment variables as the base suite. It includes the base/row tests plus one-time fees, partial settlements, immutable attachments and QR constraints.



## Discounts and PDF QR imports



New invoices/offers and editable drafts have a **Rabatt** section: choose percentage or a CHF amount. The discount reduces the subtotal before VAT; it is allocated proportionally across item net values, with cumulative cent rounding. Tax is then calculated on each reduced item. The discount must leave a positive invoice total. Existing documents default to no discount, and issued documents remain immutable. Discounts survive offer conversion and appear in previews, PDFs and attached source invoices. The CHF 50 dunning fee is separate and not discounted.



QR settings now also accept PDF files (12 MB, maximum 10 pages). PDF.js loads from this installation only when a PDF is selected, scans pages locally and extracts the first reusable Swiss bank QR code. Existing QR/IBAN checks still apply. Password-protected PDFs need an unprotected copy. Only the extracted QR image/payload is stored with company data, not the entire PDF. Build scripts copy the PDF.js reader/worker into assets/pdf-reader; include these assets when deploying.



Mobile navigation icons use distinct colours and the active tab has a matching background and top indicator. Run tests/discount_test.py on a fresh isolated local database for the full API regression suite plus discount cases.

