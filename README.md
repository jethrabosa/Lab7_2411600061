# Inventory Tracking and Automated Replenishment

Laravel 12 inventory app for Laboratory Exercise 7. It includes the Laboratory Exercise 6 inventory workflows plus supplier management, usage-based reorder recommendations, purchase orders, an admin approval workflow, delivery receipts, supplier performance metrics, notifications, and scheduled replenishment. Monetary amounts are displayed in Philippine pesos (PHP); stored numeric prices are not converted.

## Run locally

Requirements: PHP 8.2+, Composer, Node.js/npm, and XAMPP MariaDB.

Start Apache and MySQL in the XAMPP Control Panel. The default configuration uses MariaDB at `127.0.0.1:3307` and the `lab7_inventory` database; create that database in phpMyAdmin before migrating. If MariaDB uses a different port or credentials, update the `DB_*` values in `.env`.

```powershell
composer install
npm install
if (!(Test-Path .env)) { Copy-Item .env.example .env }
php artisan key:generate
php artisan migrate --seed
npm run build
php artisan serve
```

Open `http://127.0.0.1:8000`; the dashboard opens directly without a login screen.
The demo suppliers and products are seeded into MariaDB and can be viewed in phpMyAdmin under `lab7_inventory`.
In a second terminal, run `php artisan queue:work` to send low-stock and purchase-order emails. Configure `MAIL_*` in `.env` to deliver email outside the local testing mailer.

## Main pages

- `/dashboard` — inventory totals, low-stock alerts, recent transactions, and polling updates.
- `/products` — product list and details, inventory valuation, and date-filtered transaction balances.
- `/transactions` — searchable stock movement history and stock adjustment form.
- `/reports` — movement, low-stock, and valuation reports with CSV export.
- `/notifications` — low-stock alerts with mark-as-read controls.
- `/suppliers` — admin-only supplier CRUD and delivery/accuracy performance metrics.
- `/purchase-orders` — admin-only manual and automated replenishment, approval, ordering, and delivery tracking.

Assign an active supplier, optional supplier price, and optional per-product lead-time override to a product. Replenishment averages stock-out transactions over the previous 90 days, calculates the reorder point from lead time (bounded by the configured reorder level), and suggests enough stock to cover lead time plus 30 days of usage. Generated purchase orders wait for admin approval; approving and marking an order as placed are separate steps. Record each delivery on the order page to update stock through the existing inventory transaction observer. Existing outstanding purchase-order quantities are accounted for so repeated checks do not create duplicate orders.

The `inventory:generate-purchase-orders` command can be run manually. Laravel's scheduler runs it daily at 06:00; configure a once-per-minute `php artisan schedule:run` task in the host scheduler (for example, Windows Task Scheduler). Supplier deletion is a soft delete, so historical orders remain available.
Admins can also create a purchase order manually from the purchase-order index. Manual orders must contain products assigned to the selected supplier and are submitted for approval.

Only users with `is_admin = true` can manage suppliers and purchase orders. The demo seeder creates `test@example.com` with password `password` as an admin. Stock changes continue to be recorded through transactions. This no-login inventory mode allows anyone who can reach the app to change stock, so use it only in a local/classroom environment, not on a public server.

## Tests

```powershell
php artisan test
```

---

<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework. You can also check out [Laravel Learn](https://laravel.com/learn), where you will be guided through building a modern Laravel application.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com)**
- **[Tighten Co.](https://tighten.co)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Redberry](https://redberry.international/laravel-development)**
- **[Active Logic](https://activelogic.com)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
