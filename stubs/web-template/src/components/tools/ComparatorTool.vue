<script setup lang="ts">
import { ref, computed } from 'vue';

const props = defineProps<{
    tool_config: {
        comparator_type: string;
        columns: Array<{ key: string; label: string }>;
        items: Array<Record<string, string | number>>;
        highlight_best: string;
        disclaimer?: string;
    };
}>();

const sortBy = ref(props.tool_config.highlight_best || props.tool_config.columns[0]?.key || '');
const sortDir = ref<'asc' | 'desc'>('asc');

const sorted = computed(() => {
    const items = [...props.tool_config.items];
    return items.sort((a, b) => {
        const va = a[sortBy.value] ?? 0;
        const vb = b[sortBy.value] ?? 0;
        if (typeof va === 'number' && typeof vb === 'number') {
            return sortDir.value === 'asc' ? va - vb : vb - va;
        }
        return sortDir.value === 'asc'
            ? String(va).localeCompare(String(vb))
            : String(vb).localeCompare(String(va));
    });
});

function toggleSort(key: string) {
    if (sortBy.value === key) {
        sortDir.value = sortDir.value === 'asc' ? 'desc' : 'asc';
    } else {
        sortBy.value = key;
        sortDir.value = 'asc';
    }
}
</script>

<template>
    <section class="py-12 sm:py-16">
        <div class="mx-auto max-w-4xl px-4 sm:px-6">
            <div class="overflow-hidden rounded-2xl shadow-lg" style="border: 1px solid var(--site-color-surface)">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr style="background-color: var(--site-color-primary); color: #fff">
                                <th
                                    v-for="col in tool_config.columns"
                                    :key="col.key"
                                    class="cursor-pointer px-4 py-3 text-left font-semibold transition hover:opacity-80"
                                    @click="toggleSort(col.key)"
                                >
                                    {{ col.label }}
                                    <span v-if="sortBy === col.key" class="ml-1">{{ sortDir === 'asc' ? '↑' : '↓' }}</span>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="(item, i) in sorted"
                                :key="i"
                                class="border-b transition"
                                :style="{ borderColor: 'var(--site-color-surface)', backgroundColor: i % 2 === 0 ? 'var(--site-color-background)' : 'var(--site-color-surface)' }"
                            >
                                <td
                                    v-for="col in tool_config.columns"
                                    :key="col.key"
                                    class="px-4 py-3"
                                    :style="{ color: 'var(--site-color-text)' }"
                                >
                                    {{ item[col.key] }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div v-if="tool_config.disclaimer" class="border-t px-4 py-3" style="border-color: var(--site-color-surface)">
                    <p class="text-xs" style="color: var(--site-color-text_muted)">{{ tool_config.disclaimer }}</p>
                </div>
            </div>
        </div>
    </section>
</template>
