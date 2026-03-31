import { inject, provide, ref } from 'vue';
import type { SiteConfig } from '@/config';
import { getConfig } from '@/config';

const CONFIG_KEY = Symbol('siteConfig');

// Singleton — loaded once at app startup
const configInstance = ref<SiteConfig>(getConfig());

export function provideConfig() {
    provide(CONFIG_KEY, configInstance.value);
}

export function useConfig(): SiteConfig {
    // Try inject first (child components), fallback to singleton (App.vue)
    const injected = inject<SiteConfig>(CONFIG_KEY, undefined);
    return injected ?? configInstance.value;
}
