<script setup lang="ts">
import { ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import AppLayout from '@/Layouts/AppLayout.vue';
import type { Approval } from '@/types';
import AgentActivityChart from '@/Components/Charts/AgentActivityChart.vue';
import ScoreGaugeChart from '@/Components/Charts/ScoreGaugeChart.vue';
import AgentStatusGrid from '@/Components/AgentStatusGrid.vue';
import ActivityTimeline from '@/Components/ActivityTimeline.vue';
import ApprovalRow from '@/Components/ApprovalRow.vue';

const { t } = useI18n();

const props = defineProps<{
    stats: {
        agents_active: number;
        agents_completed_today: number;
        agents_failed_today: number;
        agents_total_week: number;
        approvals_pending: number;
        approvals_resolved_today: number;
        assets_total: number;
        score_avg: number | null;
        success_rate: number | null;
    };
    agentStatuses: Array<{
        type: string;
        is_running: boolean;
        last_status: string | null;
        last_run_at: string | null;
        last_error: string | null;
        today_runs: number;
        today_failed: number;
    }>;
    pendingApprovals: Approval[];
    agentActivity: Array<{ date: string; agent_type: string; status: string; count: number }>;
    assets: Array<{ id: string; domain: string; vertical: string; cpl: string; score: number | null; classification: string | null }>;
    timeline: Array<{ type: 'agent_run' | 'approval'; agent_type?: string; status: string; error?: string | null; action?: string; level?: string; at: string }>;
}>();

const sections = ref({
    agents: true,
    approvals: true,
    activity: true,
});

function classifyLabel(score: number): string {
    if (score >= 80) return 'Excelente';
    if (score >= 60) return 'Bueno';
    if (score >= 40) return 'Regular';
    return 'Bajo';
}

function classColor(c: string | null): string {
    const map: Record<string, string> = {
        excellent: 'text-emerald-600', good: 'text-blue-600',
        average: 'text-amber-600', poor: 'text-orange-600', critical: 'text-red-600',
    };
    return map[c ?? ''] ?? 'text-[#a1a1aa]';
}
</script>

<template>
    <AppLayout>
        <template #header>
            <h1 class="text-[14px] font-medium text-[#09090b]">{{ t('dashboard.title') }}</h1>
        </template>

        <!-- ===== TOP METRICS ===== -->
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5">
            <div class="rounded-lg border border-[#e4e4e7] bg-white px-4 py-3">
                <p class="text-[11px] font-medium uppercase tracking-wide text-[#a1a1aa]">Agentes</p>
                <p class="mt-1 text-[22px] font-semibold tracking-tight text-[#09090b]">{{ stats.agents_active }}</p>
                <p class="text-[11px] text-[#a1a1aa]">{{ stats.agents_completed_today }} hoy · {{ stats.agents_total_week }} sem</p>
            </div>

            <div class="rounded-lg border border-[#e4e4e7] bg-white px-4 py-3">
                <p class="text-[11px] font-medium uppercase tracking-wide text-[#a1a1aa]">Tasa éxito</p>
                <p class="mt-1 text-[22px] font-semibold tracking-tight" :class="stats.success_rate !== null && stats.success_rate >= 90 ? 'text-emerald-600' : stats.success_rate !== null && stats.success_rate >= 70 ? 'text-[#09090b]' : 'text-amber-600'">
                    {{ stats.success_rate !== null ? `${stats.success_rate}%` : '—' }}
                </p>
                <p class="text-[11px] text-[#a1a1aa]">últimos 7 días</p>
            </div>

            <div class="rounded-lg border border-[#e4e4e7] bg-white px-4 py-3">
                <p class="text-[11px] font-medium uppercase tracking-wide text-[#a1a1aa]">Aprobaciones</p>
                <p class="mt-1 text-[22px] font-semibold tracking-tight" :class="stats.approvals_pending > 0 ? 'text-amber-600' : 'text-[#09090b]'">
                    {{ stats.approvals_pending }}
                </p>
                <p class="text-[11px] text-[#a1a1aa]">{{ stats.approvals_resolved_today }} resueltas hoy</p>
            </div>

            <div class="rounded-lg border border-[#e4e4e7] bg-white px-4 py-3">
                <p class="text-[11px] font-medium uppercase tracking-wide text-[#a1a1aa]">Portafolio</p>
                <p class="mt-1 text-[22px] font-semibold tracking-tight text-[#09090b]">{{ stats.assets_total }}</p>
                <p class="text-[11px] text-[#a1a1aa]">activos operando</p>
            </div>

            <div class="rounded-lg border border-[#e4e4e7] bg-white px-4 py-3">
                <p class="text-[11px] font-medium uppercase tracking-wide text-[#a1a1aa]">Score</p>
                <p class="mt-1 text-[22px] font-semibold tracking-tight" :class="stats.score_avg !== null ? 'text-[#09090b]' : 'text-[#d4d4d8]'">
                    {{ stats.score_avg ?? '—' }}
                </p>
                <p v-if="stats.score_avg" class="text-[11px] text-[#a1a1aa]">{{ classifyLabel(stats.score_avg) }}</p>
            </div>
        </div>

        <!-- ===== AGENTS SECTION ===== -->
        <div class="mt-6">
            <div class="mb-3 flex items-center justify-between">
                <button class="flex items-center gap-1.5 text-[13px] font-medium text-[#09090b]" @click="sections.agents = !sections.agents">
                    <svg :class="['h-3 w-3 text-[#a1a1aa] transition-transform', sections.agents ? 'rotate-90' : '']" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                    </svg>
                    Estado de agentes
                    <span class="text-[11px] font-normal text-[#a1a1aa]">— 9 agentes, 3 capas</span>
                </button>
                <Link href="/agent-runs" class="text-[12px] text-[#71717a] hover:text-[#09090b]">Historial</Link>
            </div>
            <div v-show="sections.agents">
                <AgentStatusGrid :agents="agentStatuses" />
            </div>
        </div>

        <!-- ===== MAIN GRID ===== -->
        <div class="mt-6 grid grid-cols-1 gap-5 lg:grid-cols-12">

            <!-- LEFT: 8 cols -->
            <div class="space-y-5 lg:col-span-8">
                <div class="rounded-lg border border-[#e4e4e7] bg-white px-5 py-4">
                    <div class="mb-3 flex items-center justify-between">
                        <div>
                            <h3 class="text-[13px] font-medium text-[#09090b]">Actividad semanal</h3>
                            <p class="text-[11px] text-[#a1a1aa]">Ejecuciones por día y estado de los últimos 7 días</p>
                        </div>
                        <span class="rounded-md border border-[#e4e4e7] px-2 py-0.5 text-[11px] font-medium text-[#71717a]">{{ stats.agents_total_week }} total</span>
                    </div>
                    <AgentActivityChart :data="agentActivity" />
                </div>

                <div class="rounded-lg border border-[#e4e4e7] bg-white px-5 py-4">
                    <div class="mb-3 flex items-center justify-between">
                        <button class="flex items-center gap-1.5 text-[13px] font-medium text-[#09090b]" @click="sections.approvals = !sections.approvals">
                            <svg :class="['h-3 w-3 text-[#a1a1aa] transition-transform', sections.approvals ? 'rotate-90' : '']" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                            </svg>
                            Pendientes de aprobación
                            <span v-if="stats.approvals_pending > 0" class="ml-1 inline-flex h-[18px] min-w-[18px] items-center justify-center rounded-full bg-amber-100 px-1 text-[10px] font-semibold text-amber-700">{{ stats.approvals_pending }}</span>
                        </button>
                        <Link href="/approvals" class="text-[12px] text-[#71717a] hover:text-[#09090b]">Ver todas</Link>
                    </div>
                    <div v-show="sections.approvals">
                        <div v-if="pendingApprovals.length === 0" class="py-6 text-center text-[13px] text-[#a1a1aa]">
                            Todo aprobado. Sin tareas pendientes.
                        </div>
                        <div v-else class="-mx-3">
                            <ApprovalRow v-for="approval in pendingApprovals" :key="approval.id" :approval="approval" />
                        </div>
                    </div>
                </div>
            </div>

            <!-- RIGHT: 4 cols -->
            <div class="space-y-5 lg:col-span-4">
                <div class="rounded-lg border border-[#e4e4e7] bg-white px-5 py-4">
                    <h3 class="text-[13px] font-medium text-[#09090b]">Score del portafolio</h3>
                    <p class="text-[11px] text-[#a1a1aa]">Promedio ponderado de {{ stats.assets_total }} activos</p>
                    <ScoreGaugeChart :score="stats.score_avg" />
                    <div class="mt-2 space-y-0 border-t border-[#f4f4f5] pt-2">
                        <div v-for="asset in assets" :key="asset.id" class="flex items-center justify-between py-1.5">
                            <div>
                                <p class="text-[12px] font-medium text-[#09090b]">{{ asset.domain }}</p>
                                <p class="text-[10px] text-[#a1a1aa]">{{ asset.vertical }}</p>
                            </div>
                            <div v-if="asset.score !== null" class="text-right">
                                <span class="text-[13px] font-semibold text-[#09090b]">{{ asset.score }}</span>
                                <p :class="['text-[10px] font-medium', classColor(asset.classification)]">{{ asset.classification }}</p>
                            </div>
                            <span v-else class="text-[12px] text-[#d4d4d8]">—</span>
                        </div>
                    </div>
                </div>

                <div class="rounded-lg border border-[#e4e4e7] bg-white px-5 py-4">
                    <button class="mb-3 flex w-full items-center gap-1.5 text-[13px] font-medium text-[#09090b]" @click="sections.activity = !sections.activity">
                        <svg :class="['h-3 w-3 text-[#a1a1aa] transition-transform', sections.activity ? 'rotate-90' : '']" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                        </svg>
                        Actividad reciente
                    </button>
                    <div v-show="sections.activity">
                        <ActivityTimeline :events="timeline" />
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
