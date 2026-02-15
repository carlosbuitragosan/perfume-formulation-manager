# Perfumery OS (Naturals Perfumery)

Perfumery OS is a full-stack Laravel application designed as a structured database for perfumers to manage **materials**, **bottle batches**, and **blends**. The project was developed using **Test-Driven Development (TDD)** principles with **Pest**.

The application focuses on data integrity, validation, and reproducible logic for managing complex ingredient relationships.

---

## Features

### Materials

- Create, edit, view, and delete perfumery materials
- Store structured attributes such as botanical data, pyramid classification, families, functions, effects, safety notes, and IFRA maximum percentages
- Search across text fields and structured JSON attributes

### Bottles (Inventory Management)

- Add and manage bottle batches linked to materials
- Track bottle status (active / finished)
- Upload and manage related documentation
- Maintain relational integrity between materials and bottles

### Blends

- Create blends composed of multiple materials
- Prevent duplicate ingredient entries
- Validate input and enforce structured business logic
- Calculate ingredient proportions for display

### Demo Access

A demo login route is available to allow reviewers to explore the system with sample data.

---

## Tech Stack

- Laravel (PHP)
- Livewire
- Blade templates
- Tailwind CSS
- Vite
- Pest (testing framework)
- PostgreSQL / MySQL compatible

---

## Testing

This project follows a Test-Driven Development approach using Pest.

- Feature tests use database refresh for isolation
- Business rules are validated through structured test cases
- Tests verify database integrity, validation rules, and application behaviour

Run tests locally:

- `php artisan test`
- `composer test`

---

## Local Setup

### Requirements

- PHP 8.2+
- Composer
- Node.js + npm
- Local database (MySQL, PostgreSQL, or SQLite)

### Installation

1. Install backend dependencies:
   - `composer install`

2. Install frontend dependencies:
   - `npm install`

3. Create environment file and app key:
   - `cp .env.example .env`
   - `php artisan key:generate`

4. Run migrations:
   - `php artisan migrate`

5. Start the dev build and server:
   - `npm run dev`
   - `php artisan serve`

---

## Purpose

Perfumery OS was built as a structured long-term application to manage real perfumery data while practicing disciplined backend architecture, validation, and testing workflows.

If reviewing this repository for hiring purposes, please explore the test suite and database relationships to understand the system design.
