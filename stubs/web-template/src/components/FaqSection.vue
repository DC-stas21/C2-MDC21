<script setup lang="ts">
import { ref } from 'vue';

defineProps<{
    title?: string;
    items: Array<{ question: string; answer: string }>;
}>();

const openIndex = ref<number | null>(null);

function toggle(i: number) {
    openIndex.value = openIndex.value === i ? null : i;
}
</script>

<template>
    <section class="py-12 sm:py-16">
        <div class="mx-auto max-w-3xl px-4 sm:px-6">
            <h2 v-if="title" class="mb-8 text-center text-2xl font-bold" style="color: var(--site-color-text)">{{ title }}</h2>
            <div class="divide-y" style="border-color: var(--site-color-surface)">
                <div v-for="(item, i) in items" :key="i" class="py-4">
                    <button class="flex w-full items-center justify-between text-left" @click="toggle(i)">
                        <span class="text-[15px] font-medium" style="color: var(--site-color-text)">{{ item.question }}</span>
                        <svg :class="['h-5 w-5 shrink-0 transition-transform', openIndex === i ? 'rotate-180' : '']" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" style="color: var(--site-color-text_muted)">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                        </svg>
                    </button>
                    <div v-if="openIndex === i" class="mt-3 text-sm leading-relaxed" style="color: var(--site-color-text_muted)">
                        {{ item.answer }}
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>
