# Mikale Art & Craft E-Commerce Store

A full-stack e-commerce web application built for a handmade arts and crafts store. Customers can browse and personalize products, manage a shopping cart, complete payments through Stripe Checkout, and track their orders. An authenticated administration panel provides tools for managing products, inventory information, and customer orders.

## Features

### Customer
- Browse an online product catalog
- Search and filter products by category and size
- Sort products by price
- Add optional product personalization
- Add, update, and remove products from a session-based shopping cart
- Complete payments using Stripe Checkout
- Submit shipping information after payment confirmation
- Track orders using a unique tracking code

### Administration
- Secure administrator authentication
- Create and edit products
- Activate or deactivate products without deleting historical data
- Upload and replace product images
- Review customer orders
- Update order statuses
- Send order-related email notifications

## Technologies

- **Backend:** PHP 8+
- **Database:** MySQL / MySQLi
- **Frontend:** HTML, CSS, JavaScript
- **Payments:** Stripe Checkout / Stripe PHP SDK
- **Email:** PHPMailer
- **Dependency Management:** Composer
- **Web Server:** Apache with `.htaccess` routing

## Security

The application includes several server-side security measures:

- Prepared statements for database queries
- Password hashing and verification for administrator authentication
- CSRF protection for state-changing requests
- Secure PHP session configuration and session-ID regeneration after login
- Server-side validation and HTML output escaping
- Image upload validation using file type, MIME type, image content, and size checks
- Server-side product price verification during checkout
- Database transactions and duplicate-resistant Stripe order creation
- Cryptographically random order tracking codes
- Sensitive credentials stored outside version control
- Generic production error responses with server-side error logging

## Local Setup

### Requirements

- PHP 8+
- MySQL
- Apache with `mod_rewrite`
- Composer
- Stripe test account credentials
- SMTP credentials if email notifications are required

### Installation

1. Clone the repository.
2. Create a MySQL database and import `database/schema.sql`.
3. Copy `private/.env.example` to `private/.env`.
4. Add your own database, Stripe **test**, and SMTP credentials to `private/.env`.
5. Install the PHP dependencies:

   ```bash
   cd public_html
   composer install
   ```

6. Configure Apache to use `public_html` as the document root and enable `mod_rewrite`.
7. Open the application through your configured local web server.

> `private/.env` contains environment-specific credentials and must never be committed to version control.

## Project Structure

```text
database/                 Database schema and migrations
private/                  Environment configuration template
public_html/
├── admin/                Admin application logic and pages
├── admin-portal/         Public admin route entry points
├── assets/               Styles and static assets
├── config/               Database and Stripe configuration
├── includes/             Shared authentication, security, mail, and UI code
└── public/               Customer-facing application pages
```

Composer dependencies are restored with `composer install` and are intentionally excluded from version control.

## Future Improvements

- Stripe webhook-based payment confirmation
- Inventory quantities with atomic stock reservation
- Automated unit and integration tests
- Login and order-tracking rate limiting
- Dedicated database migration tooling

## Purpose

This project demonstrates full-stack PHP development, relational database design, third-party payment integration, authentication, session management, application security, debugging, and deployment-oriented configuration.
