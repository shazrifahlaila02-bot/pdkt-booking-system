# Equipment Booking System — Pejabat Daerah Kuala Terengganu

English PHP + MySQL application with JavaScript for date selection. Staff request laptops, phones and cameras. An administrator approves or rejects requests and records collection and return. Approved date overlaps are blocked, including during concurrent approvals.

## Set up in Laragon (Windows)

1. Open Laragon and click **Start All**. Select PHP **8.2 or newer**. Ensure the `pdo_mysql` extension is enabled (Laragon's PHP Extensions menu).
2. Put this project folder in `C:\laragon\www\pdkt-booking-system` (or the `www` folder under your own Laragon installation). `index.php` must be directly inside that folder.
3. Open phpMyAdmin as in your screenshot. Click the **booking** database on the left, then **Import** at the top. Choose `database/schema.sql` from this project and click **Import/Go**. It creates three tables: `users`, `equipment` and `bookings`. You do not need to create them manually. Existing data is not deleted.
4. Copy `config.example.php`, rename the copy to **config.local.php**, and open it in a text editor. Keep host `127.0.0.1`, port `3308` and database `booking` to match your screenshot. Enter your actual MySQL username and password. The example uses username `root` and an empty password, common in local Laragon installations; confirm your settings. Never post your password in chat or upload this file to GitHub.
5. Open `http://localhost/pdkt-booking-system/`. If Laragon's web server uses port **100** as in your screenshot, use **http://localhost:100/pdkt-booking-system/** instead. The website port and the MySQL port are different.
6. Create your administrator account using the first-time setup form. Sign in and register each actual laptop, phone and camera using a unique asset number.
7. Sign out and register a staff account to test a request. Sign back in as administrator to approve it, record collection, and record return.

The initial administrator can be created only once. No default passwords or example assets are shipped. Back up your MySQL `booking` database using phpMyAdmin Export.

### Troubleshooting

- **Database not ready:** start MySQL, check port `3308` and credentials in `config.local.php`, then confirm all three tables were imported into `booking`.
- **Could not find driver:** enable PHP `pdo_mysql`, then restart Laragon.
- **404:** check the project folder location and Laragon web server port.
- **Equipment already booked:** choose another asset or dates. Both collection and return days count as reserved.

## Optional cloud development with Docker

Docker is not required for Laragon. For this cloud workspace only, create an ignored `config.local.php` returning host `db`, port `3306`, database `booking`, username `root`, password empty. Then:

```sh
DOCKER_CONFIG=/tmp/pdkt-docker docker compose up -d --build
docker compose exec -T db mariadb -uroot booking < database/schema.sql
python3 tests/php_workflow.py
```

The cloud development service is bound to loopback port 8080. Check it internally with HTTP requests. Processes do not survive environment publication; restart using these commands. Data remains in the `pdkt-db` Docker volume. The SQL import is repeatable and does not reset data. Tests create unique temporary test users/assets and delete only those records afterward.

## Scope

This replaces the earlier Python/SQLite prototype; Python is only used by the optional automated HTTP test, not the application. No existing SQLite data is migrated or deleted. The application stores accounts and bookings in MySQL.

This is a local development application, not a public production deployment. Staff self-registration does not verify employment. Before official use, add organisational identity checks, login rate limiting, HTTPS, audit logs and a production deployment/backup plan. Sessions expire after 30 minutes of inactivity. Passwords are hashed; forms use CSRF tokens; SQL queries are parameterised. Create the first administrator privately before sharing access.
