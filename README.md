# SupplyMatch

SupplyMatch is a B2B sourcing platform where companies publish procurement requests and suppliers submit proposals.

The project is being built as a fullstack portfolio application with a Laravel API backend and a Vue SPA frontend.

## Goal

The main goal of this project is to demonstrate clean fullstack engineering practices:

* Laravel API backend
* Vue 3 SPA frontend
* PostgreSQL database
* Authentication and authorization
* Role-based access control
* Business workflows with explicit statuses
* Search and filtering
* Automated tests
* API documentation
* Docker-based local environment
* GitHub Actions CI
* Production-ready deployment foundation

## Tech Stack

### Backend

Current setup:

* PHP 8.3
* Laravel 13
* PostgreSQL 17
* Nginx
* PHP-FPM
* PHPUnit
* Docker Compose

Planned backend stack:

* Laravel Sanctum
* OpenAPI documentation
* Static analysis
* Code style checks
* GitHub Actions CI

### Frontend

Current setup:

* Vue 3
* TypeScript
* Vue Router
* Pinia
* Vite
* Vitest
* ESLint
* Prettier

## Project Structure

```text
supply-match/
  backend/   Laravel API
  frontend/  Vue SPA
  docs/      Project documentation
  docker/    Docker configuration
```

## Docker Development

The backend runs in Docker using the following services:

```text
Browser
  -> Nginx container
  -> PHP-FPM container
  -> Laravel API
  -> PostgreSQL container
```

The frontend currently runs separately through the Vite development server.

### Environment Files

Copy environment files:

```bash
cp .env.example .env
cp backend/.env.example backend/.env
cp frontend/.env.example frontend/.env
```

Root `.env` is used by Docker Compose.

Backend `.env` is used by Laravel.

Frontend `.env` is used by Vite.

### Start Docker Services

```bash
docker compose up -d --build
```

### Install Backend Dependencies

```bash
docker compose exec php composer install
```

### Generate Application Key

```bash
docker compose exec php php artisan key:generate
```

### Run Database Migrations

```bash
docker compose exec php php artisan migrate
```

### Backend API

```text
http://127.0.0.1:8080
```

### Health Check

```text
http://127.0.0.1:8080/api/health
```

Expected response:

```json
{
  "status": "ok",
  "service": "supply-match-api"
}
```

### PostgreSQL

PostgreSQL is available from the host machine at:

```text
127.0.0.1:5433
```

Default local credentials:

```text
Database: supply_match
User:     supply_match_user
Password: supply_match_password
```

Inside Docker, Laravel connects to PostgreSQL using the service name:

```text
postgres:5432
```

## Frontend Development

Install frontend dependencies:

```bash
cd frontend
npm install
```

Start Vite development server:

```bash
npm run dev
```

Frontend application:

```text
http://127.0.0.1:5173
```

The frontend API base URL is configured in `frontend/.env`:

```env
VITE_API_BASE_URL=http://127.0.0.1:8080/api
```

## Quality Checks

### Backend

Run backend tests inside the PHP container:

```bash
docker compose exec php php artisan test
```

### Frontend

```bash
cd frontend
npm run type-check
npm run lint
npm run test:unit -- --run
```

## Domain Overview

SupplyMatch focuses on B2B procurement and supplier discovery.

Main roles:

* Guest
* Buyer
* Supplier
* Admin

Main entities:

* Company
* User
* Category
* SourcingRequest
* RequestKeyword
* SupplierProposal
* AuditLog

## Planned Features

### Authentication

* User registration
* Login and logout
* API token authentication
* Current user endpoint

### Companies

* Company registration
* User-company relationship
* Buyer and supplier roles

### Sourcing Requests

* Create procurement requests
* Edit drafts
* Submit requests for moderation
* Publish or reject requests as admin
* Archive requests

### Supplier Proposals

* Submit proposals to published requests
* View own proposals
* Withdraw submitted proposals
* Accept or decline proposals as buyer

### Search

* Full-text search by request title, description, requirements, and keywords
* Category filter
* Region filter
* Sorting by relevance and date

### Administration

* Request moderation
* Category management
* Audit log

## Current Status

The project is in the initial setup stage.

Implemented so far:

* Laravel backend skeleton
* Laravel API-only mode
* Vue 3 frontend skeleton
* TypeScript support
* Vue Router
* Pinia
* Unit testing setup
* Linting and formatting setup
* Root monorepo structure
* Docker Compose environment
* Nginx container
* PHP-FPM container
* PostgreSQL container
* Backend health check endpoint
* Backend database connection
* Initial Laravel migrations

## Roadmap

* Add Laravel Sanctum
* Implement authentication
* Add company model
* Add roles and policies
* Add sourcing request model and status transitions
* Add supplier proposal model and status transitions
* Add search
* Add OpenAPI documentation
* Add GitHub Actions CI
* Add production deployment configuration
