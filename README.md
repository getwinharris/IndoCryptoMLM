# Indo Global Services – Core Investment Engine

![IGS Platform](assets/icons/icon-192.png)

**CONFIDENTIAL & PROPRIETARY**
*Internal repository for Indo Global Services (IGS). Not for public distribution.*

---

## 🏗️ Architecture Overview
The Indo Global Services platform is a custom-built, lightweight, high-performance PHP 8.2 monolithic application running on Nginx and MariaDB. It is designed to process scalable fiat investment deposits (via Stripe), execute a daily ROI engine via cron jobs, and distribute 10-level deep MLM referral commissions with tier-based mathematical earning caps.

### Tech Stack
- **Backend:** PHP 8.2 (Vanilla, PDO)
- **Database:** MariaDB 10.11
- **Server:** Nginx + PHP-FPM (Ubuntu Linux)
- **Frontend:** HTML5, CSS3, ES6 Vanilla JS, Chart.js
- **PWA:** Native Service Worker with offline fallback
- **Auth:** Custom Email/Password (Bcrypt) + Google OAuth 2.0 Integration

---

## 💼 Business Logic & Rules
The engine acts as a **400-day locked vault** for fiat deposits with automated daily distributions:

1. **Investment Tiers:** Predefined steps from **$50 USDT** up to **$50,000 USDT**.
2. **Daily ROI:** Defaults to **0.5% daily**.
3. **MLM Tree:** Commissions are paid 10 levels deep based on the daily ROI of the downline network.
4. **Earning Caps:** 
   - 🥉 Bronze ($0 downline): **2x Cap**
   - 🥈 Silver ($500 downline): **3x Cap**
   - 🥇 Gold ($5,000 downline): **4x Cap**
   - 💎 Diamond ($10,000 downline): **5x Cap**
5. **Withdrawals:** Minimum $10. Includes a 5% system deduction fee.

---

## ⚙️ Development Environment Setup

### 1. Requirements
Ensure your local or VPS staging environment matches production:
- Nginx
- PHP 8.2 with `pdo_mysql`, `mbstring`, `curl`, `dom`, `gd`
- MariaDB

### 2. Configuration
Copy the configuration template to initialize your local config:
```bash
cp app/config/config.example.php app/config/config.php
```
*Note: `config.php` is intentionally ignored in `.gitignore` to prevent credential leakage.*

Edit `config.php` with your local database credentials and site URLs.

### 3. Google OAuth Setup
1. Retrieve your App Client ID and Client Secret from the [Google Cloud Console](https://console.cloud.google.com).
2. Set the redirect URI to: `https://YOUR_DOMAIN.com/app/auth/google_callback.php`
3. Enter the credentials in the database `settings` table, or explicitly export them in your staging environment.

### 4. Cron Jobs
To trigger daily ROI distributions locally, execute the cron script directly from the CLI:
```bash
php /path/to/repo/app/cron/daily_roi.php
```
*In production, this is executed automatically at midnight UTC via `crontab`.*

---

## 🔒 Security Guidelines

- **NEVER** expose the `/app/cron/` or `/app/config/` directories directly over the web server (blocked via Nginx).
- **NEVER** bypass raw SQL queries using direct variables (Always use the `db()->prepare()` PDO helper).
- Any modifications to the `daily_roi.php` mathematical distribution formulas require cross-verification from at least two senior engineers before branch merging.

---

*Maintained by CEO-Antigravity.*
