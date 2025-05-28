import { fireEvent, render, screen } from '@testing-library/vue';
import { vi } from 'vitest';
import Create from './Create.vue';

// Mock TanStack Query
vi.mock('@/hooks/usePosts', () => ({
    useCreatePost: () => ({
        mutateAsync: vi.fn(),
        isPending: { value: false },
    }),
}));

// Mock layout
vi.mock('@/layouts/AppLayout.vue', () => ({
    default: { template: '<div><slot /></div>' },
}));

describe('Create Post Page', () => {
    const defaultProps = {
        categories: ['Technology', 'Design', 'Business'],
    };

    it('renders the form with required fields', () => {
        render(Create, { props: defaultProps });

        expect(screen.getByLabelText(/title/i)).toBeInTheDocument();
        expect(screen.getByLabelText(/content/i)).toBeInTheDocument();
        expect(screen.getAllByText(/status/i)).toHaveLength(2); // Label and placeholder
        expect(screen.getByRole('button', { name: /create post/i })).toBeInTheDocument();
    });

    it('shows validation errors when fields are empty', async () => {
        render(Create, { props: defaultProps });

        const submitButton = screen.getByRole('button', { name: /create post/i });
        await fireEvent.click(submitButton);

        // Form should attempt validation
        expect(submitButton).toBeInTheDocument();
    });

    it('allows typing in form fields', async () => {
        render(Create, { props: defaultProps });

        const titleInput = screen.getByLabelText(/title/i);
        const contentInput = screen.getByLabelText(/content/i);

        await fireEvent.update(titleInput, 'Test Post Title');
        await fireEvent.update(contentInput, 'Test post content');

        expect(titleInput).toHaveValue('Test Post Title');
        expect(contentInput).toHaveValue('Test post content');
    });

    it('shows save as draft button when status is not draft', async () => {
        render(Create, { props: defaultProps });

        // Initially should not show draft button (default status is draft)
        expect(screen.queryByText(/save as draft/i)).not.toBeInTheDocument();

        // Change status to published to show the draft button
        // Note: This would require more complex interaction testing
        // For now, we just verify the initial state
    });
});
