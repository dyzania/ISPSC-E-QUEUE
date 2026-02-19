# ISPSC E-Queue System - Installation & Deployment Guide

## Requirements

- **PHP**: 8.1+ (with PDO, MBString, OpenSSL)
- **Database**: MySQL 5.7+ or MariaDB 10.4+
- **Web Server**: Apache (with `mod_rewrite` enabled) or Nginx
- **Python**: 3.8+ (for Sentiment Analysis microservice)
- **SSL Certificate**: HTTPS is **REQUIRED** for browser notifications.

---

##  Installation Steps

### 1. Database Setup
1. Create a database named `equeue_system` (or your preferred name).
2. Import the schema:
   ```bash
   mysql -u root -p equeue_system < database/queue_schema.sql
   ```

### 2. Configuration
1. **PHP Config**: Edit `config/config.php` with your database credentials.
2. **Environment Variables**: Create a `.env` file in the root directory:
   ```env
   # AI & API
   OPENROUTER_API_KEY=your_key_here
   OPENROUTER_API_URL=https://openrouter.ai/api/v1/chat/completions
   AI_MODEL=stepfun/step-3.5-flash:free

   # Email (SMTP)
   MAIL_HOST=smtp.gmail.com
   MAIL_PORT=587
   MAIL_USERNAME=your-email@gmail.com
   MAIL_PASSWORD=your-app-password
   ```

### 3. Sentiment Analysis Setup (Python)
1. **Create Virtual Environment**:
   ```bash
   python -m venv .venv
   ```
2. **Activate & Install**:
   - **Windows (PowerShell)**: `.\.venv\Scripts\Activate.ps1`
   - **Linux/Mac**: `source .venv/bin/activate`
   ```bash
   pip install -r sentiment_analysis/requirements.txt
   ```
3. **Run the Server**:
   ```bash
   cd sentiment_analysis
   uvicorn app:app --host 127.0.0.1 --port 8000 --reload
   ```

---

## 🔧 Troubleshooting & Common Issues

### Database Connection
- **Error**: "Can't connect to database"
- **Solution**: Check if MySQL/MariaDB is running and verify credentials in `config/config.php`. Ensure the database name matches what you created.

### 404 Page Not Found
- **Solution**: 
  - **Apache**: Ensure `AllowOverride All` is set in your virtual host and `mod_rewrite` is active. Check the `.htaccess` in the `public/` folder.
  - **Nginx**: Ensure your config routes requests to `public/index.php`.

### Python Server (AI) Issues
- **Error**: Sentiment analysis not working.
- **Solution**: Ensure the Python server is running on port 8000 and `AI_URL` in your PHP config points to `http://127.0.0.1:8000/analyze`.

### Email Not Sending
- **Solution**: Use app-specific passwords for Gmail. Check if your ISP blocks port 587.

---

## ✅ Production Checklist
1. Enable HTTPS for secure sessions and notifications.
2. Change the default admin password (`admin@ispsc.edu.ph` / `password`).
3. Set `display_errors = 0` in `php.ini`.
4. Restrict access to the `.env` file.

 * DEPLOYMENT NOTES FOR ispsc-queue-system.com:
 * 1. Upload all files to the public_html or equivalent directory of your host.
 * 2. If the domain points directly to the root, your BASE_URL should be 'https://ispsc-queue-system.com/public'
 * 3. Update SMTP settings (lines 13-16) for email notifications to work on the server.
 * 4. Ensure your server's PHP version is 8.0+ for better performance.
 
