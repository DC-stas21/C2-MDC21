<script setup lang="ts">
import { useConfig } from '@/composables/useConfig';

const config = useConfig();

const articles = config.blog?.articles ?? [];
</script>

<template>
    <div>
        <!-- Hero -->
        <section class="py-16 text-center" style="background-color: var(--site-color-surface)">
            <div class="mx-auto max-w-4xl px-4">
                <h1 class="text-3xl font-bold" style="color: var(--site-color-text)">Blog</h1>
                <p class="mt-2 text-sm" style="color: var(--site-color-text_muted)">
                    Artículos, guías y novedades sobre {{ config.meta.vertical }}
                </p>
            </div>
        </section>

        <!-- Articles grid -->
        <section class="py-12">
            <div class="mx-auto max-w-6xl px-4 sm:px-6">
                <div v-if="articles.length === 0" class="py-16 text-center text-sm" style="color: var(--site-color-text_muted)">
                    Próximamente publicaremos artículos sobre {{ config.meta.vertical }}.
                </div>
                <div v-else class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
                    <router-link
                        v-for="article in articles"
                        :key="article.slug"
                        :to="`/blog/${article.slug}`"
                        class="group rounded-xl border p-5 transition hover:shadow-md"
                        style="border-color: var(--site-color-surface); background-color: var(--site-color-background)"
                    >
                        <p class="text-xs font-medium uppercase tracking-wide" style="color: var(--site-color-primary)">
                            {{ article.category || config.meta.vertical }}
                        </p>
                        <h2 class="mt-2 text-lg font-semibold group-hover:underline" style="color: var(--site-color-text)">
                            {{ article.title }}
                        </h2>
                        <p class="mt-2 text-sm leading-relaxed" style="color: var(--site-color-text_muted)">
                            {{ article.excerpt }}
                        </p>
                        <div class="mt-4 flex items-center gap-3 text-xs" style="color: var(--site-color-text_muted)">
                            <span v-if="article.author">{{ article.author }}</span>
                            <span v-if="article.date">{{ article.date }}</span>
                        </div>
                    </router-link>
                </div>
            </div>
        </section>
    </div>
</template>
