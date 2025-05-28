# Database Design & Schema

Database design decisions and schema for the Laravel Vue Inertia Challenge using PostgreSQL.

## Why PostgreSQL?

- **Full-text search** built right in
- **Better performance** for complex queries with joins/sorting/filtering
- **Advanced indexing** like GIN indexes for search
- **JSON support** for future flexibility

## Schema Overview

Classic blog setup:

```
Users (1) ----< Posts (1) ----< Comments
               |
               └----< Comments (replies via parent_id)
```

## Tables

### Users Table

Standard Laravel authentication table.

### Posts Table

```sql
CREATE TABLE posts (
    id BIGSERIAL PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    status VARCHAR(50) NOT NULL DEFAULT 'draft',
    category VARCHAR(100) NULL,
    views_count INTEGER NOT NULL DEFAULT 0,
    user_id BIGINT NOT NULL,
    published_at TIMESTAMP NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,

    CONSTRAINT fk_posts_user_id
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

**Key decisions:**

- `status` as varchar for flexibility (draft, published, archived)
- `views_count` for popularity tracking
- `published_at` separate from `created_at` for scheduling
- `category` as free text (simple for now)
- CASCADE DELETE cleans up user's posts

### Comments Table

```sql
CREATE TABLE comments (
    id BIGSERIAL PRIMARY KEY,
    content TEXT NOT NULL,
    status VARCHAR(50) NOT NULL DEFAULT 'pending',
    post_id BIGINT NOT NULL,
    user_id BIGINT NOT NULL,
    parent_id BIGINT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,

    CONSTRAINT fk_comments_post_id
        FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
    CONSTRAINT fk_comments_user_id
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_comments_parent_id
        FOREIGN KEY (parent_id) REFERENCES comments(id) ON DELETE CASCADE
);
```

- `parent_id` enables threaded replies
- `status` for moderation (pending, approved, rejected)
- CASCADE DELETE handles orphaned comments

## Performance Indexes

### Posts Indexes

```sql
-- Most common: published posts by date
CREATE INDEX idx_posts_status_created_at ON posts(status, created_at);

-- Category filtering
CREATE INDEX idx_posts_category ON posts(category);

-- User's posts
CREATE INDEX idx_posts_user_id ON posts(user_id);

-- Trending posts
CREATE INDEX idx_posts_trending ON posts(status, created_at, views_count);

-- Full-text search
CREATE INDEX idx_posts_search ON posts
USING GIN(to_tsvector('english', title || ' ' || content));
```

### Comments Indexes

```sql
-- Comments for a post
CREATE INDEX idx_comments_post_id ON comments(post_id);

-- User's comments
CREATE INDEX idx_comments_user_id ON comments(user_id);

-- Comment replies
CREATE INDEX idx_comments_parent_id ON comments(parent_id);

-- Approved comments by post
CREATE INDEX idx_comments_post_status ON comments(post_id, status);
```

**Why these indexes:**

- Match our WHERE + ORDER BY patterns
- GIN index enables fast full-text search
- Composite indexes for common query combinations

## Query Patterns

### Common Queries

**Published posts with pagination:**

```sql
SELECT posts.*, users.name as user_name
FROM posts
LEFT JOIN users ON posts.user_id = users.id
WHERE posts.status = 'published'
ORDER BY posts.created_at DESC
LIMIT 15;
```

Uses: `idx_posts_status_created_at`

**Search posts:**

```sql
SELECT *
FROM posts
WHERE to_tsvector('english', title || ' ' || content)
      @@ plainto_tsquery('english', 'search terms')
  AND status = 'published'
ORDER BY created_at DESC;
```

Uses: `idx_posts_search` + `idx_posts_status_created_at`

## Relationships in Laravel

```php
// User model
public function posts() { return $this->hasMany(Post::class); }

// Post model
public function user() { return $this->belongsTo(User::class); }
public function comments() { return $this->hasMany(Comment::class); }

// Comment model
public function post() { return $this->belongsTo(Post::class); }
public function replies() { return $this->hasMany(Comment::class, 'parent_id'); }
public function parent() { return $this->belongsTo(Comment::class, 'parent_id'); }
```

## Seeding Strategy

Creates realistic test data:

- 1 test user (`test@example.com` / `password`)
- 10+ additional users
- 50 posts with mix of statuses/categories
- 200 comments including nested replies

## Data Constraints

### Database Level

```sql
ALTER TABLE posts ADD CONSTRAINT chk_posts_views_count CHECK (views_count >= 0);
ALTER TABLE posts ADD CONSTRAINT chk_posts_status CHECK (status IN ('draft', 'published', 'archived'));
```

### Application Level

```php
// StorePostRequest
'title' => 'required|string|min:3|max:255',
'content' => 'required|string|min:10',
'status' => 'required|in:draft,published,archived',
'category' => 'nullable|string|max:100'
```

## Performance Monitoring

### Slow Query Detection

```php
DB::listen(function ($query) {
    if ($query->time > 1000) {
        Log::warning('Slow query detected', [
            'sql' => $query->sql,
            'time' => $query->time
        ]);
    }
});
```

### Monitor Index Usage

```sql
-- Check index usage
SELECT indexname, idx_scan, idx_tup_read
FROM pg_stat_user_indexes
ORDER BY idx_scan DESC;
```

## Common Issues & Solutions

### N+1 Query Problems

**Solution:** Use eager loading

```php
Post::with('user:id,name')->get();
```

### Concurrent Updates

**Solution:** ETag-based optimistic locking (implemented in application layer)

## Backup & Maintenance

```bash
# Backup
pg_dump curotec_challenge > backup_$(date +%Y%m%d).sql

# Restore
psql curotec_challenge < backup_file.sql

# Maintenance
ANALYZE;  # Update statistics
VACUUM;   # Reclaim space
```

## Future Scaling

**If we needed to scale:**

- Read replicas for heavy read workloads
- Connection pooling with pgBouncer
- Table partitioning by date for large datasets
- More aggressive caching with Redis

This database design is simple but supports all current features. The schema can evolve as needed while maintaining performance through strategic indexing.
