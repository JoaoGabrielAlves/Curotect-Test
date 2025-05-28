# Curotec Challenge - Laravel Vue Inertia Dashboard

A full-stack blog application built with Laravel 12, Vue.js 3, and Inertia.js. Features interactive data grids, real-time updates, and clean architecture patterns.

## What's Built

- **Interactive Data Grid** - Browse posts with search, filtering, sorting, pagination
- **My Posts Management** - Personal dashboard for drafts/published posts
- **Real-time Updates** - Live stats without refreshing
- **Comment System** - Nested comment threads
- **Role-based Access** - Users can only edit their own posts

## Tech Stack

**Backend:** Laravel 12, PostgreSQL 15+, Redis, Laravel Echo + Reverb, Pest
**Frontend:** Vue.js 3, Inertia.js, Pinia, TypeScript, shadcn/ui

## Quick Setup

### Installation

```bash
git clone <repository-url>
cd curotec-challenge
composer install && npm install
cp .env.example .env && php artisan key:generate
```

### Database Setup

```bash
# Update .env with PostgreSQL credentials
createdb curotec_challenge && createdb curotec_challenge_test
php artisan migrate && php artisan db:seed
```

### Build & Run

```bash
npm run build
php artisan serve
# Separate terminals:
php artisan reverb:start  # Real-time features
php artisan queue:work    # Background jobs
```

**Login:** `test@example.com` / `password` (after seeding)

## Testing

```bash
php artisan test                              # All tests
php artisan test --filter="PostsDataGridTest" # Specific suite
php artisan test --coverage                   # With coverage
```

## Key Features Implemented

### STORY-001: Interactive Data Grid

- Server-side pagination with customizable page sizes
- Multi-column sorting (title, date, views, author)
- Search and category filtering
- URL state preservation (bookmarkable filters)
- Optimized queries (2 queries max per request)

### STORY-002: Real-time Data Management

- Optimistic UI updates with rollback on errors
- ETag-based concurrency control
- Server-side validation with detailed errors
- Real-time broadcasting across users
- Graceful error handling

## Architecture Highlights

**Backend:** Repository pattern + Service layer + Event broadcasting
**Frontend:** Vue 3 Composition API + Pinia + TypeScript
**Real-time:** Laravel Echo + Reverb WebSocket server
**Database:** PostgreSQL with strategic indexing and full-text search

## Code Quality

Pre-commit/pre-push hooks run automatically:

- Laravel Pint (PHP formatting)
- ESLint + Prettier (JS/Vue)
- Full test suite

## Configuration

```env
# Real-time (Local)
BROADCAST_DRIVER=reverb
REVERB_APP_KEY=your-app-key

# Database
DB_CONNECTION=pgsql
DB_DATABASE=curotec_challenge

# Cache
CACHE_DRIVER=redis
```

## Documentation

- **[ARCHITECTURE.md](./ARCHITECTURE.md)** - Design decisions, patterns, and trade-offs
- **[DATABASE.md](./DATABASE.md)** - Schema design, indexes, and optimization
- **[API_DOCUMENTATION.md](./API_DOCUMENTATION.md)** - Inertia.js endpoints and data structures
- **[COMPONENTS.md](./COMPONENTS.md)** - Vue.js components and patterns

## Troubleshooting

```bash
php artisan reverb:start --debug  # Real-time issues
php artisan tinker >>> DB::connection()->getPdo()  # DB connection
npm run build && php artisan view:clear  # Asset issues
```

---

Built for the Curotec technical assessment, showcasing Laravel and Vue.js best practices.
