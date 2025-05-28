import { fireEvent, render, screen } from '@testing-library/vue';
import { vi } from 'vitest';
import CommentForm from './CommentForm.vue';

// Mock TanStack Query
vi.mock('@/hooks/usePosts', () => ({
    useCreateComment: () => ({
        mutateAsync: vi.fn(),
        isPending: { value: false },
    }),
}));

describe('CommentForm', () => {
    const defaultProps = {
        postId: 1,
    };

    it('renders the comment form', () => {
        render(CommentForm, { props: defaultProps });

        expect(screen.getByPlaceholderText(/write your comment here/i)).toBeInTheDocument();
        expect(screen.getByRole('button', { name: /post comment/i })).toBeInTheDocument();
    });

    it('shows character count', () => {
        render(CommentForm, { props: defaultProps });

        expect(screen.getByText(/0\/1000/)).toBeInTheDocument();
    });

    it('updates character count when typing', async () => {
        render(CommentForm, { props: defaultProps });

        const textarea = screen.getByPlaceholderText(/write your comment here/i);
        await fireEvent.update(textarea, 'Test comment');

        expect(screen.getByText(/12\/1000/)).toBeInTheDocument();
    });

    it('shows validation error for empty comment', async () => {
        render(CommentForm, { props: defaultProps });

        const submitButton = screen.getByRole('button', { name: /post comment/i });
        await fireEvent.click(submitButton);

        expect(screen.getByText(/comment content is required/i)).toBeInTheDocument();
    });

    it('allows form submission with valid comment', async () => {
        render(CommentForm, { props: defaultProps });

        const textarea = screen.getByPlaceholderText(/write your comment here/i);
        const submitButton = screen.getByRole('button', { name: /post comment/i });

        await fireEvent.update(textarea, 'This is a test comment');
        await fireEvent.click(submitButton);

        expect(textarea).toHaveValue('This is a test comment');
    });
});
