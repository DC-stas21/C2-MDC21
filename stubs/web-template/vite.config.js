import { defineConfig } from 'vite';
import vue from '@vitejs/plugin-vue';
import tailwindcss from '@tailwindcss/vite';
import { resolve } from 'path';

// Custom plugin to generate sitemap.xml and robots.txt after build
function seoPlugin() {
    return {
        name: 'generate-seo-files',
        closeBundle: async () => {
            const { generateSeoFiles } = await import('./src/generate-seo-files.ts');
            generateSeoFiles(resolve(__dirname, 'dist'));
        },
    };
}

export default defineConfig({
    plugins: [vue(), tailwindcss(), seoPlugin()],
    resolve: {
        alias: { '@': resolve(__dirname, 'src') },
    },
});
