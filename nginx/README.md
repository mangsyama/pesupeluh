# Nginx configuration for Pesu Peluh

This project stores Nginx configuration under `nginx/` so you can keep all app config inside the repository.

## How to use

1. Add the project config directory to your main Nginx config:

```nginx
http {
    include /var/www/pesupeluh/nginx/*.conf;
    include /etc/nginx/conf.d/*.conf;
    include /etc/nginx/sites-enabled/*;
}
```

2. Make sure `fastcgi_pass` in `nginx/pesupeluh.conf` points to your PHP-FPM socket or TCP backend.

3. If you use `APP_URL`, set it to the same host and port handled by Nginx, for example:

```env
APP_URL=http://pesupeluh.local
```

4. Add a local host entry so the domain resolves on your machine:

```text
127.0.0.1 pesupeluh.local www.pesupeluh.local
```

5. Test and reload Nginx:

```bash
sudo nginx -t && sudo systemctl reload nginx
```

## Notes

- The site root is `/var/www/pesupeluh/public`.
- The `.env` file is already created in the project root and ignored by Git.
- If you want to use a custom hostname, replace `server_name` and `APP_URL` accordingly.
