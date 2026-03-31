<script setup lang="ts">
import { ref, computed } from 'vue';

const props = defineProps<{
    tool_config: {
        checker_type: string;
        title: string;
        checks: Array<{
            id: string;
            label: string;
            description: string;
            category: string;
        }>;
        categories: string[];
        disclaimer?: string;
    };
}>();

const answers = ref<Record<string, boolean>>({});
const submitted = ref(false);

const score = computed(() => {
    const total = props.tool_config.checks.length;
    if (total === 0) return 0;
    const passed = Object.values(answers.value).filter(Boolean).length;
    return Math.round((passed / total) * 100);
});

const scoreColor = computed(() => {
    if (score.value >= 80) return 'var(--site-color-primary)';
    if (score.value >= 50) return '#f59e0b';
    return '#ef4444';
});

const resultsByCategory = computed(() => {
    return props.tool_config.categories.map((cat) => {
        const checks = props.tool_config.checks.filter((c) => c.category === cat);
        const passed = checks.filter((c) => answers.value[c.id]).length;
        return { category: cat, total: checks.length, passed, checks };
    });
});

function submit() {
    submitted.value = true;
}

function reset() {
    answers.value = {};
    submitted.value = false;
}
</script>

<template>
    <section class="py-12 sm:py-16">
        <div class="mx-auto max-w-2xl px-4 sm:px-6">
            <!-- Checker form -->
            <div v-if="!submitted" class="rounded-2xl shadow-lg" style="border: 1px solid var(--site-color-surface); background-color: var(--site-color-background)">
                <div class="p-6 sm:p-8">
                    <h3 class="text-lg font-bold" style="color: var(--site-color-text)">{{ tool_config.title }}</h3>
                    <p class="mt-1 text-sm" style="color: var(--site-color-text_muted)">Marca las opciones que aplican</p>

                    <div v-for="cat in tool_config.categories" :key="cat" class="mt-6">
                        <h4 class="mb-3 text-sm font-semibold uppercase tracking-wide" style="color: var(--site-color-primary)">{{ cat }}</h4>
                        <div class="space-y-2">
                            <label
                                v-for="check in tool_config.checks.filter(c => c.category === cat)"
                                :key="check.id"
                                class="flex cursor-pointer items-start gap-3 rounded-lg p-3 transition"
                                :style="{ backgroundColor: answers[check.id] ? 'var(--site-color-surface)' : 'transparent' }"
                            >
                                <input v-model="answers[check.id]" type="checkbox" class="mt-0.5 accent-[var(--site-color-primary)]" />
                                <div>
                                    <p class="text-sm font-medium" style="color: var(--site-color-text)">{{ check.label }}</p>
                                    <p class="text-xs" style="color: var(--site-color-text_muted)">{{ check.description }}</p>
                                </div>
                            </label>
                        </div>
                    </div>

                    <button
                        class="mt-6 w-full rounded-lg px-6 py-3 text-sm font-semibold text-white transition hover:opacity-90"
                        style="background-color: var(--site-color-primary)"
                        @click="submit"
                    >
                        Ver resultados
                    </button>
                </div>
            </div>

            <!-- Results -->
            <div v-else class="rounded-2xl shadow-lg" style="border: 1px solid var(--site-color-surface); background-color: var(--site-color-background)">
                <div class="p-6 text-center sm:p-8">
                    <p class="text-sm font-medium uppercase tracking-wide" style="color: var(--site-color-text_muted)">Tu puntuación</p>
                    <p class="mt-2 text-5xl font-bold" :style="{ color: scoreColor }">{{ score }}%</p>
                    <p class="mt-2 text-sm" style="color: var(--site-color-text_muted)">
                        {{ score >= 80 ? '¡Excelente! Estás bien preparado.' : score >= 50 ? 'Hay margen de mejora.' : 'Necesitas atención urgente.' }}
                    </p>
                </div>

                <div class="border-t px-6 py-6" style="border-color: var(--site-color-surface)">
                    <div v-for="result in resultsByCategory" :key="result.category" class="mb-4 last:mb-0">
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-medium" style="color: var(--site-color-text)">{{ result.category }}</span>
                            <span class="text-sm" :style="{ color: result.passed === result.total ? 'var(--site-color-primary)' : '#f59e0b' }">
                                {{ result.passed }}/{{ result.total }}
                            </span>
                        </div>
                        <div class="mt-1 h-2 overflow-hidden rounded-full" style="background-color: var(--site-color-surface)">
                            <div class="h-full rounded-full transition-all" :style="{ width: `${result.total > 0 ? (result.passed / result.total) * 100 : 0}%`, backgroundColor: 'var(--site-color-primary)' }" />
                        </div>
                    </div>
                </div>

                <div class="border-t px-6 py-4" style="border-color: var(--site-color-surface)">
                    <button class="text-sm font-medium" style="color: var(--site-color-primary)" @click="reset">Repetir evaluación</button>
                </div>

                <div v-if="tool_config.disclaimer" class="border-t px-6 py-3" style="border-color: var(--site-color-surface)">
                    <p class="text-xs" style="color: var(--site-color-text_muted)">{{ tool_config.disclaimer }}</p>
                </div>
            </div>
        </div>
    </section>
</template>
