# Architecture Documentation

Key architectural decisions and design patterns for this Laravel + Vue + Inertia application.

## Overall Approach

**Modern monolith** using Inertia.js to bridge Laravel and Vue. This gives us server-side Laravel benefits without separate API complexity.

### Core Principles

- Keep it simple
- Separate concerns (controllers → services → repositories)
- Test everything
- Plan for growth without over-engineering

## Backend Architecture

### Repository Pattern

Abstract database queries from business logic. Makes testing easier.

```php
interface PostRepositoryInterface
{
    public function getPaginatedPosts(array $filters = [], int $perPage = 15): LengthAwarePaginator;
    public function findWithRelations(int $id, array $relations = []): ?Post;
}
```

### Service Layer

Controllers stay thin. Complex business logic goes in services.

```php
class PostService
{
    public function createPost(array $data, User $user): Post
    {
        return DB::transaction(function () use ($data, $user) {
            $post = $this->postRepository->create($data + ['user_id' => $user->id]);
            if ($post->status === 'published') {
                event(new PostCreated($post));
            }
            return $post;
        });
    }
}
```

### Event System

Real-time updates and loose coupling.

```php
event(new PostCreated($post));
event(new PostUpdated($post));
```

Events handle broadcasting, cache invalidation, and statistics updates.

### Authorization

Simple rule: Users can only edit their own posts.

```php
class PostPolicy
{
    public function update(User $user, Post $post): bool
    {
        return $user->id === $post->user_id;
    }
}
```

## Frontend Architecture

### Vue 3 + TypeScript + Inertia

Page components receive props from Laravel controllers:

```vue
<script setup lang="ts">
interface Props {
    posts: PaginatedResponse<Post>;
    filters: FilterState;
}
const props = defineProps<Props>();
</script>
```

### Pinia State Management

```typescript
export const usePostsStore = defineStore('posts', () => {
    const posts = ref<Post[]>([]);
    const loading = ref(false);
    const publishedPosts = computed(() => posts.value.filter((post) => post.status === 'published'));
    return { posts: readonly(posts), publishedPosts, fetchPosts };
});
```

### Component Organization

- **Pages** (`resources/js/pages/`) - Inertia.js full page components
- **Components** (`resources/js/components/`) - Reusable UI components
- **Layouts** (`resources/js/layouts/`) - Page layout wrappers

## Key Design Decisions

### Optimistic UI Updates

Update UI immediately, rollback on errors.

```vue
const deletePost = async (post: Post) => { // Remove from UI immediately const originalPosts = [...posts.value]; posts.value = posts.value.filter(p =>
p.id !== post.id); try { await router.delete(`/posts/${post.id}`); } catch (error) { // Rollback on error posts.value = originalPosts;
toast.error('Failed to delete post'); } };
```

**Trade-offs:**

- ✅ Much better UX
- ❌ More complex error handling

### ETag Concurrency Control

Prevent users from overwriting each other's changes.

1. Every post has an `etag` field (hash of updated_at + id)
2. Edit forms include current ETag
3. Update requests check ETag before saving

```php
if ($request->input('etag') !== $post->etag) {
    return back()->withErrors(['etag' => 'Post modified by another user']);
}
```

### Real-time with Laravel Echo

```javascript
Echo.channel('posts')
    .listen('PostCreated', (e) => posts.value.unshift(e.post))
    .listen('PostUpdated', (e) => {
        const index = posts.value.findIndex((p) => p.id === e.post.id);
        if (index !== -1) posts.value[index] = e.post;
    });
```

## Database Design

### Schema Highlights

- `posts` table with status workflow (draft/published/archived)
- `views_count` for popularity tracking
- Foreign keys with cascade delete
- Strategic indexes for query patterns

### Performance Indexes

```sql
CREATE INDEX idx_posts_status_created_at ON posts(status, created_at);
CREATE INDEX idx_posts_search ON posts USING GIN(to_tsvector('english', title || ' ' || content));
```

### Why PostgreSQL

- Full-text search built-in
- Advanced indexing (GIN)
- Better performance for complex queries

## Testing Strategy

### Pest over PHPUnit

More readable syntax, better Laravel integration.

```php
it('can filter posts by category', function () {
    $posts = Post::factory(10)->create(['category' => 'Technology']);

    $response = $this->get('/posts?category=Technology');

    $response->assertInertia(fn (Assert $page) =>
        $page->component('Posts/Index')->has('posts.data', 10)
    );
});
```

### Test Structure

- **Feature tests:** Full stack (controller → service → repository → database)
- **Unit tests:** Individual classes with mocks

## Performance Considerations

### Caching Strategy

- Query result caching with Redis
- Strategic indexing for common queries
- Eager loading to prevent N+1 problems
- Pagination for large datasets

### Database Optimization

- Strategic indexing based on query patterns
- Eager loading with `with()`
- Query scopes for reusable logic

## Security

### Input Validation

- **Server-side:** Laravel Form Requests
- **Client-side:** Vue components for immediate feedback

### Authorization

- Middleware for authentication
- Policies for specific permissions
- Custom middleware for common checks

## Scaling Considerations

**If we needed to scale:**

- Database: Read replicas, connection pooling
- Cache: Redis clustering, smarter invalidation
- Frontend: CDN, code splitting
- Real-time: Dedicated WebSocket servers

## Conclusion

This architecture balances simplicity with functionality. We prioritized working user stories over massive scale. The modular structure makes refactoring easy if needed.

Key insight: Inertia.js lets you build modern SPAs without separate API complexity while keeping Laravel's ecosystem benefits.
