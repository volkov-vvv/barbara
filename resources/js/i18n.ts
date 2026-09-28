import { createI18n } from 'vue-i18n';
import { router } from '@inertiajs/vue3';
import en from '@/locales/en.json';
import ru from '@/locales/ru.json';

export type AppLocale = 'en' | 'ru';

export const availableLocales: AppLocale[] = ['en', 'ru'];

export const i18n = createI18n({
    legacy: false,
    locale: 'en',
    fallbackLocale: 'en',
    messages: {
        en,
        ru,
    },
});

function resolveLocale(value: unknown): AppLocale {
    return value === 'ru' ? 'ru' : 'en';
}

export function setAppLocale(locale: unknown): void {
    const resolved = resolveLocale(locale);
    i18n.global.locale.value = resolved;

    if (typeof document !== 'undefined') {
        document.documentElement.lang = resolved;
    }
}

export function syncLocaleFromPageProps(props: { locale?: unknown }): void {
    setAppLocale(props.locale);
}

export function initializeI18n(initialLocale?: unknown): void {
    setAppLocale(initialLocale);

    router.on('navigate', (event) => {
        syncLocaleFromPageProps(event.detail.page.props);
    });
}
