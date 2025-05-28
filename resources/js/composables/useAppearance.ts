import { onMounted, ref } from 'vue';

type Appearance = 'light' | 'dark' | 'system';

export function updateTheme(value: Appearance) {
    if (typeof window === 'undefined') return;

    if (value === 'system') {
        const isDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
        document.documentElement.classList.toggle('dark', isDark);
    } else {
        document.documentElement.classList.toggle('dark', value === 'dark');
    }
}

const setCookie = (name: string, value: string, days = 365) => {
    if (typeof document === 'undefined') return;

    const maxAge = days * 24 * 60 * 60;
    document.cookie = `${name}=${value};path=/;max-age=${maxAge};SameSite=Lax`;
};

export function initializeTheme() {
    if (typeof window === 'undefined') return;

    const saved = localStorage.getItem('appearance') as Appearance | null;
    updateTheme(saved || 'system');

    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
        const current = localStorage.getItem('appearance') as Appearance | null;
        updateTheme(current || 'system');
    });
}

export function useAppearance() {
    const appearance = ref<Appearance>('system');

    onMounted(() => {
        const saved = localStorage.getItem('appearance') as Appearance | null;
        if (saved) {
            appearance.value = saved;
        }
    });

    function updateAppearance(value: Appearance) {
        appearance.value = value;
        localStorage.setItem('appearance', value);
        setCookie('appearance', value);
        updateTheme(value);
    }

    return {
        appearance,
        updateAppearance,
    };
}
