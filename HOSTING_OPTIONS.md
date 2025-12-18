# Hosting Options for PHP/MySQL Application

## ⚠️ Vercel Limitation

**Vercel does NOT support PHP applications natively.** Vercel is designed for:
- Node.js serverless functions
- Python serverless functions  
- Static sites and JAMstack apps
- Next.js, Nuxt.js, SvelteKit, etc.

---

## Recommended Hosting Solutions

### Option 1: Separate Frontend/Backend (Recommended for Vercel)

If you want to use Vercel:

**Frontend (Vercel):**
- Deploy static HTML/CSS/JS files to Vercel
- Frontend makes API calls to backend server
- Configure CORS on backend

**Backend (Separate PHP Hosting):**
- Host PHP API on any PHP hosting service
- Configure CORS to allow Vercel domain
- Database can be on same server or separate MySQL service

**Benefits:**
- Fast frontend delivery via Vercel CDN
- Can use Vercel's edge network
- Flexible backend hosting

**Drawbacks:**
- More complex setup
- Two separate deployments
- Need to manage CORS

---

### Option 2: All-in-One PHP Hosting (Simplest)

Host everything together on a platform that supports PHP + MySQL:

#### A. Shared Hosting (Easiest & Affordable)
- **cPanel Hosting:** Bluehost, HostGator, SiteGround
- **Cost:** $3-10/month
- **Pros:** Easy setup, includes email, MySQL, PHP
- **Cons:** Limited resources, shared server

#### B. VPS (More Control)
- **DigitalOcean:** $5-10/month
- **Linode:** $5-10/month
- **Vultr:** $2.50-6/month
- **Pros:** Full control, scalable
- **Cons:** Need server management skills

#### C. Managed PHP Platforms
- **Platform.sh:** Auto-scaling, Git-based
- **Acquia:** Drupal-focused but supports PHP
- **Pantheon:** WordPress-focused but supports PHP
- **Pros:** Easy deployment, managed infrastructure
- **Cons:** More expensive

---

### Option 3: Cloud Platforms

#### Railway.app ⭐ (Recommended)
- **Supports:** PHP, MySQL, Node.js
- **Pricing:** Pay-as-you-go, generous free tier
- **Pros:** 
  - Easy deployment from GitHub
  - Auto-provisions MySQL
  - Environment variables management
  - Automatic HTTPS
- **Setup:** Connect GitHub repo, auto-detects PHP

#### Render.com ⭐ (Great Alternative)
- **Supports:** PHP Web Services, PostgreSQL/MySQL
- **Pricing:** Free tier available, $7/month for MySQL
- **Pros:**
  - Easy setup
  - Auto-deploy from Git
  - Free SSL
  - Managed databases
- **Cons:** Free tier has limitations

#### Heroku
- **Supports:** PHP via buildpack
- **Pricing:** $7/month + database addon
- **Pros:** Well-documented, many addons
- **Cons:** More expensive, deprecated free tier

---

### Option 4: Container-Based Deployment

If you want maximum flexibility:

#### AWS Elastic Beanstalk
- Deploy PHP app easily
- Auto-scaling
- Can use RDS for MySQL

#### Google Cloud Run
- Containerize PHP app
- Serverless containers
- Pay per use

#### Azure App Service
- Native PHP support
- Easy deployment
- MySQL available

---

## Quick Comparison

| Platform | PHP Support | MySQL | Cost/Month | Difficulty | Best For |
|----------|-------------|-------|------------|------------|----------|
| **Railway** | ✅ | ✅ | $5-20 | Easy | Modern apps |
| **Render** | ✅ | ✅ | $7+ | Easy | Startups |
| **Shared Hosting** | ✅ | ✅ | $3-10 | Very Easy | Simple sites |
| **VPS (DigitalOcean)** | ✅ | ✅ | $5-10 | Medium | Developers |
| **Heroku** | ✅ | ✅ | $7+ | Easy | Established apps |
| **Vercel** | ❌ | ❌ | Free | Easy | Frontend only |

---

## Recommended Setup for Your App

### For Quick Deployment:
1. **Use Railway.app**
   - Connect GitHub repo
   - Add MySQL service
   - Set environment variables
   - Done!

### For Production (Higher Traffic):
1. **Use DigitalOcean Droplet**
   - $10/month droplet
   - Install PHP, MySQL, Nginx
   - Use Let's Encrypt for SSL
   - More control and scalability

### For Budget-Conscious:
1. **Use Shared Hosting**
   - cPanel-based hosting
   - Upload files via FTP/SFTP
   - Use phpMyAdmin for database
   - $3-5/month

---

## Deployment Steps (Railway Example)

1. **Push code to GitHub**
   ```bash
   git add .
   git commit -m "Ready for deployment"
   git push origin main
   ```

2. **Create Railway account**
   - Go to railway.app
   - Sign up with GitHub

3. **New Project**
   - Click "New Project"
   - Select "Deploy from GitHub repo"
   - Choose your repository

4. **Add MySQL Service**
   - Click "+ New"
   - Add MySQL database
   - Copy connection details

5. **Configure Environment Variables**
   - Go to project settings
   - Add variables:
     ```
     DB_HOST=<railway_mysql_host>
     DB_NAME=<railway_mysql_database>
     DB_USER=<railway_mysql_user>
     DB_PASS=<railway_mysql_password>
     SMTP_FROM_EMAIL=...
     SMTP_FROM_NAME=...
     APP_BASE_URL=https://your-app.railway.app
     ```

6. **Deploy**
   - Railway auto-detects PHP
   - Runs `composer install`
   - Deploys your app
   - Get public URL

7. **Custom Domain (Optional)**
   - Add custom domain in settings
   - Update DNS records
   - Update `APP_BASE_URL`

---

## Environment Variables Setup

Create these environment variables on your hosting platform:

### Database:
```env
DB_HOST=localhost
DB_NAME=splitter_db
DB_USER=root
DB_PASS=your_password
DB_CHARSET=utf8mb4
```

### Application:
```env
APP_BASE_URL=https://yourdomain.com
JWT_SECRET=your_jwt_secret_key
ENVIRONMENT=production
```

### Email (see EMAIL_CONFIGURATION_GUIDE.md):
```env
SMTP_ENABLED=true
SMTP_HOST=smtp.example.com
SMTP_PORT=587
SMTP_USERNAME=your-email@example.com
SMTP_PASSWORD=your-password
SMTP_FROM_EMAIL=noreply@yourdomain.com
SMTP_FROM_NAME=Splitter App
```

---

## Important Notes

1. **Database:** Ensure MySQL 8.0+ is available
2. **PHP Version:** Requires PHP 7.4+
3. **Composer:** Most platforms run `composer install` automatically
4. **File Permissions:** Ensure `backend/uploads` is writable
5. **.htaccess:** Some platforms need web server configuration
6. **Error Logging:** Configure PHP error logging for production

---

## Next Steps

1. Choose hosting platform
2. Set up database
3. Configure environment variables
4. Deploy application
5. Test all functionality
6. Configure email service
7. Set up custom domain (optional)
8. Monitor and maintain

