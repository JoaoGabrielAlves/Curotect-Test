import '@testing-library/jest-dom';
import { config } from '@vue/test-utils';

(globalThis as any).route = vi.fn((name: string, params?: any) => {
    if (params) {
        return `/${name.replace('.', '/')}/${params}`;
    }
    return `/${name.replace('.', '/')}`;
});

Object.defineProperty(window, 'location', {
    value: {
        origin: 'http://localhost',
        pathname: '/posts',
        search: '',
        href: 'http://localhost/posts',
    },
    writable: true,
});

const mockCsrfToken = document.createElement('meta');
mockCsrfToken.name = 'csrf-token';
mockCsrfToken.content = 'mock-csrf-token';
document.head.appendChild(mockCsrfToken);

config.global.mocks = {
    route: (globalThis as any).route,
    $page: {
        props: {
            auth: { user: { id: 1 } },
            flash: {},
        },
    },
};

config.global.stubs = {
    Head: true,
    Link: {
        template: '<a><slot /></a>',
        props: ['href'],
    },
};

vi.mock('@inertiajs/vue3', () => ({
    Head: { template: '<div></div>' },
    Link: {
        template: '<a><slot /></a>',
        props: ['href'],
    },
    usePage: () => ({
        props: {
            auth: { user: { id: 1 } },
            flash: {},
        },
    }),
    router: {
        get: vi.fn(),
        post: vi.fn(),
        put: vi.fn(),
        delete: vi.fn(),
    },
}));
