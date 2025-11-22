# SplashBook - Multi-Tenant Appointment Booking SaaS

A complete, modern appointment booking platform for service-based businesses built with pure PHP and MySQL. Features include multi-tenant architecture, online booking, staff scheduling, calendar management, subscription billing, and REST API.

## Features

- ✅ **Multi-Tenant Architecture** - Single database, isolated tenant data
- ✅ **Online Booking** - Public booking pages for each business
- ✅ **Staff Management** - Multiple staff with individual schedules
- ✅ **Service Categories** - Organize services by category
- ✅ **Smart Scheduling** - Automatic availability detection with conflict prevention
- ✅ **Calendar Views** - Day, week, and month views
- ✅ **Client Management** - Track customer history and bookings
- ✅ **Subscription System** - Multiple plans with usage limits
- ✅ **Billing & Invoicing** - Automated billing with payment tracking
- ✅ **Email Notifications** - Booking confirmations and reminders (simulated)
- ✅ **REST API** - Availability and booking endpoints
- ✅ **File Uploads** - Business logos and staff photos
- ✅ **Timezone Support** - Per-tenant and per-user timezones
- ✅ **Security** - CSRF protection, password hashing, prepared statements

## Requirements

- PHP 7.0 or higher (compatible with PHP 8.x)
- MySQL 5.7+ or MariaDB 10.2+
- Apache with mod_rewrite (or Nginx with URL rewriting)
- PDO PHP Extension
- Fileinfo PHP Extension

## Installation

### 1. Clone or Download

```bash
git clone https://github.com/yourusername/splashbook.git
cd splashbook
```

### 2. Create Database

```bash
mysql -u root -p
```

```sql
CREATE DATABASE splashbook CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
exit;
```

### 3. Import Database Schema

```bash
mysql -u root -p splashbook < database.sql
```

### 4. Configure Environment

```bash
cp .env.example .env
```

Edit `.env` with your database credentials:

```
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=splashbook
DB_USERNAME=root
DB_PASSWORD=your_password
```

### 5. Set Permissions

```bash
chmod -R 755 storage/
chmod -R 755 public/assets/
```

### 6. Configure Web Server

#### Apache

The included `.htaccess` files should work out of the box. Ensure `mod_rewrite` is enabled:

```bash
sudo a2enmod rewrite
sudo systemctl restart apache2
```

Set your document root to the `public/` directory:

```apache
<VirtualHost *:80>
    ServerName splashbook.local
    DocumentRoot /path/to/splashbook/public

    <Directory /path/to/splashbook/public>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

#### Nginx

```nginx
server {
    listen 80;
    server_name splashbook.local;
    root /path/to/splashbook/public;

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
}
```

### 7. Access Application

Visit `http://splashbook.local` (or your configured domain)

## Default Credentials

### Platform Admin
- **Email:** admin@splashbook.example
- **Password:** password

### Demo Tenants

**Bella Beauty Salon** (slug: `bella-beauty`)
- **Admin Email:** admin@bellasalon.example
- **Password:** password
- **Booking URL:** http://splashbook.local/book/bella-beauty

**Healthy Smiles Dental** (slug: `healthy-smiles`)
- **Admin Email:** admin@healthysmiles.example
- **Password:** password
- **Booking URL:** http://splashbook.local/book/healthy-smiles

## Project Structure

```
splashbook/
├── app/
│   ├── controllers/      # Request handlers
│   ├── models/          # Database models
│   ├── views/           # HTML templates
│   ├── core/            # Framework classes
│   └── helpers/         # Utility functions
├── bootstrap/
│   └── autoload.php     # Autoloader and environment setup
├── config/              # Configuration files
├── public/              # Web root
│   ├── index.php        # Entry point
│   ├── assets/          # CSS, JS, images
│   └── .htaccess
├── storage/
│   └── uploads/         # File uploads
├── tests/               # Test scripts
├── database.sql         # Database schema
├── .env.example         # Environment template
└── README.md
```

## API Documentation

SplashBook provides a REST API for external integrations. All API requests require an API key.

### Authentication

Include your tenant's API key in the request header:

```
X-API-KEY: your_api_key_here
```

Find your API key in: Settings → Business Settings → API Key

### Endpoints

#### GET /api/availability

Get available time slots for booking.

**Query Parameters:**
- `service_id` (required): Service ID
- `staff_id` (optional): Specific staff member ID
- `date` (optional): Single date (Y-m-d format)
- `date_from` (optional): Start of date range
- `date_to` (optional): End of date range

**Example Request:**

```bash
curl -X GET "http://splashbook.local/api/availability?service_id=1&date=2025-12-01" \
  -H "X-API-KEY: bb_live_a1b2c3d4e5f6..."
```

**Example Response:**

```json
{
  "date": "2025-12-01",
  "service_id": 1,
  "availability": [
    {
      "staff_id": 1,
      "staff_name": "Sarah Johnson",
      "slots": [
        {
          "start_time": "09:00:00",
          "end_time": "10:00:00",
          "display_time": "9:00 AM"
        },
        {
          "start_time": "10:00:00",
          "end_time": "11:00:00",
          "display_time": "10:00 AM"
        }
      ]
    }
  ]
}
```

#### POST /api/booking

Create a new booking.

**Headers:**
- `X-API-KEY`: Your API key
- `Content-Type`: application/json

**Request Body:**

```json
{
  "service_id": 1,
  "staff_id": 1,
  "date": "2025-12-01",
  "start_time": "09:00:00",
  "client_name": "John Doe",
  "client_email": "john@example.com",
  "client_phone": "+1-555-0100",
  "notes": "First time client"
}
```

**Example Request:**

```bash
curl -X POST "http://splashbook.local/api/booking" \
  -H "X-API-KEY: bb_live_a1b2c3d4e5f6..." \
  -H "Content-Type: application/json" \
  -d '{
    "service_id": 1,
    "staff_id": 1,
    "date": "2025-12-01",
    "start_time": "09:00:00",
    "client_name": "John Doe",
    "client_email": "john@example.com"
  }'
```

**Example Response:**

```json
{
  "success": true,
  "booking_id": 123,
  "reference_number": "BOOK-A1B2C3",
  "status": "pending"
}
```

## Usage Guide

### Creating a New Business Account

1. Navigate to `/register`
2. Fill in business details
3. Create admin account
4. You'll be automatically logged in with a 14-day trial

### Managing Staff

1. Go to **Staff** → **Add Staff**
2. Enter staff details
3. Set working hours for each day
4. Assign services they can provide

### Setting Up Services

1. Go to **Services**
2. Create service categories (optional)
3. Add services with:
   - Name, description
   - Duration (in minutes)
   - Price
   - Color (for calendar)

### Public Booking Page

Share your booking URL: `http://yourdomain.com/book/your-business-slug`

Clients can:
1. Select a service
2. Choose date and time
3. Enter contact information
4. Receive confirmation via email

### Managing Bookings

- View all bookings in **Bookings**
- Filter by status, date, staff, service
- Update booking status (confirmed, completed, canceled, no-show)
- Add internal notes
- Calendar view shows all scheduled appointments

## Subscription Plans

Three built-in plans (customizable in database):

| Plan | Price/Month | Staff | Services | Bookings/Month |
|------|-------------|-------|----------|----------------|
| Starter | $29 | 3 | 10 | 50 |
| Professional | $79 | 10 | 50 | 300 |
| Enterprise | $199 | 50 | 200 | 2000 |

## Security Features

- **CSRF Protection**: All forms include CSRF tokens
- **Password Hashing**: bcrypt hashing for all passwords
- **SQL Injection Prevention**: PDO prepared statements only
- **XSS Prevention**: Output escaping in views
- **File Upload Validation**: Type and size restrictions
- **Session Security**: HTTP-only cookies, session regeneration
- **API Authentication**: API key validation

## Testing

Run basic functional tests:

```bash
php tests/run_tests.php
```

## Development

### Adding New Features

1. Create model in `app/models/`
2. Create controller in `app/controllers/`
3. Add routes in `config/routes.php`
4. Create views in `app/views/`

### Database Migrations

Manual migrations - update `database.sql` with schema changes.

### Email Configuration

Currently emails are logged to the `email_logs` table. To use real SMTP:

1. Install PHPMailer or similar
2. Update `EmailHelper` class
3. Configure SMTP settings in `.env`

## Troubleshooting

### 404 Errors on All Pages

- Check that `mod_rewrite` is enabled (Apache)
- Verify `.htaccess` files are present
- Check DocumentRoot points to `/public`

### Database Connection Errors

- Verify credentials in `.env`
- Check MySQL service is running
- Ensure database exists

### File Upload Errors

- Check permissions on `/storage/uploads`
- Verify `upload_max_filesize` in `php.ini`

### Blank Pages

- Enable error reporting in `.env`: `APP_ENV=development`
- Check PHP error logs
- Verify all required extensions are installed

## Performance Optimization

- Enable OPcache in production
- Add database indexes as needed
- Implement caching layer (Redis/Memcached)
- Use CDN for static assets
- Enable GZIP compression

## Deployment Checklist

- [ ] Set `APP_ENV=production` in `.env`
- [ ] Change default passwords
- [ ] Set up SSL certificate (HTTPS)
- [ ] Configure email SMTP
- [ ] Set up automated backups
- [ ] Enable error logging
- [ ] Restrict file permissions
- [ ] Configure firewall rules
- [ ] Set up monitoring

## License

This project is for educational and commercial use.

## Support

For issues and questions:
- Open an issue on GitHub
- Check documentation
- Review code comments

## Credits

Built with ❤️ using pure PHP and MySQL. No frameworks, just clean, beginner-friendly code.
