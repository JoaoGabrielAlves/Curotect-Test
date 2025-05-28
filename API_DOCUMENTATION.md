# API Documentation

Main endpoints and data structures for the Laravel Vue Inertia Challenge application.

## How Inertia.js Works

Instead of separate API endpoints, Inertia.js uses Laravel routes that return page components with props:

- No separate API versioning needed
- Laravel validation and authorization work normally
- Vue components receive typed props from Laravel controllers
- Form submissions go to Laravel routes and redirect on success

## Authentication

### Login

**Route:** `POST /login`

```javascript
router.post('/login', {
    email: 'test@example.com',
    password: 'password',
    remember: true,
});
```

### Logout

**Route:** `POST /logout`

## Posts Management

### View All Posts (Public)

**Route:** `GET /posts`

Public listing of published posts with data grid features.

**Query Parameters:**

- `search` - Search in title and content
- `category` - Filter by category
- `sort` - Sort field: `title`, `created_at`, `views_count`, `user_name`
- `direction` - Sort direction: `asc` or `desc`
- `per_page` - Page size: 5, 10, 15, 25, 50, 100
- `page` - Page number

### My Posts (User's Posts)

**Route:** `GET /posts/my`

User's personal posts with management capabilities. Same parameters as public view plus:

- `status` filter (draft, published, archived)
- Edit/delete actions enabled

### View Single Post

**Route:** `GET /posts/{id}`

Shows post details with comments. Increments view count automatically.

### Create Post

**Route:** `GET /posts/create` (form) | `POST /posts` (submit)

**Form Data:**

```typescript
interface PostFormData {
    title: string; // min:3, max:255
    content: string; // min:10
    status: 'draft' | 'published' | 'archived';
    category?: string; // max:100, optional
}
```

### Edit Post

**Route:** `GET /posts/{id}/edit` (form) | `PUT /posts/{id}` (submit)

Only accessible by post owner. Includes ETag for concurrency control:

```typescript
interface UpdatePostFormData extends PostFormData {
    etag: string; // Required for concurrency control
}
```

### Delete Post

**Route:** `DELETE /posts/{id}`

Only accessible by post owner.

## Comments

### Add Comment

**Route:** `POST /posts/{post}/comments`

```typescript
interface CommentFormData {
    content: string; // min:3
    parent_id?: number; // For replies
}
```

## Data Structures

### Post Object

```typescript
interface Post {
    id: number;
    title: string;
    content: string;
    status: 'draft' | 'published' | 'archived';
    category: string | null;
    views_count: number;
    user_id: number;
    published_at: string | null;
    created_at: string;
    updated_at: string;
    etag: string; // For concurrency control

    // Relationships
    user?: User;
    comments?: Comment[];
    comments_count?: number;
}
```

### User Object

```typescript
interface User {
    id: number;
    name: string;
    email: string;
    created_at: string;
    updated_at: string;
}
```

### Comment Object

```typescript
interface Comment {
    id: number;
    content: string;
    status: 'pending' | 'approved' | 'rejected';
    post_id: number;
    user_id: number;
    parent_id: number | null;
    created_at: string;
    updated_at: string;

    // Relationships
    user?: User;
    replies?: Comment[];
    parent?: Comment;
}
```

### Pagination Response

```typescript
interface PaginatedResponse<T> {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number;
    to: number;
    links: PaginationLink[];
}
```

### Filter State

```typescript
interface FilterState {
    search?: string;
    category?: string;
    status?: string;
    sort?: string;
    direction?: 'asc' | 'desc';
    per_page?: number;
}
```

## Real-time Events

### WebSocket Connection

```javascript
Echo.channel('posts')
    .listen('PostCreated', (event) => {
        // Handle new post
    })
    .listen('PostUpdated', (event) => {
        // Handle post update
    });
```

### Event Payloads

```typescript
interface PostCreatedEvent {
    post: Post & { user: User };
}

interface PostUpdatedEvent {
    post: Post & { user: User };
}

interface PostDeletedEvent {
    post: { id: number };
}

interface PostViewedEvent {
    post_id: number;
    views_count: number;
}
```

## Error Handling

### Validation Errors

Validation errors return to previous page with errors in the `errors` prop:

```vue
<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
const { errors } = usePage().props;
</script>

<template>
    <div v-if="errors.title" class="error">
        {{ errors.title }}
    </div>
</template>
```

### Authorization Errors

- **401 Unauthorized:** Redirects to login
- **403 Forbidden:** Shows access denied message

### Concurrency Conflicts

ETag mismatches return validation errors:

```php
if ($request->input('etag') !== $post->etag) {
    return back()->withErrors([
        'etag' => 'Post modified by another user. Please refresh and try again.'
    ]);
}
```

## Frontend Usage Examples

### Page Navigation

```javascript
router.get('/posts', {
    search: 'vue',
    category: 'Technology',
});
```

### Form Submission

```javascript
router.post('/posts', formData, {
    onStart: () => (loading.value = true),
    onFinish: () => (loading.value = false),
    onSuccess: () => toast.success('Post created!'),
    onError: () => toast.error('Failed to create post'),
});
```

### Optimistic Updates

```javascript
const deletePost = async (post) => {
    const originalPosts = [...posts.value];
    posts.value = posts.value.filter((p) => p.id !== post.id);

    router.delete(`/posts/${post.id}`, {
        onError: () => {
            posts.value = originalPosts;
            toast.error('Failed to delete post');
        },
    });
};
```

### Real-time Updates

```javascript
Echo.channel('posts').listen('PostCreated', (event) => {
    posts.value.unshift(event.post);
});
```

## Rate Limiting

- **General routes:** 60 requests per minute per IP
- **Authentication routes:** 5 attempts per minute per IP

Rate limit headers included in responses:

```
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 59
X-RateLimit-Reset: 1642234567
```

This covers the main API interactions. Inertia.js simplifies things significantly compared to traditional REST APIs while providing all required functionality.
