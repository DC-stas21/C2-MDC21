import { onMounted } from 'vue';
import { useConfig } from './useConfig';

export function useTheme() {
    const config = useConfig();

    onMounted(() => {
        const root = document.documentElement;
        const { colors, fonts, border_radius } = config.design;

        // Set CSS custom properties for colors
        Object.entries(colors).forEach(([key, value]) => {
            root.style.setProperty(`--site-color-${key}`, value);
        });

        // Set fonts
        root.style.setProperty('--site-font-heading', fonts.heading);
        root.style.setProperty('--site-font-body', fonts.body);
        root.style.setProperty('--site-radius', border_radius);

        // Update page title and meta
        document.title = config.meta.title;
        const metaDesc = document.querySelector('meta[name="description"]');
        if (metaDesc) {
            metaDesc.setAttribute('content', config.meta.description);
        } else {
            const meta = document.createElement('meta');
            meta.name = 'description';
            meta.content = config.meta.description;
            document.head.appendChild(meta);
        }
    });
}
