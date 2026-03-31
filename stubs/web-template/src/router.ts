import { createRouter, createWebHistory } from 'vue-router';
import { getConfig } from '@/config';
import DynamicPage from '@/pages/DynamicPage.vue';
import BlogListPage from '@/pages/BlogListPage.vue';
import BlogArticlePage from '@/pages/BlogArticlePage.vue';

const config = getConfig();

// Dynamic pages from config
const pageRoutes = config.pages.map((page) => ({
    path: page.slug,
    component: DynamicPage,
    props: { page },
    meta: { seo: page.seo },
}));

// Blog routes
const blogRoutes = config.blog?.articles?.length
    ? [
        { path: '/blog', component: BlogListPage, meta: { seo: { title: `Blog | ${config.meta.domain}`, description: `Artículos sobre ${config.meta.vertical}` } } },
        { path: '/blog/:slug', component: BlogArticlePage, meta: { seo: { title: config.meta.title } } },
    ]
    : [];

const router = createRouter({
    history: createWebHistory(),
    routes: [...pageRoutes, ...blogRoutes],
    scrollBehavior: () => ({ top: 0 }),
});

export default router;
