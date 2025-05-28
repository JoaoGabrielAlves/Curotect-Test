import type { CreatePostData, Post, UpdatePostData } from '@/types';

// Configure fetch defaults with CSRF token
const getCsrfToken = (): string => {
    const token = document.head.querySelector('meta[name="csrf-token"]') as HTMLMetaElement;
    return token?.content || '';
};

// Base fetch configuration
const apiRequest = async (url: string, options: RequestInit = {}): Promise<Response> => {
    const defaultHeaders: HeadersInit = {
        Accept: 'application/json',
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
    };

    const csrfToken = getCsrfToken();
    if (csrfToken) {
        (defaultHeaders as Record<string, string>)['X-CSRF-TOKEN'] = csrfToken;
    }

    const config: RequestInit = {
        credentials: 'same-origin',
        ...options,
        headers: {
            ...defaultHeaders,
            ...options.headers,
        },
    };

    return fetch(url, config);
};

export class ApiError extends Error {
    constructor(
        message: string,
        public status: number,
        public errors?: Record<string, string[]>,
    ) {
        super(message);
        this.name = 'ApiError';
    }
}

export const postsApi = {
    // Get all posts
    async getPosts(): Promise<Post[]> {
        const response = await apiRequest('/api/posts');

        if (!response.ok) {
            throw new ApiError(`Failed to fetch posts: ${response.statusText}`, response.status);
        }

        const data = await response.json();
        return data.data;
    },

    // Get single post
    async getPost(id: number): Promise<Post> {
        const response = await apiRequest(`/api/posts/${id}`);

        if (!response.ok) {
            throw new ApiError(`Failed to fetch post: ${response.statusText}`, response.status);
        }

        const data = await response.json();
        return data.data;
    },

    // Create post
    async createPost(data: CreatePostData): Promise<Post> {
        const response = await apiRequest('/api/posts', {
            method: 'POST',
            body: JSON.stringify(data),
        });

        if (response.status === 422) {
            const errorData = await response.json();
            throw new ApiError('Validation failed', 422, errorData.errors);
        }

        if (!response.ok) {
            throw new ApiError(`Failed to create post: ${response.statusText}`, response.status);
        }

        const result = await response.json();
        return result.data;
    },

    // Update post
    async updatePost(id: number, data: UpdatePostData): Promise<Post> {
        const response = await apiRequest(`/api/posts/${id}`, {
            method: 'PUT',
            headers: {
                'If-Match': data.etag,
            },
            body: JSON.stringify(data),
        });

        if (response.status === 422) {
            const errorData = await response.json();
            throw new ApiError('Validation failed', 422, errorData.errors);
        }

        if (response.status === 409) {
            throw new ApiError('Post has been modified by another user', 409, {
                etag: ['Post has been modified by another user. Please refresh and try again.'],
            });
        }

        if (!response.ok) {
            throw new ApiError(`Failed to update post: ${response.statusText}`, response.status);
        }

        const result = await response.json();
        return result.data;
    },

    // Delete post
    async deletePost(id: number): Promise<void> {
        const response = await apiRequest(`/api/posts/${id}`, {
            method: 'DELETE',
        });

        if (!response.ok) {
            throw new ApiError(`Failed to delete post: ${response.statusText}`, response.status);
        }
    },
};
