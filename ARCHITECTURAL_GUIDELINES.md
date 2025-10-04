# Architectural Guidelines for FlowForge

This document outlines the architectural principles, patterns, and conventions to be followed throughout the development of the FlowForge project. Adhering to these rules will ensure the codebase is consistent, maintainable, scalable, and easy for any developer to understand.

---

## 1. Coding Style

- **Standard:** The project strictly follows the **PSR-12** coding style guide.
- **Enforcement:** Code formatting is not optional. **Laravel Pint** is included in the project's dev dependencies and must be used to enforce style.
- **Usage:** Before committing, run `./vendor/bin/pint` to automatically format your code.

---

## 2. Core Architectural Principles

- **SOLID:** The SOLID principles are the foundation of our architecture. All code should be written with these principles in mind:
    - **S**ingle Responsibility Principle
    - **O**pen/Closed Principle
    - **L**iskov Substitution Principle
    - **I**nterface Segregation Principle
    - **D**ependency Inversion Principle
- **DRY (Don't Repeat Yourself):** Avoid duplicating code. Abstract common functionality into reusable classes, traits, or methods.

---

## 3. Application Layers

Code is organized into distinct layers, each with a clear responsibility. Logic should not bleed between layers.

### a. Controllers (`app/Http/Controllers`)

- **Responsibility:** To act as the entry point for an HTTP request and to return an HTTP response. **Controllers must be kept thin.**
- **DO:**
    - Receive a `Request` object (preferably a dedicated Form Request).
    - Call a single method on a single **Service Class** to perform the business logic.
    - Receive the result from the service.
    - Return a response, preferably using an **API Resource**.
- **DO NOT:**
    - Contain any business logic.
    - Perform complex database queries.
    - Contain validation logic (use Form Requests).
    - Contain authorization logic (use Policies).

### b. Services (`app/Services`)

- **Responsibility:** To orchestrate and execute all core business logic.
- **Details:** This is where the "work" of the application happens. If it's not a direct HTTP or database action, it probably belongs in a service. For example, `WorkflowExecutor` is a service.
- Services can be injected into controllers via the constructor.

### c. Models (`app/Models`)

- **Responsibility:** To represent a database table and its relationships using Eloquent.
- **DO:**
    - Define `fillable`/`guarded` properties.
    - Define relationships (`hasMany`, `belongsTo`, etc.).
    - Define attribute casting.
    - Define simple Accessors & Mutators.
- **DO NOT:**
    - Contain business logic. Logic for creating or updating a model in a complex way belongs in a service.

### d. Nodes (`app/Nodes`)

- **Responsibility:** This is a project-specific pattern. Each `Node` class is a self-contained, single-responsibility unit of work within a workflow.
- Each node must extend the base `App\Nodes\Node` abstract class and implement the `execute` method.

---

## 4. Key Patterns & Conventions

### a. Validation

- **Rule:** All validation of incoming request data **must** be handled by dedicated **Form Request classes** (e.g., `CreateWorkflowRequest`).
- **Command:** `php artisan make:request YourRequestName`
- **Reason:** This keeps controllers clean and centralizes validation logic, making it reusable and easy to find.

### b. Authorization

- **Rule:** All authorization logic (checking if a user is allowed to perform an action) **must** be handled by **Laravel Policies** (e.g., `WorkflowPolicy`).
- **Command:** `php artisan make:policy YourPolicyName --model=YourModel`
- **Details:** Policies will use the Spatie roles and permissions system (`$user->can('permission.name')`) to make decisions. They are automatically discovered for a given model.
- **Reason:** This centralizes authorization logic and keeps it out of controllers and services.

### c. API Responses

- **Rule:** All API responses that return data from a model **must** be formatted using **Eloquent API Resources**.
- **Command:** `php artisan make:resource YourResourceName`
- **Reason:** This gives us full control over the JSON output, prevents accidentally leaking data, and allows for a standardized API structure.

### d. Configuration

- **Rule:** All configuration values must be stored in `config/*.php` files.
- **Rule:** Environment-specific values (API keys, database credentials) must be stored in `.env` and accessed in config files via `env('KEY_NAME')`.
- **DO NOT:** Hardcode any configuration values (e.g., API endpoints, magic numbers) directly in services, controllers, or other classes.

---

## 5. Testing

- **Rule:** New functionality is not considered complete until it is covered by automated tests.
- **Unit Tests (`tests/Unit`):** Used to test individual classes in isolation, such as Services, Nodes, and other custom PHP classes.
- **Feature Tests (`tests/Feature`):** Used to test API endpoints and the full request-response-database cycle. These are the most critical tests for ensuring application functionality.
