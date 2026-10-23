# Ice Cream Shop Backend

A RESTful backend for an ice cream shop, built with **Laravel 13** and **MySQL**. It manages inventory items (grouped by flavor/category) and customer records, with token authentication and role-based access control for three roles: Owner, Manager and Cashier.

**Author:** Suranga Prabash  
**Repository:** https://github.com/surangaprabash/ice-cream-shop-laravel

## Features

- Token authentication with Laravel Sanctum (login, logout, current user)
- Role-based access control enforced on the backend (Owner, Manager, Cashier)
- Inventory item CRUD with search by name and pagination
- Customer CRUD with pagination
- Read-only category (flavor) listing
- Input validation with clear JSON error responses
- Login rate limiting (5 attempts per minute) and token expiry
- Migrations, seeders and automated feature tests
- Postman collection and environment included

## Tech stack

- PHP 8.3 or higher
- Laravel 13
- MySQL 8
- Laravel Sanctum
- PHPUnit (tests run on in-memory SQLite)

## Getting started

### Requirements

- PHP 8.3+ with the `pdo_mysql` and `pdo_sqlite` extensions
- Composer
- MySQL

### 1. Clone the repository

```bash
git clone https://github.com/surangaprabash/ice-cream-shop-laravel.git
cd ice-cream-shop-laravel
```

### 2. Install dependencies

```bash
composer install
```

### 3. Configure the environment

```bash
cp .env.example .env
php artisan key:generate
```

On Windows PowerShell, use `copy .env.example .env` instead of `cp`.

### 4. Create the database

Run this in MySQL (console, phpMyAdmin, Laragon or XAMPP):

```sql
CREATE DATABASE icecream_shop CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Open `.env` and set your MySQL credentials:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=icecream_shop
DB_USERNAME=root
DB_PASSWORD=
```

### 5. Run migrations and seeders

```bash
php artisan migrate --seed
```

### 6. Start the server

```bash
php artisan serve
```

The API is now available at `http://127.0.0.1:8000/api`.

## Seed data

`php artisan migrate --seed` creates:

**Users** (password for all: `password123`)

| Role | Email |
|---|---|
| Owner | owner@icecream.test |
| Manager | manager@icecream.test |
| Cashier | cashier@icecream.test |

**Categories (6):** Chocolate, Vanilla, Strawberry, Fruit and Nuts, Rocky Road, Cookies and Cream  
**Items:** 18 (3 per category)  
**Customers:** 5

To reset everything to the seed data:

```bash
php artisan migrate:fresh --seed
```

> These accounts are for development only. Change the passwords for any real deployment.

## Role permissions

| Action | Owner | Manager | Cashier |
|---|:---:|:---:|:---:|
| View items | ✅ | ✅ | ✅ |
| Create items | ✅ | ✅ | ❌ |
| Update items | ✅ | ✅ | ✅ |
| Delete items | ✅ | ❌ | ❌ |
| View customers | ✅ | ✅ | ✅ |
| Create customers | ✅ | ✅ | ✅ |
| Update customers | ✅ | ✅ | ✅ |
| Delete customers | ✅ | ❌ | ❌ |

Permissions are enforced by the `role` middleware on each route (see `routes/api.php`). A forbidden action returns `403`.

## API reference

Send `Accept: application/json` on every request. Protected endpoints also need the header `Authorization: Bearer <token>`.

| Method | Endpoint | Description | Allowed roles |
|---|---|---|---|
| POST | `/api/auth/login` | Log in and get a token | Public |
| POST | `/api/auth/logout` | Revoke the current token | Any logged-in user |
| GET | `/api/auth/me` | Current user | Any logged-in user |
| GET | `/api/categories` | List categories | Any logged-in user |
| GET | `/api/items` | List items (`?search=`, `?page=`, `?per_page=`) | All |
| GET | `/api/items/{id}` | Get an item | All |
| POST | `/api/items` | Create an item | Owner, Manager |
| PUT/PATCH | `/api/items/{id}` | Update an item | All |
| DELETE | `/api/items/{id}` | Delete an item | Owner |
| GET | `/api/customers` | List customers (`?page=`, `?per_page=`) | All |
| GET | `/api/customers/{id}` | Get a customer | All |
| POST | `/api/customers` | Create a customer | All |
| PUT/PATCH | `/api/customers/{id}` | Update a customer | All |
| DELETE | `/api/customers/{id}` | Delete a customer | Owner |

Lists are paginated, 10 per page by default (`per_page` up to 50).

### Example: login

```http
POST /api/auth/login
Content-Type: application/json

{ "email": "owner@icecream.test", "password": "password123" }
```

Response `200`:

```json
{
  "message": "Login successful.",
  "token_type": "Bearer",
  "access_token": "1|xxxxxxxx",
  "user": { "id": 1, "name": "Shop Owner", "email": "owner@icecream.test", "role": "owner" }
}
```

### Example: create an item

```http
POST /api/items
Authorization: Bearer <token>
Content-Type: application/json

{
  "category_id": 1,
  "name": "Choco Chip Cone",
  "description": "Chocolate chip single scoop",
  "price": 360.00,
  "quantity": 40,
  "unit": "cone"
}
```

### Example: create a customer

```http
POST /api/customers
Authorization: Bearer <token>
Content-Type: application/json

{
  "name": "Test Customer",
  "email": "test.customer@example.com",
  "phone": "0771112222",
  "address": "1 Test Road, Colombo"
}
```

### Validation rules

**Item**

| Field | Rule |
|---|---|
| `category_id` | required, must exist |
| `name` | required, max 255, unique within the category |
| `description` | optional, max 1000 |
| `price` | required, 0 to 999999.99 |
| `quantity` | required, whole number, 0 or more |
| `unit` | optional, max 30 (default `tub`) |

**Customer**

| Field | Rule |
|---|---|
| `name` | required, max 255 |
| `email` | optional, valid email, unique |
| `phone` | optional, digits with `+ - ( )` and spaces, 7 to 30 characters |
| `address` | optional, max 500 |

On update (PUT or PATCH) every field is optional, so you can send only the fields that changed.

### Response codes

| Code | Meaning |
|---|---|
| 200 | Success |
| 201 | Created |
| 401 | Missing, invalid or expired token, or wrong credentials |
| 403 | Your role is not allowed to perform this action |
| 404 | Record not found |
| 422 | Validation failed |
| 429 | Too many login attempts |

## Testing the API with Postman

The collection and environment are in `docs/postman/`:

- `ice-cream-shop.postman_collection.json`
- `ice-cream-shop.local.postman_environment.json`

### Import

1. Open Postman and click **Import**.
2. Drag in both JSON files (or choose them with **Files**) and click **Import**.
3. In the top-right environment dropdown, select **Ice Cream Shop - Local**.
4. Check that the `baseurl` variable is `http://127.0.0.1:8000` (edit it if your server uses another address).

### Use

1. Make sure the server is running (`php artisan serve`).
2. In the **Auth** folder, run **Login - Owner**, **Login - Manager** or **Login - Cashier**. The token is saved automatically to the `token` environment variable.
3. Run any request in the **Categories**, **Items** or **Customers** folders. They use the saved token automatically.
4. To test another role's permissions, run that role's login request first, then repeat the request. For example, log in as Cashier and run **Create item** to see the `403` response.

**Note:** when sending a body with POST, PUT or PATCH, set it to **raw → JSON** in the Body tab.

## Running the automated tests

```bash
php artisan test
```

The tests are HTTP feature tests covering:

- Login, logout, invalid credentials and login rate limiting
- The full role permission matrix for items and customers
- CRUD operations, search and pagination
- Validation rules and 404 handling

They run on an in-memory SQLite database, so your MySQL data is never touched.

To run a single test file:

```bash
php artisan test tests/Feature/ItemTest.php
```

## Database design

The ER diagram is in `docs/er-diagram/`.

- `categories` 1 → N `items`
- `users` 1 → N `personal_access_tokens` (Sanctum)
- `customers` is a standalone table
- A user's role (`owner`, `manager`, `cashier`) is stored in `users.role`

## Project structure

```
app/
  Enums/Role.php                      Role enum (owner, manager, cashier)
  Http/Controllers/Api/               Auth, Item, Customer and Category controllers
  Http/Middleware/EnsureUserHasRole   Role-based access middleware
  Http/Requests/                      Validation (form requests)
  Http/Resources/                     JSON response formatting
  Models/                             User, Category, Item, Customer
database/
  migrations/  seeders/  factories/
docs/
  postman/  er-diagram/
routes/api.php                        All API routes and role rules
tests/Feature/                        Feature tests
```

## Notes

- Authentication uses Laravel Sanctum bearer tokens. Tokens expire after 480 minutes (`SANCTUM_EXPIRATION` in `.env`).
- A category that still has items cannot be deleted (foreign key RESTRICT).

## Author

Developed by **Suranga Prabash**  
GitHub: [surangaprabash](https://github.com/surangaprabash)