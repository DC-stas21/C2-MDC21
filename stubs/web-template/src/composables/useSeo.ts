import { watch } from 'vue';
import { useRoute } from 'vue-router';
import { useConfig } from './useConfig';

export function useSeo() {
    const config = useConfig();
    const route = useRoute();

    function updateMeta() {
        const page = config.pages.find((p) => p.slug === route.path);
        if (!page?.seo) return;

        document.title = page.seo.title || config.meta.title;

        setMeta('description', page.seo.description || config.meta.description);
        setMeta('robots', 'index, follow');

        // Open Graph
        setMeta('og:title', page.seo.title || config.meta.title, 'property');
        setMeta('og:description', page.seo.description || config.meta.description, 'property');
        setMeta('og:type', 'website', 'property');
        setMeta('og:url', `https://${config.meta.domain}${route.path}`, 'property');

        // Canonical
        let canonical = document.querySelector('link[rel="canonical"]') as HTMLLinkElement;
        if (!canonical) {
            canonical = document.createElement('link');
            canonical.rel = 'canonical';
            document.head.appendChild(canonical);
        }
        canonical.href = `https://${config.meta.domain}${route.path}`;

        // JSON-LD Schema
        updateSchema(page);
    }

    function setMeta(name: string, content: string, attr: string = 'name') {
        let el = document.querySelector(`meta[${attr}="${name}"]`) as HTMLMetaElement;
        if (!el) {
            el = document.createElement('meta');
            el.setAttribute(attr, name);
            document.head.appendChild(el);
        }
        el.content = content;
    }

    function updateSchema(page: any) {
        // Remove existing
        document.querySelectorAll('script[data-schema]').forEach((el) => el.remove());

        // WebSite schema
        if (page.slug === '/') {
            injectSchema({
                '@context': 'https://schema.org',
                '@type': 'WebSite',
                name: config.meta.title,
                url: `https://${config.meta.domain}`,
                description: config.meta.description,
                inLanguage: config.meta.language,
            });
        }

        // FAQ schema
        const faqSection = page.sections?.find((s: any) => s.type === 'faq');
        if (faqSection?.items?.length) {
            injectSchema({
                '@context': 'https://schema.org',
                '@type': 'FAQPage',
                mainEntity: faqSection.items.map((item: any) => ({
                    '@type': 'Question',
                    name: item.question,
                    acceptedAnswer: {
                        '@type': 'Answer',
                        text: item.answer,
                    },
                })),
            });
        }

        // SoftwareApplication schema for tools
        const toolSection = page.sections?.find((s: any) => s.type === 'tool');
        if (toolSection) {
            injectSchema({
                '@context': 'https://schema.org',
                '@type': 'SoftwareApplication',
                name: page.seo?.title || config.meta.title,
                applicationCategory: 'FinanceApplication',
                operatingSystem: 'Web',
                offers: { '@type': 'Offer', price: '0', priceCurrency: 'EUR' },
            });
        }
    }

    function injectSchema(data: object) {
        const script = document.createElement('script');
        script.type = 'application/ld+json';
        script.setAttribute('data-schema', 'true');
        script.textContent = JSON.stringify(data);
        document.head.appendChild(script);
    }

    watch(() => route.path, updateMeta, { immediate: true });
}
