<script setup lang="ts">
defineProps<{
    headline: string;
    subtitle?: string;
    cta?: { text: string; link: string };
}>();
</script>

<template>
    <section class="relative overflow-hidden py-20 sm:py-28" style="background: linear-gradient(135deg, var(--site-color-primary), var(--site-color-secondary))">
        <div class="mx-auto max-w-4xl px-4 text-center sm:px-6">
            <h1 class="text-4xl font-bold tracking-tight text-white sm:text-5xl">{{ headline }}</h1>
            <p v-if="subtitle" class="mx-auto mt-4 max-w-2xl text-lg text-white/80">{{ subtitle }}</p>
            <router-link
                v-if="cta"
                :to="cta.link"
                class="mt-8 inline-flex rounded-lg px-6 py-3 text-sm font-semibold shadow-lg transition hover:opacity-90"
                style="background-color: var(--site-color-accent); color: #fff"
            >
                {{ cta.text }}
            </router-link>
        </div>
    </section>
</template>
