# Email Configuration Guide for Production

## ⚠️ Important: Vercel and PHP Compatibility

**Vercel does NOT natively support PHP applications.** Vercel is designed for:
- Node.js serverless functions
- Python serverless functions
- Static sites
- Next.js, Nuxt.js, etc.

### For PHP Applications, Consider These Alternatives:

1. **Traditional PHP Hosting:**
   - **Shared Hosting:** cPanel, GoDaddy, Bluehost (easy, affordable)
   - **VPS:** DigitalOcean, Linode, Vultr (more control)
   - **Managed PHP:** Platform.sh, Acquia, Pantheon

2. **Cloud Platforms with PHP Support:**
   - **Railway** - Supports PHP with MySQL
   - **Render** - Supports PHP web services
   - **Heroku** - Supports PHP (paid)
   - **AWS Elastic Beanstalk** - Supports PHP
   - **Google Cloud Run** - Can containerize PHP
   - **Azure App Service** - Supports PHP

3. **If You Must Use Vercel:**
   - Frontend only on Vercel (static HTML/CSS/JS)
   - Backend API on a PHP hosting service
   - Connect frontend to backend API via CORS

---

## Email Configuration Options

### Option 1: PHPMailer with SMTP (Recommended)

This is the most reliable method for production.

#### Step 1: Install PHPMailer

```bash
composer require phpmailer/phpmailer
```

#### Step 2: Update Email Class

The Email class has been updated to support PHPMailer. See `backend/classes/Email.php`.

#### Step 3: Configure Environment Variables

Create a `.env` file or set environment variables:

```env
# Email Configuration
SMTP_ENABLED=true
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_USERNAME=your-email@gmail.com
SMTP_PASSWORD=your-app-password
SMTP_ENCRYPTION=tls
SMTP_FROM_EMAIL=noreply@yourdomain.com
SMTP_FROM_NAME=Splitter App
APP_BASE_URL=https://yourdomain.com
ENVIRONMENT=production
```

---

### Option 2: SendGrid (API-based)

SendGrid is excellent for transactional emails and works well with serverless environments.

#### Setup:

1. **Create SendGrid Account:**
   - Sign up at https://sendgrid.com
   - Verify your sender email
   - Generate API key

2. **Install SendGrid PHP Library:**
   ```bash
   composer require sendgrid/sendgrid
   ```

3. **Environment Variables:**
   ```env
   EMAIL_SERVICE=sendgrid
   SENDGRID_API_KEY=SG.your_api_key_here
   SMTP_FROM_EMAIL=noreply@yourdomain.com
   SMTP_FROM_NAME=Splitter App
   APP_BASE_URL=https://yourdomain.com
   ```

---

### Option 3: Mailgun

Mailgun offers excellent deliverability and analytics.

#### Setup:

1. **Create Mailgun Account:**
   - Sign up at https://mailgun.com
   - Verify your domain
   - Get API key

2. **Install Mailgun PHP Library:**
   ```bash
   composer require mailgun/mailgun-php symfony/http-client
   ```

3. **Environment Variables:**
   ```env
   EMAIL_SERVICE=mailgun
   MAILGUN_API_KEY=your_api_key
   MAILGUN_DOMAIN=mg.yourdomain.com
   SMTP_FROM_EMAIL=noreply@yourdomain.com
   SMTP_FROM_NAME=Splitter App
   APP_BASE_URL=https://yourdomain.com
   ```

---

### Option 4: Resend (Modern Alternative)

Resend is modern, simple, and works great with Vercel/serverless.

#### Setup:

1. **Create Resend Account:**
   - Sign up at https://resend.com
   - Get API key

2. **Install Resend PHP Library:**
   ```bash
   composer require resend/resend-php
   ```

3. **Environment Variables:**
   ```env
   EMAIL_SERVICE=resend
   RESEND_API_KEY=re_your_api_key
   SMTP_FROM_EMAIL=noreply@yourdomain.com
   SMTP_FROM_NAME=Splitter App
   APP_BASE_URL=https://yourdomain.com
   ```

---

### Option 5: AWS SES (Scalable)

Amazon SES is cost-effective for high volumes.

#### Setup:

1. **AWS SES Setup:**
   - Create AWS account
   - Verify email/domain in SES
   - Create IAM user with SES permissions
   - Generate access keys

2. **Install AWS SDK:**
   ```bash
   composer require aws/aws-sdk-php
   ```

3. **Environment Variables:**
   ```env
   EMAIL_SERVICE=ses
   AWS_ACCESS_KEY_ID=your_access_key
   AWS_SECRET_ACCESS_KEY=your_secret_key
   AWS_REGION=us-east-1
   SMTP_FROM_EMAIL=noreply@yourdomain.com
   SMTP_FROM_NAME=Splitter App
   APP_BASE_URL=https://yourdomain.com
   ```

---

## Gmail SMTP Configuration (For Testing)

If you want to use Gmail for testing:

1. **Enable 2-Factor Authentication** on your Google account
2. **Generate App Password:**
   - Go to Google Account settings
   - Security → 2-Step Verification → App passwords
   - Create app password for "Mail"
   - Use this password (not your Gmail password)

3. **Environment Variables:**
   ```env
   SMTP_ENABLED=true
   SMTP_HOST=smtp.gmail.com
   SMTP_PORT=587
   SMTP_USERNAME=your-email@gmail.com
   SMTP_PASSWORD=your-16-char-app-password
   SMTP_ENCRYPTION=tls
   SMTP_FROM_EMAIL=your-email@gmail.com
   SMTP_FROM_NAME=Splitter App
   ```

---

## Production Email Service Comparison

| Service | Free Tier | Best For | Difficulty |
|---------|-----------|----------|------------|
| **SendGrid** | 100 emails/day | General use | Easy |
| **Mailgun** | 100 emails/day (3 months) | Developer-friendly | Easy |
| **Resend** | 3,000 emails/month | Modern apps, Vercel | Easy |
| **AWS SES** | 62,000 emails/month | High volume | Medium |
| **PHPMailer + SMTP** | Varies by provider | Custom SMTP | Easy-Medium |

---

## Recommended Setup for Production

### For Small to Medium Projects:
1. **Use Resend or SendGrid** - Simple API, good free tier
2. **Verify your domain** - Better deliverability
3. **Set up SPF/DKIM records** - Prevents spam

### For High Volume:
1. **Use AWS SES** - Cost-effective at scale
2. **Set up bounce/complaint handling**
3. **Monitor reputation**

---

## Environment Variables Reference

```env
# Required
SMTP_FROM_EMAIL=noreply@yourdomain.com
SMTP_FROM_NAME=Splitter App
APP_BASE_URL=https://yourdomain.com

# For PHPMailer/SMTP
SMTP_ENABLED=true
SMTP_HOST=smtp.example.com
SMTP_PORT=587
SMTP_USERNAME=your-email@example.com
SMTP_PASSWORD=your-password
SMTP_ENCRYPTION=tls  # or 'ssl' for port 465

# For SendGrid
EMAIL_SERVICE=sendgrid
SENDGRID_API_KEY=SG.your_key

# For Mailgun
EMAIL_SERVICE=mailgun
MAILGUN_API_KEY=your_key
MAILGUN_DOMAIN=mg.yourdomain.com

# For Resend
EMAIL_SERVICE=resend
RESEND_API_KEY=re_your_key

# For AWS SES
EMAIL_SERVICE=ses
AWS_ACCESS_KEY_ID=your_key
AWS_SECRET_ACCESS_KEY=your_secret
AWS_REGION=us-east-1

# General
ENVIRONMENT=production  # or 'development' to log emails instead
```

---

## Testing Email Configuration

### Development Mode:
Set `ENVIRONMENT=development` to log emails instead of sending:
```env
ENVIRONMENT=development
```

Check PHP error logs for email content.

### Production Testing:
1. Send test invitation email
2. Check inbox (and spam folder)
3. Verify links work correctly
4. Check email formatting

---

## DNS Configuration (For Custom Domain)

If sending from your domain, configure:

### SPF Record:
```
TXT @ "v=spf1 include:_spf.google.com ~all"
```
(Adjust based on your email service)

### DKIM Record:
(Provided by your email service)

### DMARC Record:
```
TXT _dmarc "v=DMARC1; p=quarantine; rua=mailto:admin@yourdomain.com"
```

---

## Troubleshooting

### Emails Not Sending:
1. Check environment variables are set correctly
2. Verify SMTP credentials
3. Check firewall/port blocking (587, 465, 25)
4. Review PHP error logs
5. Test SMTP connection manually

### Emails Going to Spam:
1. Verify SPF/DKIM records
2. Use verified sender email
3. Avoid spam trigger words
4. Include unsubscribe link (for bulk emails)
5. Warm up new sending domain/IP

### PHP mail() Function Issues:
- `mail()` function is unreliable on many hosts
- Use PHPMailer or email service API instead
- Better deliverability and error handling

---

## Security Best Practices

1. **Never commit credentials** - Use environment variables
2. **Use API keys** - Not passwords when possible
3. **Rotate keys regularly** - Update credentials periodically
4. **Use TLS/SSL** - Encrypt SMTP connections
5. **Verify sender domain** - Prevents spoofing
6. **Monitor bounce rates** - Maintain good reputation

---

## Next Steps

1. Choose an email service provider
2. Update `composer.json` with required packages
3. Update `Email.php` to use the chosen service
4. Set environment variables on your hosting platform
5. Test email sending
6. Configure DNS records (if using custom domain)
7. Monitor email delivery and adjust as needed

