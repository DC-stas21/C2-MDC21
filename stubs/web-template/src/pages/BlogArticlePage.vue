<script setup lang="ts">
import { computed } from 'vue';
import { useRoute } from 'vue-router';
import { useConfig } from '@/composables/useConfig';

const config = useConfig();
const route = useRoute();

const article = computed(() => {
    const slug = route.params.slug as string;
    return config.blog?.articles?.find((a) => a.slug === slug);
});
</script>

<template>
    <div v-if="article">
        <!-- Article header -->
        <section class="py-16" style="background-color: var(--site-color-surface)">
            <div class="mx-auto max-w-3xl px-4 text-center">
                <p class="text-xs font-medium uppercase tracking-wide" style="color: var(--site-color-primary)">
                    {{ article.category || config.meta.vertical }}
                </p>
                <h1 class="mt-3 text-3xl font-bold leading-tight sm:text-4xl" style="color: var(--site-color-text)">
                    {{ article.title }}
                </h1>
                <div class="mt-4 flex items-center justify-center gap-4 text-sm" style="color: var(--site-color-text_muted)">
                    <span v-if="article.author">{{ article.author }}</span>
                    <span v-if="article.date">{{ article.date }}</span>
                    <span v-if="article.reading_time">{{ article.reading_time }} min lectura</span>
                </div>
            </div>
        </section>

        <!-- Article body -->
        <article class="py-12">
            <div class="prose mx-auto max-w-3xl px-4 leading-relaxed" style="color: var(--site-color-text)" v-html="article.body" />
        </article>

        <!-- Sources -->
        <div v-if="article.sources?.length" class="border-t py-8" style="border-color: var(--site-color-surface)">
            <div class="mx-auto max-w-3xl px-4">
                <h3 class="mb-3 text-sm font-semibold" style="color: var(--site-color-text)">Fuentes</h3>
                <ul class="space-y-1">
                    <li v-for="source in article.sources" :key="source" class="text-sm" style="color: var(--site-color-text_muted)">
                        {{ source }}
                    </li>
                </ul>
            </div>
        </div>

        <!-- Disclaimer -->
        <div class="border-t py-6" style="border-color: var(--site-color-surface)">
            <div class="mx-auto max-w-3xl px-4">
                <p class="text-xs" style="color: var(--site-color-text_muted)">
                    {{ config.footer.disclaimer }}
                </p>
            </div>
        </div>

        <!-- Back to blog -->
        <div class="pb-12 text-center">
            <router-link to="/blog" class="text-sm font-medium" style="color: var(--site-color-primary)">
                ← Volver al blog
            </router-link>
        </div>
    </div>

    <!-- Not found -->
    <div v-else class="py-24 text-center">
        <p class="text-lg" style="color: var(--site-color-text_muted)">Artículo no encontrado</p>
        <router-link to="/blog" class="mt-4 inline-block text-sm font-medium" style="color: var(--site-color-primary)">
            ← Volver al blog
        </router-link>
    </div>
</template>
