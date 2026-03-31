/**
 * Generates sitemap.xml and robots.txt from site.config.json
 * Called during vite build via a custom plugin
 */
import { readFileSync, writeFileSync, mkdirSync, existsSync } from 'fs';
import { resolve } from 'path';

export function generateSeoFiles(outDir: string) {
    const configPath = resolve(process.cwd(), 'site.config.json');
    const config = JSON.parse(readFileSync(configPath, 'utf-8'));
    const domain = config.meta?.domain || 'example.com';
    const baseUrl = `https://${domain}`;

    if (!existsSync(outDir)) {
        mkdirSync(outDir, { recursive: true });
    }

    // Generate sitemap.xml
    const urls: string[] = [];

    // Pages from config
    (config.pages || []).forEach((page: any) => {
        const priority = page.slug === '/' ? '1.0' : page.type === 'tool' ? '0.9' : page.type === 'legal' ? '0.3' : '0.7';
        const freq = page.type === 'legal' ? 'yearly' : 'weekly';
        urls.push(`  <url><loc>${baseUrl}${page.slug}</loc><changefreq>${freq}</changefreq><priority>${priority}</priority></url>`);
    });

    // Blog articles
    if (config.blog?.articles?.length) {
        urls.push(`  <url><loc>${baseUrl}/blog</loc><changefreq>weekly</changefreq><priority>0.8</priority></url>`);
        config.blog.articles.forEach((article: any) => {
            urls.push(`  <url><loc>${baseUrl}/blog/${article.slug}</loc><changefreq>monthly</changefreq><priority>0.6</priority></url>`);
        });
    }

    const sitemap = `<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
${urls.join('\n')}
</urlset>`;

    writeFileSync(resolve(outDir, 'sitemap.xml'), sitemap);

    // Generate robots.txt
    const robots = `User-agent: *
Allow: /

Sitemap: ${baseUrl}/sitemap.xml`;

    writeFileSync(resolve(outDir, 'robots.txt'), robots);
}
