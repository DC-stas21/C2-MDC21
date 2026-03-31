<script setup lang="ts">
import { useConfig } from '@/composables/useConfig';
const config = useConfig();
</script>

<template>
    <footer class="border-t py-8" style="border-color: var(--site-color-surface); background-color: var(--site-color-surface)">
        <div class="mx-auto max-w-6xl px-4 sm:px-6">
            <div class="flex flex-col items-center justify-between gap-4 sm:flex-row">
                <p class="text-sm" style="color: var(--site-color-text_muted)">{{ config.footer.copyright }}</p>
                <nav class="flex gap-4">
                    <router-link
                        v-for="link in config.footer.links"
                        :key="link.slug"
                        :to="link.slug"
                        class="text-sm transition-colors hover:opacity-80"
                        style="color: var(--site-color-text_muted)"
                    >
                        {{ link.label }}
                    </router-link>
                </nav>
            </div>
            <p v-if="config.footer.disclaimer" class="mt-4 text-center text-xs" style="color: var(--site-color-text_muted)">
                {{ config.footer.disclaimer }}
            </p>
        </div>
    </footer>
</template>
