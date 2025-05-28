import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

export function useActiveRoute() {
    const page = usePage();

    const isActiveRoute = computed(() => (href: string) => {
        try {
            const currentPath = new URL(page.url, window.location.origin).pathname;
            const itemPath = new URL(href, window.location.origin).pathname;
            return currentPath === itemPath;
        } catch {
            return page.url === href;
        }
    });

    const currentPath = computed(() => {
        try {
            return new URL(page.url, window.location.origin).pathname;
        } catch {
            return page.url;
        }
    });

    return {
        isActiveRoute,
        currentPath,
    };
}
