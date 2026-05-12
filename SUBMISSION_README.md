# API Assignment Deployment Notes

## Folders

- `api/`: JSON API endpoints for the EC2 API server.
- `external_site/`: external website that calls the API. It does not connect to MySQL.
- `docs/`: API documentation in Markdown and Word-readable `.doc` format.
- `nginx/`: sample Nginx configuration for the EC2 API server.

## EC2 API Server

1. Pull this repo on EC2.
2. Copy or merge `nginx/equipment_api_server.conf` into your Nginx site config.
3. Confirm `ec2-3-138-156-77.us-east-2.compute.amazonaws.com` is still your active EC2 public DNS.
4. Replace `/var/www/Advanced_Software_Engineering` with the actual repo path on EC2.
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

1. Upload `external_site/` to the external server.
2. Edit `external_site/config.php`.
3. Set `API_BASE_URL` to your EC2 API URL.

Example:

```php
const API_BASE_URL = 'http://1.2.3.4/api';
```

## Submission Zip

Create the zip using your required ID as the filename:

```powershell
Compress-Archive -Path api,external_site,docs,nginx -DestinationPath abc123.zip
```

Replace `abc123.zip` with your own required username or ID.
