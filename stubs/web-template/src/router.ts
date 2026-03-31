import { createRouter, createWebHistory } from 'vue-router';
import { getConfig } from '@/config';
import DynamicPage from '@/pages/DynamicPage.vue';

const config = getConfig();

const routes = config.pages.map((page) => ({
    path: page.slug,
    component: DynamicPage,
    props: { page },
    meta: { seo: page.seo },
}));

const router = createRouter({
    history: createWebHistory(),
    routes,
    scrollBehavior: () => ({ top: 0 }),
});

// Update document title on navigation
router.afterEach((to) => {
    const seo = to.meta.seo as { title?: string } | undefined;
    if (seo?.title) {
        document.title = seo.title;
    }
});

export default router;
