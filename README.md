# Project API

A REST API built from scratch in PHP without using a PHP web framework.

The project explores the fundamentals of building a REST API manually, including HTTP request handling, routing, JWT authentication, MySQL database integration, Redis-based rate limiting, HTTP caching, pagination and standardized JSON responses.

## Why This Project?

This project was built to understand what happens underneath a web framework when building a REST API.

Instead of using frameworks such as Laravel or Symfony, the API is implemented directly with PHP and Apache. This provides a practical understanding of the core components that frameworks normally handle for developers.

The project demonstrates:

- HTTP methods and status codes
- JSON request and response handling
- JWT authentication
- Password hashing
- API routing with `.htaccess`
- MySQL database operations
- Redis-based rate limiting
- Sliding-window rate limiting
- HTTP caching
- ETags and `Last-Modified`
- Pagination
- Response compression
- Request payload protection
- Standardized JSON responses

## Features

- REST-style API endpoints
- User registration and login
- JWT-based authentication
- Protected user profile endpoints
- MySQL database integration
- Password hashing
- Redis-based rate limiting
- Sliding-window rate limiting
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
```
