<script setup lang="ts">
defineProps<{
    title: string;
    subtitle?: string;
    button?: { text: string; link: string };
}>();
</script>

<template>
    <section class="py-12 sm:py-16" style="background-color: var(--site-color-surface)">
        <div class="mx-auto max-w-3xl px-4 text-center sm:px-6">
            <h2 class="text-2xl font-bold" style="color: var(--site-color-text)">{{ title }}</h2>
            <p v-if="subtitle" class="mt-3 text-sm" style="color: var(--site-color-text_muted)">{{ subtitle }}</p>
            <router-link
                v-if="button"
                :to="button.link"
                class="mt-6 inline-flex rounded-lg px-6 py-3 text-sm font-semibold text-white transition hover:opacity-90"
                style="background-color: var(--site-color-primary)"
            >
                {{ button.text }}
            </router-link>
        </div>
    </section>
</template>
