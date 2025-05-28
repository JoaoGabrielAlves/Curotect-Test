# Vue Components Documentation

Vue.js components built with Vue 3 Composition API, TypeScript, and shadcn/ui.

## Page Components (Inertia.js Pages)

### Posts/Index.vue

**Location:** `resources/js/pages/Posts/Index.vue`

Public posts listing with data grid functionality.

```typescript
interface Props {
    posts: PaginatedResponse<Post>;
    categories: string[];
    filters: FilterState;
    showActions: boolean;
    showStatusFilter: boolean;
}
```

Features: Server-side pagination, sorting, filtering, search, URL state preservation

### Posts/MyPosts.vue

User's personal posts management. Same props as Index.vue with `showActions: true` and `showStatusFilter: true`.

### Posts/Show.vue

Single post view with comments.

```typescript
interface Props {
    post: Post & {
        user: User;
        comments: Comment[];
    };
}
```

### Posts/Create.vue & Posts/Edit.vue

Post creation and editing forms.

```typescript
interface Props {
    post?: Post; // Only for edit
    categories: string[];
}
```

Features: Rich text editor, status selection, category management, ETag handling (edit), real-time validation

## Reusable Components

### UI Components (shadcn/ui)

- **Buttons:** Primary, secondary, destructive variants with loading states
- **Forms:** Input, textarea, form validation display
- **Tables:** Responsive layouts with sorting indicators
- **Cards:** Content containers with consistent spacing

### Custom Components

#### DataGrid Component

**Location:** `resources/js/components/DataGrid/`

Split into sub-components:

- `DataGridTable.vue` - Main table
- `DataGridPagination.vue` - Pagination controls
- `DataGridFilters.vue` - Search and filter controls

```vue
<template>
    <div class="space-y-4">
        <DataGridFilters :filters="filters" :categories="categories" @update:filters="updateFilters" />

        <DataGridTable :posts="posts.data" :loading="loading" @row-click="viewPost" />

        <DataGridPagination :pagination="posts" @page-change="changePage" />
    </div>
</template>
```

Handles: Search (debounced), filtering, sorting, pagination, URL synchronization

#### Post Components

- **PostCard.vue** - Grid view cards
- **PostContent.vue** - Content display with formatting
- **CommentThread.vue** - Recursive nested comments
- **CommentForm.vue** - Add/edit comment form

## Layout Components

### AppLayout.vue

**Location:** `resources/js/layouts/AppLayout.vue`

Main layout with navigation, user menu, breadcrumbs, flash messages, real-time connection status.

### GuestLayout.vue

Simple layout for login/register pages.

## Composables

### useRealTimeUpdates

**Location:** `resources/js/composables/useRealTimeUpdates.ts`

```typescript
const { connectToRealTime, stats, recentPosts } = useRealTimeUpdates();
```

Features: WebSocket management, automatic reconnection, direct UI updates, shared state

### useAsyncOperation

**Location:** `resources/js/composables/useAsyncOperation.ts`

```typescript
const { execute, loading, error } = useAsyncOperation();

const deletePost = (post) => {
    execute(() => router.delete(`/posts/${post.id}`), {
        onSuccess: () => toast.success('Post deleted'),
        onError: () => toast.error('Failed to delete post'),
    });
};
```

### useOptimisticUpdates

**Location:** `resources/js/composables/useOptimisticUpdates.ts`

```typescript
const { optimisticDelete, optimisticUpdate } = useOptimisticUpdates();

const deletePost = (post) => {
    optimisticDelete(posts, post, () => router.delete(`/posts/${post.id}`));
};
```

## State Management (Pinia)

### usePostsStore

**Location:** `resources/js/stores/posts.ts`

```typescript
const postsStore = usePostsStore();
const { posts, loading } = storeToRefs(postsStore);
await postsStore.fetchPosts({ category: 'Technology' });
```

**State:** posts, loading, filters
**Actions:** fetchPosts, createPost, updatePost, deletePost

## Form Validation

Server-side: Laravel Form Requests
Client-side: Vue 3 reactive validation

```vue
<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';

const form = useForm({
    title: '',
    content: '',
    status: 'draft',
    category: '',
});

const submit = () => {
    form.post('/posts', {
        onError: () => {
            // Errors automatically available in form.errors
        },
    });
};
</script>

<template>
    <form @submit.prevent="submit">
        <Input v-model="form.title" :error="form.errors.title" placeholder="Post title" />

        <Button :loading="form.processing"> Create Post </Button>
    </form>
</template>
```

## Component Patterns

### Props & Events

```typescript
interface Props {
    post: Post;
    showActions?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    showActions: true,
});

interface Events {
    edit: [post: Post];
    delete: [post: Post];
}

const emit = defineEmits<Events>();
```

### Loading States

```vue
<template>
    <div v-if="loading" class="space-y-4">
        <Skeleton class="h-4 w-full" />
        <Skeleton class="h-4 w-3/4" />
    </div>
    <div v-else>
        <!-- Content -->
    </div>
</template>
```

### Error Handling

```vue
<template>
    <Alert v-if="error" variant="destructive">
        <AlertDescription>{{ error }}</AlertDescription>
    </Alert>
</template>
```

## Testing Components

```typescript
import { mount } from '@vue/test-utils';
import PostCard from '@/components/PostCard.vue';

test('displays post title and content', () => {
    const post = {
        id: 1,
        title: 'Test Post',
        content: 'Test content',
        status: 'published',
    };

    const wrapper = mount(PostCard, {
        props: { post },
    });

    expect(wrapper.text()).toContain('Test Post');
});

test('emits edit event when edit button clicked', async () => {
    const wrapper = mount(PostCard, {
        props: { post: mockPost, showActions: true },
    });

    await wrapper.find('[data-testid="edit-button"]').trigger('click');
    expect(wrapper.emitted('edit')).toBeTruthy();
});
```

## Performance Considerations

### Component Optimization

```typescript
// Lazy loading
const DataVisualization = defineAsyncComponent(() => import('@/components/DataVisualization.vue'));

// Computed properties
const filteredPosts = computed(() => posts.value.filter((post) => post.title.toLowerCase().includes(searchQuery.value.toLowerCase())));

// Debounced watchers
const debouncedSearch = debounce((query: string) => {
    fetchPosts({ search: query });
}, 300);

watch(searchQuery, debouncedSearch);
```

This covers the main components and patterns. Focus is on reusability, type safety, and consistent user experience.
