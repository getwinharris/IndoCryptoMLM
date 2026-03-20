# Indo Global Services – Production Investment Platform

![IGS Platform](assets/banner.svg)

**OVERVIEW**
Indo Global Services is a high-performance investment engine featuring a 10-level MLM network, automated daily ROI distributions, and secure Stripe-powered wallet funding.

---

## 🏗️ Production Tech Stack
- **Engine:** PHP 8.2 (Vanilla, High Performance)
- **Database:** MariaDB 10.11 (Optimized Indexes)
- **Server:** Nginx + PHP-FPM (Strict SSL)
- **Branding:** High-Fidelity Animated SVG (v7 "Ultra-Crowd")
- **PWA:** Native Service Worker with multi-device support

---

## 💼 Core Business Rules
- **Daily ROI:** 0.5% (Configurable via Admin)
- **Deposit Minimum:** $10 (Via Stripe Secure)
- **Withdrawal Minimum:** $10 (5% system fee applies)
- **MLM Commissions:** 10 Levels Deep (Real-time calculation)
- **Investment Duration:** 400 Days (Locked Principal)
- **Earnings Caps:** Tiers range from Bronze (2x) to Diamond (5x of investment)

---

## 🛠️ Internal Testing Credentials (IGS TEAM ONLY)
To access the testing environment during the certification phase:
- **Admin Panel:** `https://indoglobalservices.in/app/auth/login.php`
- **Username:** `admin@indoglobalservices.in`
- **Password:** `AdminSecure@2026!`

---

## 🚀 Post-Deployment Maintenance

### 1. Daily ROI Cron Job
The ROI engine is automated via crontab. Verify it is running daily at midnight:
```bash
0 0 * * * /usr/bin/php /root/Dev/app/cron/daily_roi.php >> /var/log/igs_cron.log 2>&1
```

### 2. Transaction Logs
All financial events are logged in the `transactions` table. System auditors can review these via the Admin Dashboard.

### 3. Financial Gateways
Ensure the following keys are set in the `settings` table before launching to the public:
- `stripe_pub`: Stripe Publishable Key
- `stripe_secret`: Stripe Secret Key
- `smtp_host`, `smtp_user`, `smtp_pass`: SMTP Credentials (for OTP/Email)

---

## 🔍 Pre-Launch Audit Checklist - 2026-03-21
- [x] Banner SVG Sync (GitHub/README)
- [x] Logo SVG Sync (All Pages)
- [x] MLM Level Commissions Verification
- [x] Stripe-to-Wallet Hook Verification
- [x] Mobile PWA Installation Logic
- [ ] SMTP Production Keys Update (Action Required by Admin)
- [ ] Stripe Live Keys Update (Action Required by Admin)

---

*Maintained by Antigravity CEO AI.*
