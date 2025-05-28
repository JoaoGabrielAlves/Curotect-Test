import { render, screen } from '@testing-library/vue';
import { vi } from 'vitest';
import Show from './Show.vue';

// Mock layout and components
vi.mock('@/layouts/AppLayout.vue', () => ({
    default: { template: '<div><slot /></div>' },
}));

vi.mock('@/components/CommentForm.vue', () => ({
    default: { template: '<div>Comment Form</div>' },
}));

vi.mock('@/components/StatusBadge.vue', () => ({
    default: { template: '<span>Status Badge</span>' },
}));

describe('Show Post Page', () => {
    const mockPost = {
        id: 1,
        title: 'Test Post Title',
        content: 'This is test post content',
        status: 'published',
        category: 'Technology',
        views_count: 42,
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
        comments: [
            {
                id: 1,
                content: 'Great post!',
                status: 'published',
                post_id: 1,
                user_id: 2,
                parent_id: null,
                created_at: '2024-01-01T01:00:00Z',
                updated_at: '2024-01-01T01:00:00Z',
                user: {
                    id: 2,
                    name: 'Jane Smith',
                    email: 'jane@example.com',
                    email_verified_at: null,
                    created_at: '2024-01-01T00:00:00Z',
                    updated_at: '2024-01-01T00:00:00Z',
                },
                replies: [],
            },
        ],
    };

    it('displays post title and content', () => {
        render(Show, { props: { post: { data: mockPost } } });

        expect(screen.getAllByText('Test Post Title')).toHaveLength(2);
        expect(screen.getByText('This is test post content')).toBeInTheDocument();
    });

    it('shows post metadata', () => {
        render(Show, { props: { post: { data: mockPost } } });

        expect(screen.getByText('John Doe')).toBeInTheDocument();
        expect(screen.getByText('42 views')).toBeInTheDocument();
        expect(screen.getByText('Technology')).toBeInTheDocument();
    });

    it('displays comments section', () => {
        render(Show, { props: { post: { data: mockPost } } });

        expect(screen.getByText(/comments \(1\)/i)).toBeInTheDocument();
        expect(screen.getByText('Great post!')).toBeInTheDocument();
        expect(screen.getByText('Jane Smith')).toBeInTheDocument();
    });

    it('shows comment form for authenticated users', () => {
        render(Show, { props: { post: { data: mockPost } } });

        expect(screen.getByText('Comment Form')).toBeInTheDocument();
    });

    it('shows edit button for post owner', () => {
        render(Show, { props: { post: { data: mockPost } } });

        expect(screen.getByText(/edit post/i)).toBeInTheDocument();
    });

    it('displays empty state when no comments', () => {
        const postWithoutComments = { ...mockPost, comments: [] };
        render(Show, { props: { post: { data: postWithoutComments } } });

        expect(screen.getByText(/no comments yet/i)).toBeInTheDocument();
        expect(screen.getByText(/be the first to comment/i)).toBeInTheDocument();
    });
});
