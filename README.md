# Project API

A REST API built from scratch in PHP without using a PHP web framework.

The project explores the fundamentals of building a REST API manually, including HTTP request handling, routing, JWT authentication, MySQL database integration, Redis-based rate limiting, HTTP caching, pagination and standardized JSON responses.

## Why This Project?

The project was built from the ground up to explore the components that are often abstracted away by frameworks when building a REST API.

Instead of simply using a framework-provided authentication system, router, middleware, cache layer, or rate limiter, this project implements these concepts directly in PHP.

The goal is not to replace frameworks, but to understand what they provide.

## What This Project Demonstrates

The main purpose of this project is to understand how a REST API works internally by implementing its core components without relying on a PHP web framework.

- HTTP methods and status codes
- User registration and login
- JWT-based authentication
- Protected user profile endpoints
- MySQL database integration
- Password hashing
- Redis-based rate limiting
- Request payload size limits
- HTTP caching with:
  - `ETag`
  - `Last-Modified`
  - `Cache-Control`
  - `Vary`
- JSON response formatting
- Response compression
- Pagination for user listing
- Simple HTML frontend
- Apache URL rewriting for cleaner API endpoints

## Tech Stack

- **PHP**
- **MySQL**
- **Redis**
- **Apache**
- **HTML**
- **CSS**
- **JavaScript**
- **JWT**
- **Firebase PHP-JWT**
- **Composer**

## Project Structure

```text
project-api/
│
├── .htaccess
├── index.html
├── auth.html
├── profile.html
│
├── auth.php
├── profile.php
│
├── config.php
├── jwt.php
├── rate_limit.php
├── http_cache.php
├── response.php
├── db.sql
│
├── keygen.php
└── composer.json
```

### Main Files

The application is intentionally lightweight.

There is no Laravel, Symfony, Slim, or other PHP web framework handling the API.

Instead, the project is composed of small PHP modules responsible for individual concerns.

| File | Purpose |
|------|---------|
| `.htaccess` | Apache URL rewriting and API routing |
| `index.html` | Main frontend page |
| `auth.html` | Authentication interface |
| `profile.html` | User profile interface |
| `auth.php` | Registration and login API |
| `profile.php` | User profile and user-management API |
| `config.php` | Database, JWT, and Redis configuration |
| `jwt.php` | JWT generation and verification |
| `rate_limit.php` | Redis-based rate limiting |
| `http_cache.php` | HTTP caching functionality |
| `response.php` | Standardized API responses |
| `db.sql` | MySQL database schema |
| `keygen.php` | JWT secret-key generation utility |

## Project Architecture

```text
                 ┌─────────────────┐
                 │    Frontend     │
                 │ HTML / JS / CSS │
                 └────────┬────────┘
                          │
                          ▼
                 ┌─────────────────┐
                 │     Apache      │
                 │   .htaccess     │
                 └────────┬────────┘
                          │
                          ▼
                 ┌─────────────────┐
                 │    PHP API      │
                 └────────┬────────┘
                          │
          ┌───────────────┼───────────────┐
          ▼               ▼               ▼
      JWT Auth       Rate Limiting      Caching
          │               │               │
          ▼               ▼               ▼
       Firebase         Redis          HTTP Headers
       PHP-JWT
                          │
                          ▼
                       MySQL
```

## API Routing

The API uses Apache's `.htaccess` file to route clean API URLs
to the corresponding PHP files.

| API Route | PHP File |
|-----------|----------|
| `POST /auth/signup` | `auth.php` |
| `POST /auth/login` | `auth.php` |
| `GET /profile/me` | `profile.php` |
| `GET /profile/users` | `profile.php` |
| `PUT /profile/me` | `profile.php` |
| `DELETE /profile/me` | `profile.php` |

The routing is implemented using Apache rewrite rules rather than a
PHP web framework.

This allows the API to expose clean REST-style endpoints while
keeping the underlying PHP files separate from the public API URLs.

## API Endpoints

### Authentication

| Method | Endpoint | Description | Authentication |
|--------|----------|-------------|----------------|
| `POST` | `/auth/signup` | Create a new user | No |
| `POST` | `/auth/login` | Authenticate a user and receive a JWT | No |

### User

| Method | Endpoint | Description | Authentication |
|--------|----------|-------------|----------------|
| `GET` | `/profile/me` | Get the authenticated user's profile | JWT |
| `GET` | `/profile/users` | Get a paginated list of users | JWT |
| `GET` | `/profile/users?email=...` | Find a user by email | JWT |
| `PUT` | `/profile/me` | Update the authenticated user's profile | JWT |
| `DELETE` | `/profile/me` | Delete the authenticated user's account | JWT |

> The exact URL depends on the Apache configuration and directory in which the project is hosted.

## Authentication

The API uses **JSON Web Tokens (JWT)** for authentication.

After a successful login, the API returns a token that can be used to access protected endpoints.

Example:

```json
{
  "status": "success",
  "message": "Login successful",
  "data": {
    "token": "YOUR_JWT_TOKEN"
  }
}
```

The token is sent through the `Authorization` header:

```http
Authorization: Bearer YOUR_JWT_TOKEN
```

The API verifies the token before allowing access to protected resources.

The token contains information such as the user ID, issuer, issued-at time, and expiration time.

## Example Requests

### Register a User

```http
POST /auth/signup
Content-Type: application/json
```

```json
{
  "name": "John Doe",
  "email": "john@example.com",
  "password": "password123"
}
```

### Login

```http
POST /auth/login
Content-Type: application/json
```

```json
{
  "email": "john@example.com",
  "password": "password123"
}
```

### Get Current User

```http
GET /profile/me
Authorization: Bearer YOUR_JWT_TOKEN
```

### Update Profile

```http
PUT /profile/me
Authorization: Bearer YOUR_JWT_TOKEN
Content-Type: application/json
```

```json
{
  "name": "John Smith",
  "email": "john@example.com"
}
```

### Delete Account

```http
DELETE /profile/me
Authorization: Bearer YOUR_JWT_TOKEN
```

## Response Format

The API uses a consistent JSON response structure.

### Successful Response

```json
{
  "status": "success",
  "message": "User profile retrieved",
  "data": {
    "user": {
      "id": 1,
      "name": "John Doe",
      "email": "john@example.com"
    }
  }
}
```

### Error Response

```json
{
  "status": "error",
  "message": "Invalid credentials"
}
```

Using a consistent response structure makes it easier for frontend applications and other clients to process API responses.

## Database

The project uses **MySQL** for persistent data storage.

The included `db.sql` file creates the required database and tables.

The users table contains fields such as:

- `id`
- `name`
- `email`
- `password`
- `created_at`
- `updated_at`

Passwords are stored using PHP's password hashing functionality rather than storing plain-text passwords.

## Rate Limiting

The API uses **Redis** to implement request rate limiting.

Multiple algorithms are experimented to track requests over a period of time and prevent excessive API calls.

Examples of the configured limits include:

| Endpoint | Limit |
|----------|-------|
| Signup | 5 requests / 60 seconds |
| Login | 5 requests / 60 seconds |
| Current profile | 60 requests / 60 seconds |
| User listing | 30 requests / 60 seconds |
| Profile update | 20 requests / 60 seconds |
| Account deletion | 10 requests / 60 seconds |

When a client exceeds its limit, the API returns:

```http
429 Too Many Requests
```

along with a JSON error response.

## HTTP Caching

The project also implements HTTP caching mechanisms.

The API can use:

- `Cache-Control`
- `ETag`
- `Last-Modified`
- `Vary`

For cacheable responses, an `ETag` and/or `Last-Modified` value can be used to determine whether the requested resource has changed.

If the resource has not changed, the server can respond with:

```http
304 Not Modified
```

This allows the client to reuse its cached representation instead of downloading the same data again.

Authenticated responses also use the `Authorization` header as part of cache variation to prevent responses from different users from being incorrectly shared.

## Request Protection

The API limits the size of incoming request bodies for methods such as:

```text
POST
PUT
PATCH
```

The current maximum payload size is **20 KB**.

Requests exceeding the configured limit receive:

```http
413 Payload Too Large
```

This provides a basic layer of protection against unnecessarily large request bodies.

## Response Compression

The API can compress responses when the client supports compression.

It checks the client's `Accept-Encoding` header and can use:

- Brotli (`br`)
- Gzip

Compression reduces the amount of data transferred between the server and client, particularly for larger JSON responses.

## Pagination

The user-list endpoint supports pagination so that the API does not need to return every user in a single response.

For example:

```text
/profile/users?page=1&limit=10
```

Pagination allows clients to retrieve results in smaller sets.

## Running Locally

### Requirements

You need:

- PHP
- Apache
- MySQL
- Redis
- PHP Redis extension
- Composer

An environment such as **XAMPP** can be used for Apache and MySQL.

### 1. Clone the Repository

```bash
git clone https://github.com/tashfinsami/project-api.git
cd project-api
```

### 2. Install Composer Dependencies

```bash
composer install
```

### 3. Create the Database

Import the included:

```text
db.sql
```

file into MySQL.

For example:

```sql
SOURCE db.sql;
```

### 4. Configure the Application

Update the configuration values in `config.php` for your local environment.

The application requires:

- MySQL connection details
- Redis connection details
- JWT secret key

**Do not commit production credentials or JWT secrets to the repository.**

### 5. Start Required Services

Make sure the following services are running:

```text
Apache
MySQL
Redis
```

### 6. Open the Application

Place the project inside the Apache web root and open the corresponding local URL in a browser.

The included HTML pages provide a simple interface for interacting with the API.

## Security Considerations

This project is primarily intended for learning and experimentation.

A production application would require additional security and operational considerations, including:

- HTTPS
- Secure secret management
- Environment-based configuration
- Prepared SQL statements
- Comprehensive input validation
- More granular authorization
- Production-grade logging
- Monitoring
- Security headers
- More extensive error handling
- Proper deployment configuration

Never commit real database credentials, API keys, JWT secrets, or other sensitive configuration values to a public repository.

## Future Improvements

Possible future improvements include:

- Role-based authorization
- Refresh tokens
- More comprehensive validation
- Automated API tests
- OpenAPI/Swagger documentation
- Docker-based deployment
- More granular middleware
- Improved logging
- Automated CI/CD
- Additional API resources
