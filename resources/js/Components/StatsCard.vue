<script setup lang="ts">
defineProps<{
    label: string;
    value: number | string;
    sub?: string;
    color: 'indigo' | 'amber' | 'emerald' | 'cyan' | 'violet' | 'red';
}>();
</script>

<template>
    <div class="rounded-lg border border-[#e4e4e7] bg-white px-4 py-4">
        <p class="text-[12px] font-medium text-[#71717a]">{{ label }}</p>
        <p class="mt-1 text-[22px] font-semibold tracking-tight text-[#09090b]">{{ value }}</p>
        <p v-if="sub" class="mt-0.5 text-[11px] text-[#a1a1aa]">{{ sub }}</p>
    </div>
</template>
