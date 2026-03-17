import { createI18n } from 'vue-i18n';
import es from '../lang/es/app.json';
import en from '../lang/en/app.json';

export const i18n = createI18n({
    legacy: false,
    locale: document.documentElement.lang || 'es',
    fallbackLocale: 'en',
    messages: { es, en },
});
