# API Assignment Deployment Notes

## Folders

- `api/`: JSON API endpoints for the EC2 API server.
- `external_site/`: external website that calls the API. It does not connect to MySQL.
- `docs/`: API documentation in Markdown and Word-readable `.doc` format.
- `nginx/`: sample Nginx configurations for the EC2 API server and external site server.

## EC2 API Server

Public DNS: `ec2-3-138-156-77.us-east-2.compute.amazonaws.com`

1. Upload the project contents to `/var/www/html` on the API server.
2. Copy or merge `nginx/equipment_api_server.conf` into your Nginx site config.
3. Confirm `ec2-3-138-156-77.us-east-2.compute.amazonaws.com` is still your active EC2 public DNS.
4. Confirm the API Nginx config uses `root /var/www/html/web` and maps `/api/*.php` to `/var/www/html/api/*.php`.
5. Replace `php8.3-fpm.sock` if your server uses a different PHP-FPM version.
6. Run:

```bash
sudo nginx -t
sudo systemctl reload nginx
```

API base URL after deployment:

```text
http://ec2-3-138-156-77.us-east-2.compute.amazonaws.com/api
```

## External Site

Public DNS: `ec2-18-119-235-98.us-east-2.compute.amazonaws.com`

1. Upload the `external_site/` folder to `/var/www/html/external_site` on the external server.
2. Copy or merge `nginx/external_site_server.conf` into your Nginx site config.
3. Confirm the Nginx root is `/var/www/html` and that `/external_site` redirects to `/external_site/index.php`.
4. Replace `php8.3-fpm.sock` if your server uses a different PHP-FPM version.
5. Run:

```bash
sudo nginx -t
sudo systemctl reload nginx
```

6. Confirm `external_site/config.php` points to the EC2 API server:

   ```php
   const API_BASE_URL = 'http://ec2-3-138-156-77.us-east-2.compute.amazonaws.com/api';
   const EXTERNAL_SITE_ORIGIN = 'https://ec2-18-119-235-98.us-east-2.compute.amazonaws.com';
   const EXTERNAL_SITE_BASE_PATH = '/external_site';
   ```

External site URL after deployment:

```text
https://ec2-18-119-235-98.us-east-2.compute.amazonaws.com/external_site
```

## Submission Zip

Create the zip using your required ID as the filename:

```powershell
Compress-Archive -Path api,external_site,docs,nginx -DestinationPath abc123.zip
```

Replace `abc123.zip` with your own required username or ID.
