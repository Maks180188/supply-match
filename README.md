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

## Tech Stack

### Backend

Current setup:

* PHP 8.3+
* Laravel 13
* PHPUnit

Planned backend stack:

* PostgreSQL
* Laravel Sanctum
* OpenAPI documentation
* Static analysis
* Code style checks
* Docker

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

## Local Development

### Backend

```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate
php artisan serve
```

Backend application:

```text
http://127.0.0.1:8000
```

### Frontend

```bash
cd frontend
npm install
npm run dev
```

Frontend application:

```text
http://127.0.0.1:5173
```

## Quality Checks

### Backend

```bash
cd backend
php artisan test
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
* Vue 3 frontend skeleton
* TypeScript support
* Vue Router
* Pinia
* Unit testing setup
* Linting and formatting setup
* Root monorepo structure

## Roadmap

* Configure PostgreSQL
* Configure Docker Compose
* Convert Laravel backend to API-first structure
* Add Laravel Sanctum
* Implement authentication
* Add company model
* Add roles and policies
* Add sourcing request model and status transitions
* Add supplier proposal model and status transitions
* Add search
* Add OpenAPI documentation
* Add GitHub Actions CI
