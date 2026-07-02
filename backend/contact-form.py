#!/usr/bin/env python3
"""
SendGrid Contact Form Backend
Handles contact form submissions and sends emails via SendGrid API
"""

import os
import json
import sys
from http.server import HTTPServer, BaseHTTPRequestHandler
from urllib.parse import parse_qs
import sendgrid
from sendgrid.helpers.mail import Mail, Email, To, Content

# Configuration
SENDGRID_API_KEY = os.environ.get('SENDGRID_API_KEY', '')
FROM_EMAIL = os.environ.get('FROM_EMAIL', 'noreply@yourdomain.com')
TO_EMAIL = os.environ.get('TO_EMAIL', 'your-email@example.com')
PORT = int(os.environ.get('PORT', 8000))

class ContactFormHandler(BaseHTTPRequestHandler):
    def do_POST(self):
        """Handle contact form submission"""
        try:
            # Read POST data
            content_length = int(self.headers.get('Content-Length', 0))
            post_data = self.rfile.read(content_length).decode('utf-8')
            
            # Parse form data
            if self.headers.get('Content-Type') == 'application/json':
                data = json.loads(post_data)
            else:
                data = parse_qs(post_data)
                # Flatten parse_qs results
                data = {k: v[0] if len(v) == 1 else ', '.join(v) for k, v in data.items()}
            
            # Validate required fields
            required_fields = ['name', 'email', 'subject']
            for field in required_fields:
                if not data.get(field):
                    self.send_error_response(400, f'Missing required field: {field}')
                    return
            
            # Send email via SendGrid
            email_sent = self.send_email(data)
            
            if email_sent:
                self.send_success_response({
                    'success': True,
                    'message': 'Thank you! Your message has been sent successfully.'
                })
            else:
                self.send_error_response(500, 'Failed to send email')
                
        except json.JSONDecodeError:
            self.send_error_response(400, 'Invalid JSON data')
        except Exception as e:
            self.send_error_response(500, f'Server error: {str(e)}')
    
    def do_OPTIONS(self):
        """Handle CORS preflight requests"""
        self.send_response(200)
        self.send_cors_headers()
        self.end_headers()
    
    def send_email(self, data):
        """Send email via SendGrid"""
        if not SENDGRID_API_KEY:
            print("ERROR: SENDGRID_API_KEY not configured", file=sys.stderr)
            return False
        
        try:
            # Create email content
            subject = f"Contact Form: {data.get('subject', 'No Subject')}"
            
            # Build HTML content
            html_content = f"""
            <html>
            <body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
                <h2 style="color: #667eea;">New Contact Form Submission</h2>
                <table style="width: 100%; max-width: 600px; border-collapse: collapse;">
                    <tr>
                        <td style="padding: 10px 0; font-weight: bold;">Name:</td>
                        <td style="padding: 10px 0;">{data.get('name', 'N/A')}</td>
                    </tr>
                    <tr>
                        <td style="padding: 10px 0; font-weight: bold;">Email:</td>
                        <td style="padding: 10px 0;">{data.get('email', 'N/A')}</td>
                    </tr>
                    {f"<tr><td style='padding: 10px 0; font-weight: bold;'>Company:</td><td style='padding: 10px 0;'>{data.get('company', 'N/A')}</td></tr>" if data.get('company') else ''}
                    {f"<tr><td style='padding: 10px 0; font-weight: bold;'>Phone:</td><td style='padding: 10px 0;'>{data.get('phone', 'N/A')}</td></tr>" if data.get('phone') else ''}
                    <tr>
                        <td style="padding: 10px 0; font-weight: bold;">Subject:</td>
                        <td style="padding: 10px 0;">{data.get('subject', 'N/A')}</td>
                    </tr>
                    <tr>
                        <td style="padding: 10px 0; font-weight: bold; vertical-align: top;">Message:</td>
                        <td style="padding: 10px 0;">{data.get('message', 'N/A')}</td>
                    </tr>
                </table>
                <hr style="border: none; border-top: 1px solid #eee; margin: 20px 0;">
                <p style="color: #999; font-size: 12px;">This message was sent from your portfolio contact form.</p>
            </body>
            </html>
            """
            
            # Create plain text version
            text_content = f"""
New Contact Form Submission

Name: {data.get('name', 'N/A')}
Email: {data.get('email', 'N/A')}
{f"Company: {data.get('company', 'N/A')}" if data.get('company') else ''}
{f"Phone: {data.get('phone', 'N/A')}" if data.get('phone') else ''}
Subject: {data.get('subject', 'N/A')}

Message:
{data.get('message', 'N/A')}
            """.strip()
            
            # Send via SendGrid
            message = Mail(
                from_email=Email(FROM_EMAIL),
                to_emails=To(TO_EMAIL),
                subject=subject,
                plain_text_content=text_content,
                html_content=Content("text/html", html_content)
            )
            
            # Add reply-to header
            message.reply_to = Email(data.get('email', TO_EMAIL))
            
            sg = sendgrid.SendGridAPIClient(api_key=SENDGRID_API_KEY)
            response = sg.send(message)
            
            print(f"SendGrid response: {response.status_code}", file=sys.stderr)
            return response.status_code == 202
            
        except Exception as e:
            print(f"SendGrid error: {str(e)}", file=sys.stderr)
            return False
    
    def send_success_response(self, data):
        """Send JSON success response"""
        self.send_response(200)
        self.send_cors_headers()
        self.send_header('Content-Type', 'application/json')
        self.end_headers()
        self.wfile.write(json.dumps(data).encode('utf-8'))
    
    def send_error_response(self, status_code, message):
        """Send JSON error response"""
        self.send_response(status_code)
        self.send_cors_headers()
        self.send_header('Content-Type', 'application/json')
        self.end_headers()
        self.wfile.write(json.dumps({'error': message}).encode('utf-8'))
    
    def send_cors_headers(self):
        """Send CORS headers for cross-origin requests"""
        self.send_header('Access-Control-Allow-Origin', '*')
        self.send_header('Access-Control-Allow-Methods', 'POST, OPTIONS')
        self.send_header('Access-Control-Allow-Headers', 'Content-Type')
    
    def log_message(self, format, *args):
        """Custom log format"""
        print(f"[{self.log_date_time_string()}] {args[0]}", file=sys.stderr)

def main():
    if not SENDGRID_API_KEY:
        print("WARNING: SENDGRID_API_KEY not set. Email sending will fail.", file=sys.stderr)
        print("Set it with: export SENDGRID_API_KEY='your-api-key'", file=sys.stderr)
    
    print(f"Starting contact form server on port {PORT}...", file=sys.stderr)
    print(f"From: {FROM_EMAIL}, To: {TO_EMAIL}", file=sys.stderr)
    
    server = HTTPServer(('0.0.0.0', PORT), ContactFormHandler)
    try:
        server.serve_forever()
    except KeyboardInterrupt:
        print("\nShutting down server...", file=sys.stderr)
        server.shutdown()

if __name__ == '__main__':
    main()
