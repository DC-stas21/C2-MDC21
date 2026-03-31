import { inject, provide } from 'vue';
import type { SiteConfig } from '@/config';

const CONFIG_KEY = Symbol('siteConfig');

export function provideConfig(config: SiteConfig) {
    provide(CONFIG_KEY, config);
}

export function useConfig(): SiteConfig {
    const config = inject<SiteConfig>(CONFIG_KEY);
    if (!config) throw new Error('Site config not provided');
    return config;
}
