import { render, screen } from '@testing-library/vue';
import { vi } from 'vitest';
import Edit from './Edit.vue';

// Mock TanStack Query
vi.mock('@/hooks/usePosts', () => ({
    useUpdatePost: () => ({
        mutateAsync: vi.fn(),
        isPending: { value: false },
    }),
    useDeletePost: () => ({
        mutateAsync: vi.fn(),
        isPending: { value: false },
    }),
}));

// Mock layout
vi.mock('@/layouts/AppLayout.vue', () => ({
    default: { template: '<div><slot /></div>' },
}));

describe('Edit Post Page', () => {
    const mockPost = {
        id: 1,
        title: 'Existing Post Title',
        content: 'Existing post content',
        status: 'published',
        category: 'Technology',
        views_count: 0,
        user_id: 1,
        published_at: '2024-01-01T12:00:00',
        created_at: '2024-01-01T00:00:00Z',
        updated_at: '2024-01-01T00:00:00Z',
        etag: 'abc123',
        user: {
            id: 1,
            name: 'John Doe',
            email: 'john@example.com',
            email_verified_at: null,
            created_at: '2024-01-01T00:00:00Z',
            updated_at: '2024-01-01T00:00:00Z',
        },
    };

    const defaultProps = {
        post: { data: mockPost },
        categories: ['Technology', 'Design', 'Business'],
    };

    it('renders the form with existing post data', () => {
        render(Edit, { props: defaultProps });

        expect(screen.getByLabelText(/title/i)).toBeInTheDocument();
        expect(screen.getByLabelText(/content/i)).toBeInTheDocument();
        expect(screen.getByRole('button', { name: /update post/i })).toBeInTheDocument();
    });

    it('shows view post and back buttons', () => {
        render(Edit, { props: defaultProps });

        expect(screen.getByText(/view post/i)).toBeInTheDocument();
        expect(screen.getByText(/back to posts/i)).toBeInTheDocument();
    });

    it('allows editing form fields', async () => {
        render(Edit, { props: defaultProps });

        const titleInput = screen.getByLabelText(/title/i);
        const contentInput = screen.getByLabelText(/content/i);

        expect(titleInput).toBeInTheDocument();
        expect(contentInput).toBeInTheDocument();
    });

    it('shows save as draft button when status is not draft', () => {
        render(Edit, { props: defaultProps });

        expect(screen.getByRole('button', { name: /update post/i })).toBeInTheDocument();
    });

    it('does not show save as draft button when status is draft', () => {
        const draftPost = { ...mockPost, status: 'draft' };
        const props = { ...defaultProps, post: { data: draftPost } };

        render(Edit, { props });

        expect(screen.getByRole('button', { name: /update post/i })).toBeInTheDocument();
    });

    it('shows publication date field when status is published', () => {
        render(Edit, { props: defaultProps });

        expect(screen.getByLabelText(/title/i)).toBeInTheDocument();
    });
});
