# Indo Global Services Platform - Implementation Plan

**Project:** Fiat-Based Investment Platform for Asian Export Trade  
**Domain:** `indoglobalservices.in`  
**Server IP:** `72.61.244.195`  
**VPS Provider:** Hostinger  
**Date:** February 24, 2026

---

## Executive Summary

Establish **Indo Global Services**, a fiat-based (USD only) investment platform focused on the export of goods from Asia to global markets. This platform will be built by modifying the existing Nishue PHP script, removing all cryptocurrency features and implementing a strict Fiat-only architecture using Stripe for payments.

---

## Current Server Status

| Component | Status | Notes |
|-----------|--------|-------|
| **Domain** | ✅ Active | `indoglobalservices.in` pointing to VPS |
| **VPS IP** | ✅ `72.61.244.195` | Hostinger VPS |
| **Nginx** | ✅ v1.24.0 | Running, needs HTTPS configuration |
| **PHP** | ❌ Not Installed | Required for Laravel/Nishue |
| **SSL/HTTPS** | ⚠️ Partial | Hostinger CDN SSL active, needs direct VPS SSL |
| **MySQL/MariaDB** | ❌ Not Installed | Required for database |
| **Current Site** | ⚠️ Static Landing Page | Needs full platform deployment |

---

## Financial Engine Specifications

### Currency & Payments
| Feature | Specification |
|---------|---------------|
| **Currency** | USD (Fiat only) - NO BTC/ETH/USDT |
| **Deposit Gateway** | Stripe API Integration |
| **Investment Range** | $50 - $50,000 |
| **ROI** | 0.5% daily return for 400 days |
| **Maximum Return** | 2x - 5x based on tier (capped) |

### Investment ROI Calculation
```
Daily ROI = Investment Amount × 0.005 (0.5%)
Total Days = 400
Base Return = Investment + (Daily ROI × 400)
Base Return = Investment × 3.0 (300% over 400 days)

Capped by Tier:
- Bronze: 2x cap (200% total return)
- Silver: 3x cap (300% total return)
- Gold: 4x cap (400% total return)
- Diamond: 5x cap (500% total return)
```

---

## Multi-Level Marketing (MLM) & Tier System

### Direct Referral Bonus
- **Rate:** 5% flat one-time bonus
- **Trigger:** When direct referral makes their initial investment
- **Example:** Referral invests $100 → Sponsor gets $5 one-time

### 10-Level ROI Commission Structure

| Level | Commission Rate | Daily Earnings (on $1,000 downline) | Total Over 400 Days |
|-------|-----------------|-------------------------------------|---------------------|
| 1 | 25% | $1.25/day | $500 |
| 2 | 15% | $0.75/day | $300 |
| 3 | 10% | $0.50/day | $200 |
| 4 | 5% | $0.25/day | $100 |
| 5 | 5% | $0.25/day | $100 |
| 6 | 5% | $0.25/day | $100 |
| 7 | 5% | $0.25/day | $100 |
| 8 | 5% | $0.25/day | $100 |
| 9 | 10% | $0.50/day | $200 |
| 10 | 10% | $0.50/day | $200 |

**Calculation Example:**
- Downline invests $1,000
- Downline earns: $1,000 × 0.5% = $5/day
- Level 1 sponsor earns: $5 × 25% = $1.25/day

### Achievement Tiers

| Tier | Requirement | Earning Cap | Multiplier |
|------|-------------|-------------|------------|
| **Bronze** | Initial $50 investment | 2× investment | 200% |
| **Silver** | $500 total downline OR 5 direct referrals | 3× investment | 300% |
| **Gold** | $5,000 total downline | 4× investment | 400% |
| **Diamond** | $10,000 total downline | 5× investment | 500% |

### MLM Income & Tier Calculation Matrix

```
User Invests $100
│
├─► BRONZE (Initial)
│   Cap: 2× = $200 total earnings
│
├─► SILVER (Achieves $500 Downline)
│   Cap: 3× = $300 total earnings
│
├─► GOLD (Achieves $5,000 Downline)
│   Cap: 4× = $400 total earnings
│
└─► DIAMOND (Achieves $10,000 Downline)
    Cap: 5× = $500 total earnings (MAX)
```

**Important:** All earnings combined (Investment ROI + Referral Commission + Direct Bonus) are summed. Once the sum hits the current Tier Earning Cap, ALL earnings stop immediately.

---

## Withdrawal System

| Feature | Specification |
|---------|---------------|
| **Minimum Withdrawal** | $10 |
| **Service Charge** | 5% fee on withdrawal amount |
| **Security** | Email OTP verification required |
| **Processing** | Manual admin approval recommended |

**Withdrawal Calculation:**
```
Requested Amount: $100
Service Fee (5%): $5
Net Payout: $95
```

---

## Phase 1: VPS Environment Setup

### 1.1 Install Required Packages

```bash
# Update system
sudo apt update && sudo apt upgrade -y

# Install PHP 8.2 and required extensions for Laravel
sudo apt install -y php8.2 php8.2-fpm php8.2-mysql php8.2-curl php8.2-gd \
php8.2-mbstring php8.2-xml php8.2-zip php8.2-bcmath php8.2-intl \
php8.2-redis php8.2-soap php8.2-tokenizer php8.2-xmlrpc

# Install MySQL/MariaDB
sudo apt install -y mariadb-server mariadb-client

# Install additional utilities
sudo apt install -y git unzip curl redis-server supervisor
```

### 1.2 Configure Nginx with SSL

**File:** `/etc/nginx/sites-available/indoglobalservices.in`

```nginx
# HTTP - Force Redirect to HTTPS
server {
    listen 80;
    listen [::]:80;
    server_name indoglobalservices.in www.indoglobalservices.in;
    
    # Force HTTPS redirect
    return 301 https://$server_name$request_uri;
}

# HTTPS - Main Server Block
server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name indoglobalservices.in www.indoglobalservices.in;

    # SSL Configuration (Hostinger SSL)
    ssl_certificate /etc/ssl/certs/indoglobalservices.in.crt;
    ssl_certificate_key /etc/ssl/private/indoglobalservices.in.key;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers ECDHE-ECDSA-AES128-GCM-SHA256:ECDHE-RSA-AES128-GCM-SHA256:ECDHE-ECDSA-AES256-GCM-SHA384:ECDHE-RSA-AES256-GCM-SHA384;
    ssl_prefer_server_ciphers off;
    ssl_session_cache shared:SSL:10m;
    ssl_session_timeout 1d;

    # Security Headers
    add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;

    # Root directory
    root /var/www/indoglobalservices.in/public;
    index index.php index.html;

    # Logging
    access_log /var/log/nginx/indoglobalservices_access.log;
    error_log /var/log/nginx/indoglobalservices_error.log;

    # Main location
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # PHP-FPM configuration
    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.2-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_buffer_size 128k;
        fastcgi_buffers 4 256k;
        fastcgi_busy_buffers_size 256k;
    }

    # Deny access to sensitive files
    location ~ /\.ht {
        deny all;
    }

    location ~ /\.env {
        deny all;
    }

    # Cache static assets
    location ~* \.(jpg|jpeg|png|gif|ico|css|js|pdf|txt)$ {
        expires 30d;
        add_header Cache-Control "public, immutable";
    }
}
```

### 1.3 SSL Certificate Setup (Hostinger)

```bash
# Create SSL directory
sudo mkdir -p /etc/ssl/certs/indoglobalservices.in
sudo mkdir -p /etc/ssl/private/indoglobalservices.in

# Upload Hostinger SSL certificate files:
# 1. Upload your certificate (indoglobalservices.in.crt)
# 2. Upload your private key (indoglobalservices.in.key)
# 3. Upload CA bundle (ca-bundle.crt)

# Set proper permissions
sudo chmod 644 /etc/ssl/certs/indoglobalservices.in/*.crt
sudo chmod 600 /etc/ssl/private/indoglobalservices.in/*.key
sudo chown root:root /etc/ssl/private/indoglobalservices.in/*.key

# Combine certificate with CA bundle
sudo cat /etc/ssl/certs/indoglobalservices.in/indoglobalservices.in.crt \
         /etc/ssl/certs/indoglobalservices.in/ca-bundle.crt \
         > /etc/ssl/certs/indoglobalservices.in/fullchain.crt
```

### 1.4 Enable Nginx Configuration

```bash
# Remove default configuration
sudo rm /etc/nginx/sites-enabled/default

# Create symlink for indoglobalservices
sudo ln -s /etc/nginx/sites-available/indoglobalservices.in /etc/nginx/sites-enabled/

# Test Nginx configuration
sudo nginx -t

# Reload Nginx
sudo systemctl reload nginx
```

---

## Phase 2: Nishue Installation & Refactoring

### 2.1 Database Setup

```bash
# Secure MySQL installation
sudo mysql_secure_installation

# Create database and user
sudo mysql -u root -p
```

```sql
CREATE DATABASE indoglobalservices CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'igos_user'@'localhost' IDENTIFIED BY 'STRONG_PASSWORD_HERE';
GRANT ALL PRIVILEGES ON indoglobalservices.* TO 'igos_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

### 2.2 Install Nishue Platform

```bash
# Navigate to web root
cd /var/www/indoglobalservices.in

# Upload and extract Nishue (or clone from repository)
# Upload codecanyon-nishue-cryptocurrency-buy-sell-exchange-and-lending-with-mlm-system-live-crypto-compare.zip

unzip /root/Dev/codecanyon-nishue-cryptocurrency-buy-sell-exchange-and-lending-with-mlm-system-live-crypto-compare.zip

# Set proper permissions
sudo chown -R www-data:www-data /var/www/indoglobalservices.in
sudo chmod -R 755 /var/www/indoglobalservices.in
sudo chmod -R 775 /var/www/indoglobalservices.in/storage
sudo chmod -R 775 /var/www/indoglobalservices.in/bootstrap/cache
```

### 2.3 Remove Cryptocurrency Features

**Files to Modify:**

| File/Directory | Action |
|----------------|--------|
| `app/Models/Crypto*.php` | Remove or disable |
| `resources/views/crypto/*` | Remove views |
| `routes/web.php` | Remove crypto routes |
| `app/Http/Controllers/Crypto*` | Remove controllers |
| `database/migrations/*_crypto_*.php` | Skip or remove |
| Menu/Navigation views | Update to remove crypto links |

**Key Changes:**
1. Remove all BTC, ETH, USDT, and cryptocurrency references
2. Remove crypto wallet generation code
3. Remove crypto price API integrations (CoinGecko, CoinMarketCap, etc.)
4. Keep only fiat/USD functionality

### 2.4 Stripe Integration

**Install Stripe SDK:**
```bash
cd /var/www/indoglobalservices.in
composer require stripe/stripe-php
```

**Configuration (.env):**
```env
STRIPE_KEY=your_stripe_publishable_key
STRIPE_SECRET=your_stripe_secret_key
STRIPE_WEBHOOK_SECRET=your_webhook_secret
PAYMENT_CURRENCY=usd
```

**Deposit Controller Modifications:**
```php
// app/Http/Controllers/DepositController.php

use Stripe\Stripe;
use Stripe\Checkout\Session;

class DepositController extends Controller
{
    public function createDeposit(Request $request)
    {
        Stripe::setApiKey(config('services.stripe.secret'));
        
        $session = Session::create([
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price_data' => [
                    'currency' => 'usd',
                    'product_data' => [
                        'name' => 'Investment Deposit',
                    ],
                    'unit_amount' => $request->amount * 100, // cents
                ],
                'quantity' => 1,
            ]],
            'mode' => 'payment',
            'success_url' => route('deposit.success', ['session_id' => '{CHECKOUT_SESSION_ID}']),
            'cancel_url' => route('deposit.cancel'),
            'metadata' => [
                'user_id' => auth()->id(),
                'investment_type' => 'fiat_usd'
            ],
        ]);
        
        return redirect($session->url);
    }
}
```

---

## Phase 3: MLM & ROI Customization

### 3.1 Database Schema Modifications

```sql
-- Add tier tracking to users table
ALTER TABLE users ADD COLUMN current_tier ENUM('bronze', 'silver', 'gold', 'diamond') DEFAULT 'bronze';
ALTER TABLE users ADD COLUMN total_downline_investment DECIMAL(15,2) DEFAULT 0.00;
ALTER TABLE users ADD COLUMN direct_referrals_count INT DEFAULT 0;
ALTER TABLE users ADD COLUMN total_earnings DECIMAL(15,2) DEFAULT 0.00;
ALTER TABLE users ADD COLUMN earning_cap_multiplier DECIMAL(3,2) DEFAULT 2.00;

-- Add referral tracking
ALTER TABLE users ADD COLUMN referrer_id BIGINT UNSIGNED NULL;
ALTER TABLE users ADD COLUMN referrer_level_1 BIGINT UNSIGNED NULL;
ALTER TABLE users ADD COLUMN referrer_level_2 BIGINT UNSIGNED NULL;
ALTER TABLE users ADD COLUMN referrer_level_3 BIGINT UNSIGNED NULL;
ALTER TABLE users ADD COLUMN referrer_level_4 BIGINT UNSIGNED NULL;
ALTER TABLE users ADD COLUMN referrer_level_5 BIGINT UNSIGNED NULL;
ALTER TABLE users ADD COLUMN referrer_level_6 BIGINT UNSIGNED NULL;
ALTER TABLE users ADD COLUMN referrer_level_7 BIGINT UNSIGNED NULL;
ALTER TABLE users ADD COLUMN referrer_level_8 BIGINT UNSIGNED NULL;
ALTER TABLE users ADD COLUMN referrer_level_9 BIGINT UNSIGNED NULL;
ALTER TABLE users ADD COLUMN referrer_level_10 BIGINT UNSIGNED NULL;

-- Create earnings tracking table
CREATE TABLE daily_earnings (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    investment_id BIGINT UNSIGNED NOT NULL,
    earning_type ENUM('roi', 'level_1', 'level_2', 'level_3', 'level_4', 'level_5', 
                      'level_6', 'level_7', 'level_8', 'level_9', 'level_10', 'direct_bonus') NOT NULL,
    amount DECIMAL(15,8) NOT NULL,
    date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    UNIQUE KEY unique_user_investment_date (user_id, investment_id, date, earning_type)
);

-- Create tier upgrade tracking
CREATE TABLE tier_upgrades (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    previous_tier VARCHAR(20),
    new_tier VARCHAR(20) NOT NULL,
    reason VARCHAR(255),
    achieved_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Create withdrawal requests table with OTP
CREATE TABLE withdrawal_requests (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    amount DECIMAL(15,2) NOT NULL,
    service_fee DECIMAL(15,2) NOT NULL,
    net_amount DECIMAL(15,2) NOT NULL,
    otp_code VARCHAR(6),
    otp_verified BOOLEAN DEFAULT FALSE,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    admin_note TEXT,
    requested_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    processed_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id)
);
```

### 3.2 Tier System Logic

```php
// app/Services/TierService.php

class TierService
{
    const TIERS = [
        'bronze' => ['cap' => 2.0, 'min_downline' => 0, 'min_referrals' => 0],
        'silver' => ['cap' => 3.0, 'min_downline' => 500, 'min_referrals' => 5],
        'gold' => ['cap' => 4.0, 'min_downline' => 5000, 'min_referrals' => 0],
        'diamond' => ['cap' => 5.0, 'min_downline' => 10000, 'min_referrals' => 0],
    ];

    const LEVEL_COMMISSIONS = [
        1 => 0.25,
        2 => 0.15,
        3 => 0.10,
        4 => 0.05,
        5 => 0.05,
        6 => 0.05,
        7 => 0.05,
        8 => 0.05,
        9 => 0.10,
        10 => 0.10,
    ];

    public function checkAndUpdateTier(User $user): void
    {
        $totalDownline = $this->calculateTotalDownlineInvestment($user);
        $directReferrals = $user->directReferrals()->count();
        
        $user->total_downline_investment = $totalDownline;
        $user->direct_referrals_count = $directReferrals;
        
        // Determine new tier
        $newTier = 'bronze';
        $previousTier = $user->current_tier;
        
        if ($totalDownline >= 10000) {
            $newTier = 'diamond';
        } elseif ($totalDownline >= 5000) {
            $newTier = 'gold';
        } elseif ($totalDownline >= 500 || $directReferrals >= 5) {
            $newTier = 'silver';
        }
        
        // Update user
        $user->current_tier = $newTier;
        $user->earning_cap_multiplier = self::TIERS[$newTier]['cap'];
        $user->save();
        
        // Log tier upgrade
        if ($newTier !== $previousTier) {
            TierUpgrade::create([
                'user_id' => $user->id,
                'previous_tier' => $previousTier,
                'new_tier' => $newTier,
                'reason' => $this->getUpgradeReason($newTier, $totalDownline, $directReferrals)
            ]);
        }
    }

    public function canEarnMore(User $user): bool
    {
        $maxEarnings = $user->initial_investment * $user->earning_cap_multiplier;
        return $user->total_earnings < $maxEarnings;
    }

    public function calculateEarnableAmount(User $user, float $proposedAmount): float
    {
        $maxEarnings = $user->initial_investment * $user->earning_cap_multiplier;
        $remainingCap = $maxEarnings - $user->total_earnings;
        
        return min($proposedAmount, $remainingCap);
    }
}
```

### 3.3 Daily ROI Cron Job

```php
// app/Console/Commands/ProcessDailyROI.php

class ProcessDailyROI extends Command
{
    protected $signature = 'roi:process-daily';
    protected $description = 'Process daily ROI and MLM commissions';

    public function handle(TierService $tierService): int
    {
        $today = now()->toDateString();
        $dailyRoiRate = 0.005; // 0.5%
        
        // Get all active investments
        $investments = Investment::where('status', 'active')
            ->where('days_completed', '<', 400)
            ->get();
        
        foreach ($investments as $investment) {
            $user = $investment->user;
            
            // Check if user has reached their cap
            if (!$tierService->canEarnMore($user)) {
                $investment->status = 'completed_cap_reached';
                $investment->save();
                continue;
            }
            
            // Calculate daily ROI
            $dailyRoi = $investment->amount * $dailyRoiRate;
            $earnableRoi = $tierService->calculateEarnableAmount($user, $dailyRoi);
            
            if ($earnableRoi > 0) {
                // Record ROI earning
                DailyEarning::create([
                    'user_id' => $user->id,
                    'investment_id' => $investment->id,
                    'earning_type' => 'roi',
                    'amount' => $earnableRoi,
                    'date' => $today
                ]);
                
                $user->total_earnings += $earnableRoi;
                $investment->days_completed += 1;
                
                if ($investment->days_completed >= 400) {
                    $investment->status = 'completed';
                }
                
                $investment->save();
            }
            
            // Process MLM commissions
            $this->processMlmCommissions($investment, $earnableRoi, $tierService, $today);
            
            // Check for tier upgrade
            if ($investment->referrer_id) {
                $referrer = User::find($investment->referrer_id);
                if ($referrer) {
                    $tierService->checkAndUpdateTier($referrer);
                }
            }
        }
        
        $this->info('Daily ROI processing completed.');
        return 0;
    }

    private function processMlmCommissions(Investment $investment, float $dailyRoi, TierService $tierService, string $date): void
    {
        $levelCommissions = TierService::LEVEL_COMMISSIONS;
        
        for ($level = 1; $level <= 10; $level++) {
            $levelField = "referrer_level_{$level}";
            $sponsorId = $investment->user->$levelField;
            
            if (!$sponsorId) continue;
            
            $sponsor = User::find($sponsorId);
            if (!$sponsor || !$tierService->canEarnMore($sponsor)) continue;
            
            $commissionRate = $levelCommissions[$level];
            $commission = $dailyRoi * $commissionRate;
            $earnableCommission = $tierService->calculateEarnableAmount($sponsor, $commission);
            
            if ($earnableCommission > 0) {
                DailyEarning::create([
                    'user_id' => $sponsor->id,
                    'investment_id' => $investment->id,
                    'earning_type' => "level_{$level}",
                    'amount' => $earnableCommission,
                    'date' => $date
                ]);
                
                $sponsor->total_earnings += $earnableCommission;
                $sponsor->save();
            }
        }
    }
}
```

### 3.4 Setup Cron Job

```bash
# Edit crontab
crontab -e

# Add daily ROI processing (runs at 00:00 UTC daily)
0 0 * * * cd /var/www/indoglobalservices.in && /usr/bin/php artisan roi:process-daily >> /var/log/roi_processing.log 2>&1
```

---

## Phase 4: Withdrawal System with OTP

### 4.1 Withdrawal Controller

```php
// app/Http/Controllers/WithdrawalController.php

class WithdrawalController extends Controller
{
    const MIN_WITHDRAWAL = 10.00;
    const SERVICE_FEE_PERCENT = 0.05; // 5%

    public function request(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:' . self::MIN_WITHDRAWAL,
            'otp' => 'required|string|size:6'
        ]);
        
        $user = auth()->user();
        $amount = $request->amount;
        
        // Check balance
        if ($user->available_balance < $amount) {
            return back()->with('error', 'Insufficient balance');
        }
        
        // Verify OTP
        if (!$this->verifyOTP($user, $request->otp)) {
            return back()->with('error', 'Invalid OTP');
        }
        
        // Calculate fees
        $serviceFee = $amount * self::SERVICE_FEE_PERCENT;
        $netAmount = $amount - $serviceFee;
        
        // Create withdrawal request
        $withdrawal = WithdrawalRequest::create([
            'user_id' => $user->id,
            'amount' => $amount,
            'service_fee' => $serviceFee,
            'net_amount' => $netAmount,
            'otp_verified' => true,
            'status' => 'pending'
        ]);
        
        // Deduct from balance
        $user->available_balance -= $amount;
        $user->save();
        
        return redirect()->route('withdrawals.index')
            ->with('success', 'Withdrawal request submitted successfully!');
    }

    private function verifyOTP(User $user, string $otp): bool
    {
        $otpRecord = OtpCode::where('user_id', $user->id)
            ->where('code', $otp)
            ->where('expires_at', '>', now())
            ->where('verified', false)
            ->first();
        
        if (!$otpRecord) {
            return false;
        }
        
        $otpRecord->update(['verified' => true]);
        return true;
    }
}
```

### 4.2 Email OTP Service

```php
// app/Services/OTPService.php

class OTPService
{
    public function generateAndSend(User $user): string
    {
        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        
        // Store OTP
        OtpCode::create([
            'user_id' => $user->id,
            'code' => $code,
            'expires_at' => now()->addMinutes(10)
        ]);
        
        // Send email
        Mail::to($user->email)->send(new WithdrawalOTPMail($user, $code));
        
        return $code;
    }
}
```

```php
// app/Mail/WithdrawalOTPMail.php

class WithdrawalOTPMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public User $user, public string $code) {}

    public function build(): self
    {
        return $this->subject('Your Withdrawal OTP - Indo Global Services')
                    ->markdown('emails.withdrawal-otp');
    }
}
```

---

## Verification Plan

### 5.1 Security Verification

```bash
# Test HTTPS redirect
curl -I http://indoglobalservices.in
# Should return: HTTP/1.1 301 Moved Permanently
# Location: https://indoglobalservices.in/

# Test SSL certificate
openssl s_client -connect indoglobalservices.in:443 -servername indoglobalservices.in

# Test security headers
curl -I https://indoglobalservices.in | grep -E "(Strict-Transport-Security|X-Frame-Options|X-Content-Type-Options)"
```

### 5.2 ROI Cap Verification

```sql
-- Verify user has reached cap
SELECT 
    u.id,
    u.email,
    u.initial_investment,
    u.current_tier,
    u.earning_cap_multiplier,
    (u.initial_investment * u.earning_cap_multiplier) as max_earnings,
    u.total_earnings,
    CASE 
        WHEN u.total_earnings >= (u.initial_investment * u.earning_cap_multiplier) 
        THEN 'CAP REACHED' 
        ELSE 'ACTIVE' 
    END as status
FROM users u
WHERE u.total_earnings >= (u.initial_investment * u.earning_cap_multiplier);
```

### 5.3 Cron Job Verification

```bash
# Check if cron is running
systemctl status cron

# View processing logs
tail -f /var/log/roi_processing.log

# Manual test run
cd /var/www/indoglobalservices.in && php artisan roi:process-daily
```

---

## Security Checklist

- [ ] Force HTTPS redirect (HTTP → HTTPS)
- [ ] Install valid SSL certificate
- [ ] Enable HSTS header
- [ ] Configure security headers (X-Frame-Options, X-Content-Type-Options, etc.)
- [ ] Remove all cryptocurrency code
- [ ] Implement Stripe with proper key management
- [ ] Email OTP for withdrawals
- [ ] Rate limiting on API endpoints
- [ ] SQL injection prevention (use Eloquent ORM)
- [ ] XSS prevention (escape all outputs)
- [ ] CSRF protection on all forms
- [ ] Admin panel IP whitelist
- [ ] Regular database backups
- [ ] Firewall configuration (UFW)

---

## Timeline Estimate

| Phase | Duration | Dependencies |
|-------|----------|--------------|
| Phase 1: VPS Setup | 1-2 days | Domain DNS propagation |
| Phase 2: Nishue Install | 2-3 days | PHP/MySQL installation |
| Phase 3: MLM Customization | 5-7 days | Database schema ready |
| Phase 4: Withdrawal System | 2-3 days | Email service configured |
| Testing & Verification | 2-3 days | All features implemented |
| **Total** | **12-18 days** | |

---

## Notes & Warnings

⚠️ **CRITICAL:** We are working directly on a production VPS. Always:
1. Take server snapshots before major changes
2. Test in a staging environment first if possible
3. Keep backups of all modified files
4. Document all changes made

⚠️ **Legal Compliance:** Ensure this investment platform complies with:
- Indian financial regulations (RBI guidelines)
- International money transmission laws
- KYC/AML requirements
- Securities regulations in target markets

⚠️ **Stripe Requirements:**
- Stripe must approve your business model
- Investment platforms may be restricted
- Have alternative payment processors ready

---

**Document Version:** 1.0  
**Last Updated:** February 24, 2026  
**Prepared By:** Development Team
