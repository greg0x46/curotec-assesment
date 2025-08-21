# Project Setup

## Prerequisites
- PostgreSQL server
  
## Environment Configuration
Create your .env and .env.testing files based on .env.example

## Broadcasting
Notifications require a broadcast driver Reverb.  Start the broadcast server in a separate terminal:

```bash
php artisan reverb:start
```

## Queue Worker
Background jobs rely on a running queue worker.  Start one with:

```bash
php artisan queue:work
```

## Assumptions
- PostgreSQL is the default database unless overridden in `.env`.
- Both the broadcast server and a queue worker must be running for notifications and reminder jobs.

# API Documentation

## API Endpoints

### Categories
- **GET** `/categories` – Paginated list of categories (10 per page)
- **POST** `/categories` – Create category (name must be unique and ≥ 3 chars)
- **PUT/PATCH** `/categories/{category}` – Update category by ID
- **DELETE** `/categories/{category}` – Soft-delete category by ID

### Tasks
- **GET** `/tasks` – Paginated task list with optional filters (`status`, `priority`, `category`, `q`)
- **POST** `/tasks` – Create new task; owner set to authenticated user; categories optional
- **PUT/PATCH** `/tasks/{task}` – Update task; reassigning requires ownership; sync categories if provided
- **DELETE** `/tasks/{task}` – Remove task after authorization check

### Settings (Authenticated Routes)
- **GET** `/settings/profile` – View profile
- **PATCH** `/settings/profile` – Update profile
- **DELETE** `/settings/profile` – Delete profile (password confirmation required)
- **GET** `/settings/password` – View password settings
- **PUT** `/settings/password` – Update password (throttled)
- **GET** `/settings/appearance` – View appearance settings

### Authentication (Guest Routes)
- **GET/POST** `/register` – Registration form & submission
- **GET/POST** `/login` – Login form & submission
- **GET/POST** `/forgot-password` – Request password reset
- **GET** `/reset-password/{token}` / **POST** `/reset-password` – Reset password via token

### Authentication (Authenticated Routes)
- **GET** `/verify-email` – Email verification notice
- **GET** `/verify-email/{id}/{hash}` – Verify email (signed & throttled)
- **POST** `/email/verification-notification` – Resend verification email
- **GET/POST** `/confirm-password` – Password confirmation before sensitive actions
- **POST** `/logout` – End session

---

## Data Models

### Category
- **Fields:** `id`, `name`, `timestamps`, `soft deletes`
- **Mass assignable:** `name` (string)
- **Relationships:** Many-to-many with tasks (includes timestamps)

### Task
- **Fields:**
    - `id`, `title`, `description`, `priority`, `status`, `due_date`
    - `timestamps`, `soft deletes`
    - `owner_id`, `assigned_to_id` (indexed)
- **Mass assignable:** `owner_id`, `assigned_to_id`, `title`, `description`, `priority`, `due_date`, `status`
- **Casts:**
    - `status` → `TaskStatus`
    - `priority` → `TaskPriority`
    - `due_date` → immutable datetime
- **Relationships:**
    - Belongs to owner (User)
    - Optional assignee (User)
    - Many-to-many categories (with timestamps)
- **Query scopes:**
    - Full-text-like search
    - Filter by status, priority, category
    - Composite filter helper
- **Enums:**
    - `TaskPriority`: low, normal, high
    - `TaskStatus`: pending, done

### User
- **Fields:** `id`, `name`, `email`, `email_verified_at`, `password`, `remember_token`, `timestamps`
- **Mass assignable:** `name`, `email`, `password`
- **Hidden:** `password`, `remember_token`
- **Casts:**
    - `email_verified_at` → datetime
    - `password` → hashed

### Pivot: CategoryTask
- **Fields:** `category_id`, `task_id`, `timestamps`
- **Indexes:** on both foreign keys
