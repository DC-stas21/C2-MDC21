<script setup lang="ts">
defineProps<{
    title?: string;
    body: string;
}>();
</script>

<template>
    <section class="py-12 sm:py-16">
        <div class="mx-auto max-w-4xl px-4 sm:px-6">
            <h2 v-if="title" class="mb-6 text-2xl font-bold" style="color: var(--site-color-text)">{{ title }}</h2>
            <div class="prose max-w-none leading-relaxed" style="color: var(--site-color-text_muted)" v-html="body" />
        </div>
    </section>
</template>
