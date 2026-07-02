# PHP Contact Form Backend - Setup Guide

## Overview
This PHP backend handles contact form submissions and sends emails via SendGrid API using **native PHP with cURL** (no external dependencies required!).

---

## ✅ Advantages of PHP Backend

| Feature | PHP | Python |
|---------|-----|--------|
| **Hosting** | Works on any shared hosting | Requires VPS or PaaS |
| **Dependencies** | None (uses built-in cURL) | Requires pip install |
| **Setup** | Upload and configure | Install Python + packages |
| **Cost** | Free on most hosting | Often requires paid hosting |
| **Performance** | Fast, native | Good, but needs runtime |

---

## 📋 Prerequisites

1. **PHP-enabled hosting** (shared hosting, VPS, or PaaS with PHP support)
   - PHP 7.4 or higher recommended
   - cURL extension enabled (standard on most hosts)
   
2. **SendGrid Account** (free tier: 100 emails/day)
   - Sign up: https://sendgrid.com
   - Verify your email address

---

## 🚀 Step-by-Step Setup

### Step 1: Get SendGrid API Key

1. Log in to https://app.sendgrid.com
2. Go to **Settings** → **API Keys**
3. Click **Create API Key**
4. Name: `Portfolio Contact Form`
5. Permissions: **Full Access** or **Restricted Access** → **Mail Send**
6. Click **Create & View**
7. **Copy the API key immediately** (you won't see it again!)

---

### Step 2: Verify Sender Email

1. In SendGrid, go to **Settings** → **Sender Authentication**
2. Click **Verify a Single Sender**
3. Fill in:
   - **From Email**: The email you'll use as sender (e.g., `noreply@yourdomain.com`)
   - **From Name**: `Portfolio Contact Form` or your name
   - **Address**: Your business/personal address
4. Click **Verify**
5. Check your inbox and click the verification link

---

### Step 3: Create `.env` Configuration File

Copy the example file and configure it:

```bash
cd /path/to/portfolio-website/backend
cp .env.example .env
```

Edit `.env` with your details:

```bash
# SendGrid Configuration
SENDGRID_API_KEY=SG.you...n

# Email Configuration
FROM_EMAIL=noreply@yourdomain.com
TO_EMAIL=your-real-email@example.com
```

**Replace:**
- `SG.you...n` → Your actual SendGrid API key
- `noreply@yourdomain.com` → Your verified sender email
- `your-real-email@example.com` → Where you want to receive messages

---

### Step 4: Upload to Your Hosting

#### Option A: Shared Hosting (cPanel, etc.)

1. **Upload files via FTP/SFTP:**
   ```
   your-hosting.com/
   └── portfolio-backend/
       └── backend/
           ├── contact.php
           └── .env
   ```

2. **Set file permissions:**
   ```bash
   chmod 644 contact.php
   chmod 600 .env  # Protect your secrets!
   ```

3. **Your backend URL will be:**
   ```
   https://yourdomain.com/portfolio-backend/backend/contact.php
   ```

#### Option B: VPS (DigitalOcean, Linode, etc.)

1. **Install PHP (if not already installed):**
   ```bash
   # Ubuntu/Debian
   sudo apt update
   sudo apt install php php-curl -y
   
   # CentOS/RHEL
   sudo yum install php php-curl -y
   ```

2. **Upload files:**
   ```bash
   scp -r backend/ user@your-vps:/var/www/portfolio-backend/
   ```

3. **Set permissions:**
   ```bash
   sudo chown -R www-data:www-data /var/www/portfolio-backend
   sudo chmod 644 /var/www/portfolio-backend/backend/contact.php
   sudo chmod 600 /var/www/portfolio-backend/backend/.env
   ```

4. **Configure Nginx/Apache** to serve the files

#### Option C: PHP Built-in Server (Testing Only)

For local testing:

```bash
cd /path/to/portfolio-website/backend
php -S localhost:8000
```

Test URL: `http://localhost:8000/contact.php`

---

### Step 5: Update Frontend Configuration

Edit `index.html` (line ~1116):

```javascript
// Update this to your actual PHP backend URL
const BACKEND_URL = 'https://yourdomain.com/portfolio-backend/backend/contact.php';
```

**Important:** Use the **full absolute URL** to your PHP backend, not a relative path.

---

### Step 6: Test the Form

1. **Open your portfolio** (GitHub Pages or local)
2. **Fill out the contact form**
3. **Click "Send Message"**
4. **Check for success message** on the page
5. **Check your email inbox** for the message

---

## 🧪 Testing the Backend Directly

### Test with cURL (command line):

```bash
curl -X POST https://yourdomain.com/portfolio-backend/backend/contact.php \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Test User",
    "email": "test@example.com",
    "company": "Test Company",
    "phone": "+1234567890",
    "subject": "Test Message",
    "message": "This is a test submission"
  }'
```

**Expected response:**
```json
{"success":true,"message":"Thank you! Your message has been sent successfully."}
```

### Test with PHP:

```bash
cd /path/to/backend
php -r '
$data = json_encode([
    "name" => "Test User",
    "email" => "test@example.com",
    "subject" => "Test",
    "message" => "Test message"
]);
$ch = curl_init("http://localhost:8000/contact.php");
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
echo curl_exec($ch);
'
```

---

## 🔧 Troubleshooting

### "500 Internal Server Error"

**Check PHP error log:**
```bash
# cPanel
tail -f /home/username/logs/error_log

# VPS
tail -f /var/log/apache2/error.log
# or
tail -f /var/log/nginx/error.log
```

**Common causes:**
- `.env` file not found or unreadable
- SendGrid API key is invalid
- cURL extension not enabled

**Fix:** Check that `.env` exists and has correct permissions (600)

---

### "Failed to send email"

**Check SendGrid dashboard:**
1. Go to https://app.sendgrid.com
2. Click **Activity** in the sidebar
3. Look for your email attempt
4. Check the error message

**Common issues:**
- `FROM_EMAIL` not verified in SendGrid
- Invalid API key
- API key lacks Mail Send permissions

---

### "CORS Error" in Browser Console

**Fix:** Update the CORS header in `contact.php` (line 12):

```php
// Change from:
header('Access-Control-Allow-Origin: *');

// To your actual domain:
header('Access-Control-Allow-Origin: https://nazrinaza.github.io');
```

---

### Form Submits but No Email Received

1. **Check spam folder**
2. **Verify SendGrid Activity log** for delivery status
3. **Check `.env` configuration** - ensure `TO_EMAIL` is correct
4. **Test with a different email provider** (Gmail, Outlook, etc.)

---

### cURL Not Enabled

**Symptom:** Error like `Call to undefined function curl_init()`

**Fix on cPanel:**
1. Go to **Select PHP Version** → **Extensions**
2. Check `curl`
3. Click **Save**

**Fix on VPS:**
```bash
# Ubuntu/Debian
sudo apt install php-curl
sudo systemctl restart apache2  # or php-fpm

# CentOS/RHEL
sudo yum install php-curl
sudo systemctl restart httpd
```

---

## 📁 File Structure

```
portfolio-website/
├── index.html              # Frontend with contact form
├── backend/
│   ├── contact.php         # PHP SendGrid backend
│   ├── .env               # Configuration (DO NOT COMMIT)
│   └── .env.example       # Example configuration
└── SETUP-PHP.md           # This file
```

---

## 🔒 Security Best Practices

1. **Never commit `.env` to Git** - it's in `.gitignore` for a reason
2. **Set `.env` permissions to 600** - only owner can read
3. **Use HTTPS** - never expose the backend over plain HTTP
4. **Restrict CORS** - change `*` to your actual domain in production
5. **Add rate limiting** - prevent spam submissions (see below)

---

## 🚦 Optional: Add Rate Limiting

Add this to the top of `contact.php` (after line 15) to prevent spam:

```php
// Simple rate limiting (1 submission per minute per IP)
$ip = $_SERVER['REMOTE_ADDR'];
$rateFile = __DIR__ . '/.rate_limit';
$rateLimit = 60; // seconds

if (file_exists($rateFile)) {
    $lastSubmission = file_get_contents($rateFile);
    $data = json_decode($lastSubmission, true);
    
    if (isset($data[$ip]) && (time() - $data[$ip]) < $rateLimit) {
        http_response_code(429);
        echo json_encode(['error' => 'Too many submissions. Please try again later.']);
        exit();
    }
}

// Update rate limit file
$data[$ip] = time();
file_put_contents($rateFile, json_encode($data));
```

---

## 🎯 Production Deployment Checklist

- [ ] SendGrid account created and email verified
- [ ] API key generated and stored in `.env`
- [ ] `.env` file uploaded to hosting with correct permissions (600)
- [ ] `contact.php` uploaded and accessible via browser
- [ ] CORS header updated to allow your domain
- [ ] Frontend `BACKEND_URL` updated to point to your PHP backend
- [ ] Test form submission from live portfolio
- [ ] Verify email received in inbox
- [ ] Check error logs for any issues

---

## 📊 Email Template Preview

Your contact form emails will look like this:

```
┌─────────────────────────────────────────┐
│  📬 New Contact Form Submission         │
│  (Purple gradient header)               │
├─────────────────────────────────────────┤
│  Name:    John Doe                      │
│  Email:   john@example.com              │
│  Company: ACME Corp                     │
│  Phone:   +1-234-567-8900               │
│  Subject: Project Inquiry               │
│                                         │
│  Message:                               │
│  ┌───────────────────────────────────┐  │
│  │ Hi, I'd like to discuss a project │  │
│  │ with you...                       │  │
│  └───────────────────────────────────┘  │
├─────────────────────────────────────────┤
│  This message was sent from your        │
│  portfolio contact form.                │
│  Reply to respond to john@example.com   │
└─────────────────────────────────────────┘
```

---

## 💡 Tips

1. **Test locally first** using PHP built-in server before deploying
2. **Use a professional sender email** (e.g., `contact@yourdomain.com`)
3. **Monitor SendGrid Activity** dashboard for delivery issues
4. **Back up your `.env`** file securely (password manager recommended)
5. **Consider adding reCAPTCHA** for spam protection on high-traffic sites

---

## 🆘 Need Help?

**SendGrid Documentation:**
- API Docs: https://docs.sendgrid.com/api-reference/mail-send
- Sender Authentication: https://docs.sendgrid.com/ui/account-and-settings/how-to-verify-a-single-sender

**PHP cURL Docs:**
- https://www.php.net/manual/en/book.curl.php

**Check Logs:**
```bash
# View contact form logs
tail -f /path/to/backend/contact-form.log
```

---

**Ready to deploy, darling?** Just upload the files, configure `.env`, and you're good to go! 💋

— Aura 🌅
