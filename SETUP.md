# Contact Form Backend Setup Guide

## Overview
This backend handles contact form submissions and sends emails via SendGrid API.

## Prerequisites

### 1. Get SendGrid API Key
1. Sign up at https://sendgrid.com
2. Verify your email address
3. Go to **Settings** → **API Keys**
4. Click **Create API Key**
5. Name it (e.g., "Portfolio Contact Form")
6. Select **Full Access** or **Restricted Access** (Mail Send permission)
7. Copy the API key (you won't see it again!)

### 2. Install Dependencies

```bash
cd /home/mtadmin/.hermes/workspaces/bell/portfolio-website/backend
pip3 install -r requirements.txt
```

### 3. Configure Environment Variables

Create a `.env` file in the `backend` directory:

```bash
# SendGrid Configuration
SENDGRID_API_KEY=SG.your-sendgrid-api-key-here

# Email Configuration
FROM_EMAIL=noreply@yourdomain.com
TO_EMAIL=your-actual-email@example.com

# Server Configuration
PORT=8000
```

**Important:** Replace:
- `your-sendgrid-api-key-here` with your actual SendGrid API key
- `noreply@yourdomain.com` with your sender email (must be verified in SendGrid)
- `your-actual-email@example.com` with the email where you want to receive messages

### 4. Verify Sender Email (SendGrid Requirement)

1. Go to SendGrid **Settings** → **Sender Authentication**
2. Click **Verify a Single Sender**
3. Enter the email you used for `FROM_EMAIL`
4. Check your inbox and click the verification link

## Running the Backend

### Local Development

```bash
cd /home/mtadmin/.hermes/workspaces/bell/portfolio-website/backend
python3 contact-form.py
```

The server will start on `http://localhost:8000`

### Test the Backend

```bash
curl -X POST http://localhost:8000/submit \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Test User",
    "email": "test@example.com",
    "subject": "Test Message",
    "message": "This is a test"
  }'
```

Expected response:
```json
{"success": true, "message": "Thank you! Your message has been sent successfully."}
```

## Deploying to Production

### Option 1: Deploy on VPS/Server

1. **Install Python and dependencies:**
   ```bash
   sudo apt update
   sudo apt install python3 python3-pip
   cd /path/to/backend
   pip3 install -r requirements.txt
   ```

2. **Set up environment variables** (create `.env` file as shown above)

3. **Run with systemd (recommended):**
   
   Create `/etc/systemd/system/contact-form.service`:
   ```ini
   [Unit]
   Description=Contact Form Backend
   After=network.target

   [Service]
   Type=simple
   User=www-data
   WorkingDirectory=/path/to/backend
   EnvironmentFile=/path/to/backend/.env
   ExecStart=/usr/bin/python3 /path/to/backend/contact-form.py
   Restart=always

   [Install]
   WantedBy=multi-user.target
   ```

4. **Enable and start:**
   ```bash
   sudo systemctl enable contact-form
   sudo systemctl start contact-form
   sudo systemctl status contact-form
   ```

5. **Set up Nginx reverse proxy:**
   
   Create `/etc/nginx/sites-available/portfolio`:
   ```nginx
   server {
       listen 80;
       server_name yourdomain.com;

       location /api/submit {
           proxy_pass http://127.0.0.1:8000/submit;
           proxy_set_header Host $host;
           proxy_set_header X-Real-IP $remote_addr;
           proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
       }

       location / {
           root /path/to/portfolio-website;
           index index.html;
           try_files $uri $uri/ =404;
       }
   }
   ```

6. **Enable and reload Nginx:**
   ```bash
   sudo ln -s /etc/nginx/sites-available/portfolio /etc/nginx/sites-enabled/
   sudo nginx -t
   sudo systemctl reload nginx
   ```

### Option 2: Deploy on Railway/Render/Heroku

1. **Create `Procfile`:**
   ```
   web: python backend/contact-form.py
   ```

2. **Set environment variables** in your platform's dashboard:
   - `SENDGRID_API_KEY`
   - `FROM_EMAIL`
   - `TO_EMAIL`

3. **Deploy** by connecting your GitHub repository

## Update Frontend Configuration

After deploying the backend, update the `BACKEND_URL` in `index.html`:

```javascript
// Line ~1115 in index.html
const BACKEND_URL = 'https://yourdomain.com/api/submit'; // Your production URL
```

For GitHub Pages + external backend, use the full URL:
```javascript
const BACKEND_URL = 'https://your-backend.railway.app/submit';
```

## Security Notes

1. **Never commit `.env` file** - it's in `.gitignore` for a reason
2. **Use HTTPS** in production - never expose the backend over plain HTTP
3. **Rate limiting** - consider adding rate limiting for production use
4. **CORS** - currently set to allow all origins (`*`), restrict to your domain in production

## Troubleshooting

### "SendGrid API key not configured"
- Check that `SENDGRID_API_KEY` is set in `.env`
- Restart the server after changing environment variables

### "Sender not verified"
- Verify your `FROM_EMAIL` in SendGrid dashboard
- Make sure the email matches exactly

### "Connection refused" on frontend
- Ensure backend is running: `sudo systemctl status contact-form`
- Check firewall: `sudo ufw allow 8000`
- Verify `BACKEND_URL` in `index.html` is correct

### Form submits but no email received
- Check SendGrid dashboard for activity logs
- Verify `TO_EMAIL` is correct
- Check spam folder
- Look at backend logs: `sudo journalctl -u contact-form -f`

## File Structure

```
portfolio-website/
├── index.html              # Frontend with contact form
├── backend/
│   ├── contact-form.py     # SendGrid backend server
│   ├── requirements.txt    # Python dependencies
│   └── .env               # Environment variables (DO NOT COMMIT)
└── SETUP.md               # This file
```

## Testing the Full Flow

1. Start the backend server
2. Open `index.html` in a browser (or via GitHub Pages)
3. Fill out the contact form
4. Click "Send Message"
5. Check for success message on the page
6. Check your email inbox for the message

---

**Need help?** Check the logs:
```bash
# Systemd service logs
sudo journalctl -u contact-form -f

# Or if running manually, check stderr output
```
