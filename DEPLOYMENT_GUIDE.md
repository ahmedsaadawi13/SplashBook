# SplashBook - Deployment Guide

## Quick Start

### 1. Server Requirements
- PHP 7.0+ (tested up to PHP 8.x)
- MySQL 5.7+ or MariaDB 10.2+
- Apache with mod_rewrite OR Nginx
- Minimum 512MB RAM
- 100MB disk space

### 2. Installation Steps

```bash
# Clone repository
git clone <repository-url>
cd SplashBook

# Copy environment file
cp .env.example .env

# Edit .env with your database credentials
nano .env

# Create database
mysql -u root -p
CREATE DATABASE splashbook CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
exit;

# Import schema
mysql -u root -p splashbook < database.sql

# Set permissions
chmod -R 755 storage/
chmod -R 755 public/assets/

# For Apache (with .htaccess support)
sudo a2enmod rewrite
sudo systemctl restart apache2
```

### 3. Web Server Configuration

#### Apache VirtualHost
```apache
<VirtualHost *:80>
    ServerName yourdomain.com
    DocumentRoot /var/www/splashbook/public

    <Directory /var/www/splashbook/public>
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/splashbook-error.log
    CustomLog ${APACHE_LOG_DIR}/splashbook-access.log combined
</VirtualHost>
```

#### Nginx Configuration
```nginx
server {
    listen 80;
    server_name yourdomain.com;
    root /var/www/splashbook/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php7.4-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

### 4. SSL Configuration (Recommended)

```bash
# Install Certbot
sudo apt install certbot python3-certbot-apache

# Get certificate
sudo certbot --apache -d yourdomain.com

# Or for Nginx
sudo certbot --nginx -d yourdomain.com
```

### 5. Production Environment Settings

Edit `.env`:
```
APP_ENV=production
APP_URL=https://yourdomain.com
REQUIRE_HTTPS=true
```

### 6. Cron Jobs (Optional)

For automated tasks like sending reminders:
```cron
# Run every hour
0 * * * * cd /var/www/splashbook && php cron/send_reminders.php
```

## Security Checklist

- [ ] Change all default passwords
- [ ] Set APP_ENV=production
- [ ] Enable HTTPS (SSL certificate)
- [ ] Restrict database user permissions
- [ ] Set proper file permissions (755 for directories, 644 for files)
- [ ] Disable directory listing
- [ ] Keep PHP and MySQL updated
- [ ] Regular backups of database
- [ ] Monitor error logs
- [ ] Configure firewall (UFW/iptables)

## Performance Optimization

### 1. Enable OPcache

Edit `php.ini`:
```ini
opcache.enable=1
opcache.memory_consumption=128
opcache.max_accelerated_files=10000
opcache.revalidate_freq=2
```

### 2. Database Indexing

The schema includes all necessary indexes. Monitor slow queries:
```sql
SET GLOBAL slow_query_log = 'ON';
SET GLOBAL long_query_time = 1;
```

### 3. Caching

For high-traffic sites, consider:
- Redis/Memcached for session storage
- CDN for static assets
- Database query caching

## Backup Strategy

### Database Backup
```bash
# Daily backup script
mysqldump -u root -p splashbook > backup_$(date +%Y%m%d).sql

# Compress
gzip backup_$(date +%Y%m%d).sql
```

### File Backup
```bash
# Backup uploads
tar -czf uploads_backup_$(date +%Y%m%d).tar.gz storage/uploads/
```

## Monitoring

### Error Logging

Check logs regularly:
```bash
tail -f /var/log/apache2/splashbook-error.log
tail -f storage/logs/app.log  # If you implement file logging
```

### Health Check Endpoint

Create `/health.php`:
```php
<?php
require_once __DIR__ . '/../bootstrap/autoload.php';
try {
    $db = Database::getInstance();
    echo json_encode(['status' => 'healthy', 'timestamp' => time()]);
} catch (Exception $e) {
    http_response_code(503);
    echo json_encode(['status' => 'unhealthy', 'error' => 'Database connection failed']);
}
```

## Troubleshooting

### Issue: 500 Internal Server Error
- Check PHP error logs
- Verify .htaccess files exist
- Ensure mod_rewrite is enabled
- Check file permissions

### Issue: Database Connection Failed
- Verify credentials in .env
- Check MySQL service: `sudo systemctl status mysql`
- Test connection: `mysql -u username -p`

### Issue: File Uploads Failing
- Check storage/ permissions
- Verify upload_max_filesize in php.ini
- Check disk space: `df -h`

## Scaling Considerations

### Horizontal Scaling
- Use load balancer (HAProxy/Nginx)
- Session storage in Redis/Memcached
- Shared file storage (NFS/S3)
- Database replication (master-slave)

### Vertical Scaling
- Increase PHP memory_limit
- Add more database connections
- Optimize MySQL configuration
- Enable PHP OPcache

## Migration from Development

1. Export database from dev:
   ```bash
   mysqldump -u root -p splashbook_dev > migration.sql
   ```

2. Import to production:
   ```bash
   mysql -u root -p splashbook_prod < migration.sql
   ```

3. Update .env with production settings

4. Clear any cached data

5. Test thoroughly before going live

## Support & Maintenance

- Regular security updates
- Monitor error logs daily
- Weekly database backups
- Monthly performance reviews
- Update documentation as needed
