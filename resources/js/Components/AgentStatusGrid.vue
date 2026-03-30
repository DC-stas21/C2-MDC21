<script setup lang="ts">
import { ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

defineProps<{
    agents: Array<{
        type: string;
        is_running: boolean;
        last_status: string | null;
        last_run_at: string | null;
        last_error: string | null;
        today_runs: number;
        today_failed: number;
    }>;
}>();

const expanded = ref<string | null>(null);

function toggle(type: string) {
    expanded.value = expanded.value === type ? null : type;
}

function timeAgo(dateStr: string | null): string {
    if (!dateStr) return 'nunca';
    const diff = Date.now() - new Date(dateStr).getTime();
    const mins = Math.floor(diff / 60000);
    if (mins < 1) return 'ahora';
    if (mins < 60) return `hace ${mins}m`;
    const hours = Math.floor(mins / 60);
    if (hours < 24) return `hace ${hours}h`;
    return `hace ${Math.floor(hours / 24)}d`;
}

const layerMap: Record<string, string> = {
    orchestrator: 'Capa 0',
    policy_brand: 'Capa 0',
    seo_content: 'Capa 1',
    distribution: 'Capa 1',
    engagement_retention: 'Capa 1',
    monetization_leads: 'Capa 1',
    build_release: 'Capa 2',
    infra_reliability: 'Capa 2',
    qa_experimentation: 'Capa 2',
};

const descMap: Record<string, string> = {
    orchestrator: 'Claude Sonnet · ciclo 12h · Score Compuesto + clasificación N1/N2/N3',
    policy_brand: 'Claude Haiku · filtro transversal · aprueba/rechaza contenido',
    seo_content: 'GPT-4o + GPT-4o-mini · research SERP + artículos E-E-A-T',
    distribution: 'Claude + GPT-4o · prepara contenido para publicación humana',
    engagement_retention: 'GPT-4o-mini · newsletters, FAQs, drip, PDFs, A/B tests',
    monetization_leads: 'Claude + GPT-4o · scoring leads + propuestas CPL',
    build_release: 'Script · GitHub Actions + Forge · deploy + rollback',
    infra_reliability: 'Script · cada hora · monitoreo infra + alertas',
    qa_experimentation: 'Playwright + Lighthouse + Pest · QA + A/B tests',
};
</script>

<template>
    <div class="grid grid-cols-1 gap-px overflow-hidden rounded-lg border border-[#e4e4e7] bg-[#e4e4e7] sm:grid-cols-3">
        <div
            v-for="agent in agents"
            :key="agent.type"
            class="relative cursor-pointer bg-white p-3.5 transition hover:bg-[#fafafa]"
            @click="toggle(agent.type)"
        >
            <!-- Status indicator -->
            <div class="flex items-start justify-between">
                <div class="min-w-0">
                    <div class="flex items-center gap-2">
                        <span
                            :class="[
                                'h-2 w-2 rounded-full',
                                agent.is_running ? 'animate-pulse bg-indigo-500' :
                                agent.last_status === 'completed' ? 'bg-emerald-500' :
                                agent.last_status === 'failed' ? 'bg-red-500' : 'bg-[#d4d4d8]',
                            ]"
                        />
                        <span class="text-[13px] font-medium text-[#09090b]">{{ t(`agents.${agent.type}`) }}</span>
                    </div>
                    <p class="mt-0.5 text-[11px] text-[#a1a1aa]">{{ layerMap[agent.type] }}</p>
                </div>
                <div class="text-right text-[11px]">
                    <p v-if="agent.is_running" class="font-medium text-indigo-600">Ejecutando</p>
                    <p v-else class="text-[#a1a1aa]">{{ timeAgo(agent.last_run_at) }}</p>
                    <p v-if="agent.today_runs > 0" class="text-[#a1a1aa]">
                        {{ agent.today_runs }}x hoy
                        <span v-if="agent.today_failed > 0" class="text-red-500">· {{ agent.today_failed }} err</span>
                    </p>
                </div>
            </div>

            <!-- Expanded details -->
            <div v-if="expanded === agent.type" class="mt-3 border-t border-[#f4f4f5] pt-3">
                <p class="text-[12px] leading-relaxed text-[#71717a]">{{ descMap[agent.type] }}</p>
                <p v-if="agent.last_error" class="mt-2 rounded bg-red-50 px-2 py-1 text-[11px] text-red-600">
                    {{ agent.last_error }}
                </p>
                <Link
                    :href="`/agent-runs?agent_type=${agent.type}`"
                    class="mt-2 inline-block text-[12px] font-medium text-[#71717a] underline decoration-[#e4e4e7] underline-offset-2 hover:text-[#09090b]"
                >
                    Ver historial
                </Link>
            </div>
        </div>
    </div>
</template>
