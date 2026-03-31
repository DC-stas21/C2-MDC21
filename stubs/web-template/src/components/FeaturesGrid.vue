<script setup lang="ts">
defineProps<{
    title?: string;
    items: Array<{ icon?: string; title: string; description: string }>;
}>();
</script>

<template>
    <section class="py-12 sm:py-16" style="background-color: var(--site-color-surface)">
        <div class="mx-auto max-w-6xl px-4 sm:px-6">
            <h2 v-if="title" class="mb-10 text-center text-2xl font-bold" style="color: var(--site-color-text)">{{ title }}</h2>
            <div class="grid grid-cols-1 gap-8 sm:grid-cols-2 lg:grid-cols-3">
                <div v-for="(item, i) in items" :key="i" class="rounded-xl p-6" style="background-color: var(--site-color-background)">
                    <div v-if="item.icon" class="mb-3 flex h-10 w-10 items-center justify-center rounded-lg text-lg" style="background-color: var(--site-color-primary); color: #fff">
                        {{ item.icon === 'calculator' ? '🧮' : item.icon === 'shield' ? '🛡️' : item.icon === 'chart' ? '📊' : item.icon === 'clock' ? '⏱️' : item.icon === 'check' ? '✓' : '•' }}
                    </div>
                    <h3 class="text-lg font-semibold" style="color: var(--site-color-text)">{{ item.title }}</h3>
                    <p class="mt-2 text-sm leading-relaxed" style="color: var(--site-color-text_muted)">{{ item.description }}</p>
                </div>
            </div>
        </div>
    </section>
</template>
