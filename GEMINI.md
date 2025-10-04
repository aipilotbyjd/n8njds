# Gemini Guidelines

This document provides guidelines for Gemini to follow when interacting with this project.

## Project Overview

This appears to be a backend application for a workflow automation tool, similar to n8n. The project is built with the Laravel framework. Key concepts include Workflows, Nodes, Executions, Organizations, Users, and Credentials.

## Tech Stack

*   **Backend:** PHP, Laravel
*   **Frontend:** JavaScript (likely with a framework, configured via `vite.config.js`)
*   **Database:** SQLite (based on `database/database.sqlite` and `config/database.php`)
*   **Dependencies:** Managed by Composer (`composer.json`) for PHP and npm/yarn (`package.json`) for JavaScript.

### Versions

*   **PHP:** 8.4.13
*   **Node.js:** v24.8.0
*   **Laravel:** ^12.0
*   **Vite:** ^7.0.7
*   **TailwindCSS:** ^4.0.0
*   **Axios:** ^1.11.0
*   **Passport:** ^13.0
*   **Spatie Permission:** ^6.21

## Key Directories

*   `app/`: Contains the core application logic, including Models, Controllers, Services, and Repositories.
*   `config/`: Application configuration files.
*   `database/`: Database migrations, factories, and seeders.
*   `routes/`: API and web routes are defined here (`api.php`, `web.php`).
*   `tests/`: Contains the test suite.

## Coding Style & Conventions

*   Follow the existing coding style and conventions found in the project.
*   Adhere to Laravel's best practices.
*   Use the repository pattern for data access (`app/Repositories`).
*   Use Data Transfer Objects (DTOs) for data transfer (`app/DataTransferObjects`).

## Testing

*   The project uses PHPUnit for testing. The configuration is in `phpunit.xml`.
*   Tests are located in the `tests/` directory.
*   When adding new features or fixing bugs, please include corresponding tests.

## Commands

*   Run database migrations with `php artisan migrate`.
*   Run tests with `./vendor/bin/phpunit` or `php artisan test`.

By following these guidelines, you can help me more effectively.
