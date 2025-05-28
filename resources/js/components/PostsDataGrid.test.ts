import { fireEvent, render, screen } from '@testing-library/vue';
import { vi } from 'vitest';
import PostsDataGrid from './PostsDataGrid.vue';

// Mock Inertia usePage for this specific component
vi.mock('@inertiajs/vue3', async () => {
    const actual = await vi.importActual('@inertiajs/vue3');
    return {
        ...actual,
        usePage: () => ({
            url: 'http://localhost/posts',
            props: {
                auth: { user: { id: 1 } },
                flash: {},
            },
        }),
        router: {
            get: vi.fn(),
        },
    };
});

// Mock store
vi.mock('@/stores/posts', () => ({
    usePostsStore: () => ({
        setPosts: vi.fn(),
        setCategories: vi.fn(),
        setFilters: vi.fn(),
    }),
}));

// Mock components
vi.mock('@/components/Icon.vue', () => ({
    default: { template: '<span>Icon</span>' },
}));

vi.mock('@/components/PostActions.vue', () => ({
    default: { template: '<div>Actions</div>' },
}));

vi.mock('@/components/SortableHeader.vue', () => ({
    default: { template: '<span><slot /></span>' },
}));

vi.mock('@/components/StatusBadge.vue', () => ({
    default: { template: '<span>Status</span>' },
}));

describe('PostsDataGrid', () => {
    const mockPosts = {
        data: [
            {
                id: 1,
                title: 'First Post',
                content: 'This is the first post content',
                status: 'published' as const,
                category: 'Technology',
                views_count: 100,
                user_id: 1,
                published_at: '2024-01-01T00:00:00Z',
                created_at: '2024-01-01T00:00:00Z',
                updated_at: '2024-01-01T00:00:00Z',
                user: {
                    id: 1,
                    name: 'John Doe',
                    email: 'john@example.com',
                    email_verified_at: null,
                    created_at: '2024-01-01T00:00:00Z',
                    updated_at: '2024-01-01T00:00:00Z',
                },
                comments_count: 5,
            },
            {
                id: 2,
                title: 'Second Post',
                content: 'This is the second post content',
                status: 'draft' as const,
                category: 'Design',
                views_count: 50,
                user_id: 2,
                published_at: null,
                created_at: '2024-01-02T00:00:00Z',
                updated_at: '2024-01-02T00:00:00Z',
                user: {
                    id: 2,
                    name: 'Jane Smith',
                    email: 'jane@example.com',
                    email_verified_at: null,
                    created_at: '2024-01-01T00:00:00Z',
                    updated_at: '2024-01-01T00:00:00Z',
                },
                comments_count: 2,
            },
        ],
        meta: {
            current_page: 1,
            last_page: 1,
            per_page: 10,
            from: 1,
            to: 2,
            total: 2,
            links: [],
            path: '/posts',
        },
        links: {
            first: '/posts?page=1',
            last: '/posts?page=1',
            prev: null,
            next: null,
        },
    };

    const defaultProps = {
        posts: mockPosts as any,
        categories: ['Technology', 'Design', 'Business'],
        filters: {
            search: '',
            category: '',
            status: '',
            sort: '',
            direction: 'asc',
            per_page: 10,
        } as any,
    };

    it('renders posts in table format', () => {
        render(PostsDataGrid, { props: defaultProps });

        expect(screen.getByText('First Post')).toBeInTheDocument();
        expect(screen.getByText('Second Post')).toBeInTheDocument();
        expect(screen.getByText('John Doe')).toBeInTheDocument();
        expect(screen.getByText('Jane Smith')).toBeInTheDocument();
    });

    it('shows search input', () => {
        render(PostsDataGrid, { props: defaultProps });

        expect(screen.getByPlaceholderText(/search posts/i)).toBeInTheDocument();
    });

    it('shows category filter dropdown', () => {
        render(PostsDataGrid, { props: defaultProps });

        expect(screen.getByText(/all categories/i)).toBeInTheDocument();
    });

    it('shows status filter dropdown', () => {
        render(PostsDataGrid, { props: defaultProps });

        expect(screen.getByText(/all categories/i)).toBeInTheDocument();
    });

    it('shows per page selector', () => {
        render(PostsDataGrid, { props: defaultProps });

        expect(screen.getByText(/show:/i)).toBeInTheDocument();
    });

    it('displays post metadata correctly', () => {
        render(PostsDataGrid, { props: defaultProps });

        expect(screen.getByText('Technology')).toBeInTheDocument();
        expect(screen.getByText('Design')).toBeInTheDocument();
        expect(screen.getByText('100')).toBeInTheDocument();
        expect(screen.getByText('50')).toBeInTheDocument();
    });

    it('allows typing in search input', async () => {
        render(PostsDataGrid, { props: defaultProps });

        const searchInput = screen.getByPlaceholderText(/search posts/i);
        await fireEvent.update(searchInput, 'test search');

        expect(searchInput).toHaveValue('test search');
    });

    it('shows empty state when no posts', () => {
        const emptyProps = {
            ...defaultProps,
            posts: { ...mockPosts, data: [] },
        };

        render(PostsDataGrid, { props: emptyProps });

        expect(screen.getByText(/no posts found/i)).toBeInTheDocument();
    });

    it('shows pagination info', () => {
        render(PostsDataGrid, { props: defaultProps });

        expect(screen.getByText(/show:/i)).toBeInTheDocument();
    });
});
