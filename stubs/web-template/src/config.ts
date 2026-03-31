import siteConfig from '../site.config.json';

export interface SiteConfig {
    meta: { domain: string; vertical: string; language: string; title: string; description: string };
    design: {
        colors: Record<string, string>;
        fonts: { heading: string; body: string };
        border_radius: string;
        style: string;
    };
    navigation: Array<{ label: string; slug: string }>;
    pages: Array<{
        slug: string;
        type: string;
        seo: { title: string; description: string; keywords?: string[] };
        sections: Array<{ type: string; [key: string]: any }>;
    }>;
    footer: {
        copyright: string;
        links: Array<{ label: string; slug: string }>;
        disclaimer: string;
    };
    ads: {
        adsense_id: string;
        auto_ads: boolean;
    };
    blog: {
        articles: Array<{
            slug: string;
            title: string;
            excerpt: string;
            body: string;
            author: string;
            date: string;
            reading_time: number;
            category: string;
            sources: string[];
        }>;
    };
    tools: {
        lead_form: { enabled: boolean; endpoint: string; fields: string[]; asset_domain: string };
    };
    build: { generated_at: string; generator: string; config_version: number };
}

export function getConfig(): SiteConfig {
    return siteConfig as unknown as SiteConfig;
}
