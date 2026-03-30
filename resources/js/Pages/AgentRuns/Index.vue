<script setup lang="ts">
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import AppLayout from '@/Layouts/AppLayout.vue';
import AgentProfileCard from '@/Components/AgentProfileCard.vue';
import AgentActivityChart from '@/Components/Charts/AgentActivityChart.vue';
import type { AgentRun, AgentStatus, PaginatedResponse } from '@/types';

const { t } = useI18n();

type AgentProfile = {
    type: string; layer: number; model: string; queue: string;
    is_running: boolean; last_status: string | null; last_run_at: string | null;
    last_error: string | null; today_runs: number; today_failed: number;
    week_total: number; success_rate: number | null; avg_duration_sec: number | null;
};

const props = defineProps<{
    runs: PaginatedResponse<AgentRun>;
    filters: { agent_type?: string; status?: string };
    globalStats: {
        running: number; pending: number; completed_today: number;
        failed_today: number; total_week: number; success_rate: number | null;
        avg_duration: number | null;
    };
    agentProfiles: AgentProfile[];
    dailyActivity: Array<{ date: string; status: string; count: number }>;
    recentErrors: Array<{ id: string; agent_type: string; error: string; started_at: string }>;
}>();

const selectedAgent = ref<string | null>(props.filters.agent_type ?? null);
const showProfiles = ref(true);
const statusFilter = ref<string | null>(props.filters.status ?? null);

const statuses: AgentStatus[] = ['pending', 'running', 'completed', 'failed'];

function selectAgent(type: string) {
    if (selectedAgent.value === type) {
        selectedAgent.value = null;
        applyFilters(null, statusFilter.value);
    } else {
        selectedAgent.value = type;
        applyFilters(type, statusFilter.value);
    }
}

function toggleStatus(s: string) {
    if (statusFilter.value === s) {
        statusFilter.value = null;
        applyFilters(selectedAgent.value, null);
    } else {
        statusFilter.value = s;
        applyFilters(selectedAgent.value, s);
    }
}

function applyFilters(agentType: string | null, status: string | null) {
    const params: Record<string, string> = {};
    if (agentType) params.agent_type = agentType;
    if (status) params.status = status;
    router.get('/agent-runs', params, { preserveState: true, preserveScroll: true });
}

function clearFilters() {
    selectedAgent.value = null;
    statusFilter.value = null;
    router.get('/agent-runs', {}, { preserveState: true, preserveScroll: true });
}

const selectedProfile = computed(() =>
    selectedAgent.value ? props.agentProfiles.find((a) => a.type === selectedAgent.value) : null
);

const statusDot: Record<string, string> = {
    pending: 'bg-[#d4d4d8]', running: 'bg-indigo-500', completed: 'bg-emerald-500', failed: 'bg-red-500',
};

function duration(run: AgentRun): string {
    if (!run.started_at || !run.finished_at) return '—';
    const s = Math.round((new Date(run.finished_at).getTime() - new Date(run.started_at).getTime()) / 1000);
    return s < 60 ? `${s}s` : `${Math.floor(s / 60)}m ${s % 60}s`;
}

function timeAgo(d: string): string {
    const m = Math.floor((Date.now() - new Date(d).getTime()) / 60000);
    if (m < 1) return 'ahora';
    if (m < 60) return `${m}m`;
    const h = Math.floor(m / 60);
    return h < 24 ? `${h}h` : `${Math.floor(h / 24)}d`;
}

function fmtSec(s: number | null): string {
    if (s === null) return '—';
    return s < 60 ? `${s}s` : `${Math.floor(s / 60)}m ${s % 60}s`;
}

const layerLabels = ['Capa 0 — Núcleo', 'Capa 1 — Funcionales', 'Capa 2 — Operativos'];
const profilesByLayer = computed(() => {
    return [0, 1, 2].map((layer) => ({
        label: layerLabels[layer],
        agents: props.agentProfiles.filter((a) => a.layer === layer),
    }));
});
</script>

<template>
    <AppLayout>
        <template #header>
            <h1 class="text-[14px] font-medium text-[#09090b]">{{ t('nav.agents') }}</h1>
        </template>

        <!-- ===== GLOBAL METRICS ===== -->
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-6">
            <div class="rounded-lg border border-[#e4e4e7] bg-white px-4 py-3">
                <p class="text-[11px] font-medium uppercase tracking-wide text-[#a1a1aa]">Ejecutando</p>
                <p class="mt-1 text-[22px] font-semibold text-[#09090b]">{{ globalStats.running }}</p>
            </div>
            <div class="rounded-lg border border-[#e4e4e7] bg-white px-4 py-3">
                <p class="text-[11px] font-medium uppercase tracking-wide text-[#a1a1aa]">En cola</p>
                <p class="mt-1 text-[22px] font-semibold text-[#09090b]">{{ globalStats.pending }}</p>
            </div>
            <div class="rounded-lg border border-[#e4e4e7] bg-white px-4 py-3">
                <p class="text-[11px] font-medium uppercase tracking-wide text-[#a1a1aa]">Completados hoy</p>
                <p class="mt-1 text-[22px] font-semibold text-emerald-600">{{ globalStats.completed_today }}</p>
            </div>
            <div class="rounded-lg border border-[#e4e4e7] bg-white px-4 py-3">
                <p class="text-[11px] font-medium uppercase tracking-wide text-[#a1a1aa]">Fallidos hoy</p>
                <p class="mt-1 text-[22px] font-semibold" :class="globalStats.failed_today > 0 ? 'text-red-600' : 'text-[#09090b]'">{{ globalStats.failed_today }}</p>
            </div>
            <div class="rounded-lg border border-[#e4e4e7] bg-white px-4 py-3">
                <p class="text-[11px] font-medium uppercase tracking-wide text-[#a1a1aa]">Tasa éxito</p>
                <p class="mt-1 text-[22px] font-semibold" :class="globalStats.success_rate !== null && globalStats.success_rate >= 90 ? 'text-emerald-600' : 'text-[#09090b]'">
                    {{ globalStats.success_rate !== null ? `${globalStats.success_rate}%` : '—' }}
                </p>
                <p class="text-[11px] text-[#a1a1aa]">últimos 7 días</p>
            </div>
            <div class="rounded-lg border border-[#e4e4e7] bg-white px-4 py-3">
                <p class="text-[11px] font-medium uppercase tracking-wide text-[#a1a1aa]">Duración media</p>
                <p class="mt-1 font-mono text-[20px] font-semibold text-[#09090b]">{{ fmtSec(globalStats.avg_duration) }}</p>
                <p class="text-[11px] text-[#a1a1aa]">{{ globalStats.total_week }} ejecuciones/sem</p>
            </div>
        </div>

        <!-- ===== AGENT PROFILES ===== -->
        <div class="mt-6">
            <button class="flex items-center gap-1.5 text-[13px] font-medium text-[#09090b]" @click="showProfiles = !showProfiles">
                <svg :class="['h-3 w-3 text-[#a1a1aa] transition-transform', showProfiles ? 'rotate-90' : '']" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                </svg>
                Agentes
                <span class="text-[11px] font-normal text-[#a1a1aa]">— click en un agente para filtrar historial</span>
            </button>
        </div>

        <div v-show="showProfiles" class="mt-3 space-y-5">
            <div v-for="group in profilesByLayer" :key="group.label">
                <p class="mb-2 text-[11px] font-medium uppercase tracking-wide text-[#a1a1aa]">{{ group.label }}</p>
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    <AgentProfileCard
                        v-for="agent in group.agents"
                        :key="agent.type"
                        :agent="agent"
                        :selected="selectedAgent === agent.type"
                        @select="selectAgent"
                    />
                </div>
            </div>
        </div>

        <!-- ===== CHART + ERRORS ===== -->
        <div class="mt-6 grid grid-cols-1 gap-5 lg:grid-cols-12">
            <div class="rounded-lg border border-[#e4e4e7] bg-white px-5 py-4 lg:col-span-8">
                <div class="mb-3 flex items-center justify-between">
                    <div>
                        <h3 class="text-[13px] font-medium text-[#09090b]">Actividad semanal</h3>
                        <p class="text-[11px] text-[#a1a1aa]">Ejecuciones totales por día y estado</p>
                    </div>
                    <span class="rounded-md border border-[#e4e4e7] px-2 py-0.5 text-[11px] font-medium text-[#71717a]">{{ globalStats.total_week }} total</span>
                </div>
                <AgentActivityChart :data="dailyActivity" />
            </div>

            <div class="rounded-lg border border-[#e4e4e7] bg-white px-5 py-4 lg:col-span-4">
                <h3 class="mb-3 text-[13px] font-medium text-[#09090b]">Errores recientes</h3>
                <div v-if="recentErrors.length === 0" class="py-6 text-center text-[13px] text-[#a1a1aa]">Sin errores recientes</div>
                <div v-else class="space-y-0">
                    <div v-for="err in recentErrors" :key="err.id" class="border-b border-[#f4f4f5] py-2.5 last:border-0">
                        <div class="flex items-center justify-between">
                            <span class="text-[12px] font-medium text-[#09090b]">{{ t(`agents.${err.agent_type}`) }}</span>
                            <span class="text-[11px] text-[#a1a1aa]">{{ timeAgo(err.started_at) }}</span>
                        </div>
                        <p class="mt-0.5 truncate text-[11px] text-red-500">{{ err.error }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- ===== SELECTED AGENT DETAIL BAR ===== -->
        <div v-if="selectedProfile" class="mt-5 rounded-lg border border-[#09090b] bg-[#09090b] px-5 py-3 text-white">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <span
                        :class="[
                            'h-2.5 w-2.5 rounded-full',
                            selectedProfile.is_running ? 'animate-pulse bg-indigo-400' :
                            selectedProfile.last_status === 'completed' ? 'bg-emerald-400' :
                            selectedProfile.last_status === 'failed' ? 'bg-red-400' : 'bg-gray-500',
                        ]"
                    />
                    <span class="text-[14px] font-semibold">{{ t(`agents.${selectedProfile.type}`) }}</span>
                    <span class="text-[12px] text-gray-400">{{ selectedProfile.model }}</span>
                </div>
                <div class="flex items-center gap-6 text-[12px]">
                    <span>Éxito: <strong>{{ selectedProfile.success_rate ?? '—' }}%</strong></span>
                    <span>Duración media: <strong class="font-mono">{{ fmtSec(selectedProfile.avg_duration_sec) }}</strong></span>
                    <span>Hoy: <strong>{{ selectedProfile.today_runs }}x</strong></span>
                    <span>Semana: <strong>{{ selectedProfile.week_total }}x</strong></span>
                    <button class="text-gray-400 hover:text-white" @click="clearFilters">Limpiar filtro</button>
                </div>
            </div>
        </div>

        <!-- ===== HISTORY TABLE ===== -->
        <div class="mt-5">
            <div class="mb-3 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <h3 class="text-[13px] font-medium text-[#09090b]">
                        {{ selectedAgent ? `Historial — ${t(`agents.${selectedAgent}`)}` : 'Historial de ejecuciones' }}
                    </h3>
                    <!-- Status pills -->
                    <div class="flex gap-1">
                        <button
                            v-for="s in statuses"
                            :key="s"
                            :class="[
                                'flex items-center gap-1 rounded-full border px-2 py-0.5 text-[11px] font-medium transition',
                                statusFilter === s
                                    ? 'border-[#09090b] bg-[#09090b] text-white'
                                    : 'border-[#e4e4e7] text-[#71717a] hover:border-[#a1a1aa]',
                            ]"
                            @click="toggleStatus(s)"
                        >
                            <span :class="['h-[5px] w-[5px] rounded-full', statusFilter === s ? 'bg-white' : statusDot[s]]" />
                            {{ t(`agents.status.${s}`) }}
                        </button>
                    </div>
                </div>
                <button v-if="filters.agent_type || filters.status" class="text-[12px] text-[#71717a] hover:text-[#09090b]" @click="clearFilters">
                    Limpiar filtros
                </button>
            </div>

            <div class="overflow-hidden rounded-lg border border-[#e4e4e7] bg-white">
                <table class="w-full text-[13px]">
                    <thead>
                        <tr class="border-b border-[#e4e4e7]">
                            <th class="px-4 py-2.5 text-left text-[11px] font-medium uppercase tracking-wide text-[#a1a1aa]">Agente</th>
                            <th class="px-4 py-2.5 text-left text-[11px] font-medium uppercase tracking-wide text-[#a1a1aa]">Estado</th>
                            <th class="hidden px-4 py-2.5 text-left text-[11px] font-medium uppercase tracking-wide text-[#a1a1aa] sm:table-cell">Duración</th>
                            <th class="hidden px-4 py-2.5 text-left text-[11px] font-medium uppercase tracking-wide text-[#a1a1aa] md:table-cell">Error</th>
                            <th class="px-4 py-2.5 text-right text-[11px] font-medium uppercase tracking-wide text-[#a1a1aa]">Hace</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="run in runs.data" :key="run.id" class="border-b border-[#f4f4f5] last:border-0 hover:bg-[#fafafa]">
                            <td class="px-4 py-2.5 font-medium text-[#09090b]">{{ t(`agents.${run.agent_type}`) }}</td>
                            <td class="px-4 py-2.5">
                                <span class="inline-flex items-center gap-1.5">
                                    <span :class="['h-[6px] w-[6px] rounded-full', run.status === 'running' ? 'animate-pulse' : '', statusDot[run.status]]" />
                                    <span class="text-[#71717a]">{{ t(`agents.status.${run.status}`) }}</span>
                                </span>
                            </td>
                            <td class="hidden px-4 py-2.5 font-mono text-[12px] text-[#a1a1aa] sm:table-cell">{{ duration(run) }}</td>
                            <td class="hidden max-w-[220px] truncate px-4 py-2.5 text-[12px] text-red-500 md:table-cell">{{ run.error || '' }}</td>
                            <td class="px-4 py-2.5 text-right text-[12px] text-[#a1a1aa]">{{ run.started_at ? timeAgo(run.started_at) : '' }}</td>
                        </tr>
                        <tr v-if="runs.data.length === 0">
                            <td colspan="5" class="px-4 py-10 text-center text-[#a1a1aa]">Sin ejecuciones</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div v-if="runs.last_page > 1" class="mt-4 flex items-center justify-between text-[12px]">
                <span class="text-[#a1a1aa]">{{ runs.total }} ejecuciones</span>
                <div class="flex gap-0.5">
                    <button
                        v-for="page in runs.last_page"
                        :key="page"
                        :class="[
                            'h-7 w-7 rounded-md font-medium transition',
                            page === runs.current_page ? 'bg-[#09090b] text-white' : 'text-[#71717a] hover:bg-[#f4f4f5]',
                        ]"
                        @click="router.get('/agent-runs', { ...filters, page }, { preserveState: true })"
                    >{{ page }}</button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
