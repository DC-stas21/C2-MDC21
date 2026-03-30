<script setup lang="ts">
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import AppLayout from '@/Layouts/AppLayout.vue';
import ApprovalRow from '@/Components/ApprovalRow.vue';
import type { Approval, ApprovalStatus, PaginatedResponse } from '@/types';

const { t } = useI18n();

const props = defineProps<{
    approvals: PaginatedResponse<Approval>;
    filters: { status?: string; level?: string; agent_type?: string };
    stats: {
        pending: number; approved_today: number; denied_today: number;
        approved_week: number; denied_week: number; total: number;
        oldest_pending_hours: number | null;
    };
    byLevel: Record<string, number>;
    byAgent: Record<string, number>;
    recentDecisions: Array<{
        id: string; action: string; status: string; level: string;
        decided_by: string | null; decision_note: string | null;
        decided_at: string; agent_run_id: string;
        decider?: { id: string; name: string } | null;
        agent_run?: { id: string; agent_type: string } | null;
    }>;
    avgResponseTime: number | null;
}>();

const activeTab = ref<string>(props.filters.status || 'pending');
const showDecisions = ref(true);

function switchTab(status: string) {
    activeTab.value = status;
    const params: Record<string, string> = {};
    if (status !== 'pending') params.status = status;
    if (props.filters.level) params.level = props.filters.level;
    if (props.filters.agent_type) params.agent_type = props.filters.agent_type;
    router.get('/approvals', params, { preserveState: true, preserveScroll: true });
}

function filterLevel(level: string) {
    const params: Record<string, string> = {};
    if (activeTab.value !== 'pending') params.status = activeTab.value;
    if (props.filters.level === level) {
        // Toggle off
    } else {
        params.level = level;
    }
    if (props.filters.agent_type) params.agent_type = props.filters.agent_type;
    router.get('/approvals', params, { preserveState: true, preserveScroll: true });
}

function filterAgent(type: string) {
    const params: Record<string, string> = {};
    if (activeTab.value !== 'pending') params.status = activeTab.value;
    if (props.filters.level) params.level = props.filters.level;
    if (props.filters.agent_type === type) {
        // Toggle off
    } else {
        params.agent_type = type;
    }
    router.get('/approvals', params, { preserveState: true, preserveScroll: true });
}

function clearFilters() {
    activeTab.value = 'pending';
    router.get('/approvals', {}, { preserveState: true, preserveScroll: true });
}

function timeAgo(dateStr: string): string {
    const diff = Date.now() - new Date(dateStr).getTime();
    const mins = Math.floor(diff / 60000);
    if (mins < 1) return 'ahora';
    if (mins < 60) return `${mins}m`;
    const h = Math.floor(mins / 60);
    return h < 24 ? `${h}h` : `${Math.floor(h / 24)}d`;
}

const levelInfo: Record<string, { label: string; desc: string; color: string }> = {
    N1: { label: 'N1', desc: 'Automático', color: 'bg-emerald-500' },
    N2: { label: 'N2', desc: 'Semiautomático', color: 'bg-amber-500' },
    N3: { label: 'N3', desc: 'Humano obligatorio', color: 'bg-red-500' },
};

const hasFilters = props.filters.level || props.filters.agent_type || (props.filters.status && props.filters.status !== 'pending');
</script>

<template>
    <AppLayout>
        <template #header>
            <h1 class="text-[14px] font-medium text-[#09090b]">{{ t('approvals.title') }}</h1>
        </template>

        <!-- ===== TOP: Urgency bar when pending > 0 ===== -->
        <div v-if="stats.pending > 0" class="mb-5 flex items-center justify-between rounded-lg border border-amber-200 bg-amber-50 px-4 py-3">
            <div class="flex items-center gap-3">
                <div class="flex h-8 w-8 items-center justify-center rounded-full bg-amber-100">
                    <svg class="h-4 w-4 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.999L13.732 4.001c-.77-1.333-2.694-1.333-3.464 0L3.34 16.001C2.57 17.334 3.532 19 5.072 19z" />
                    </svg>
                </div>
                <div>
                    <p class="text-[13px] font-semibold text-amber-900">
                        {{ stats.pending }} {{ stats.pending === 1 ? 'aprobación pendiente' : 'aprobaciones pendientes' }}
                    </p>
                    <p v-if="stats.oldest_pending_hours !== null" class="text-[12px] text-amber-700">
                        La más antigua lleva {{ stats.oldest_pending_hours }}h esperando
                    </p>
                </div>
            </div>
            <button class="rounded-md bg-amber-600 px-3 py-1.5 text-[12px] font-medium text-white hover:bg-amber-700" @click="switchTab('pending')">
                Revisar ahora
            </button>
        </div>

        <!-- ===== METRICS ROW ===== -->
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-4 lg:grid-cols-7">
            <div class="rounded-lg border border-[#e4e4e7] bg-white px-4 py-3">
                <p class="text-[11px] font-medium uppercase tracking-wide text-[#a1a1aa]">Pendientes</p>
                <p class="mt-1 text-[22px] font-semibold" :class="stats.pending > 0 ? 'text-amber-600' : 'text-[#09090b]'">{{ stats.pending }}</p>
            </div>
            <div class="rounded-lg border border-[#e4e4e7] bg-white px-4 py-3">
                <p class="text-[11px] font-medium uppercase tracking-wide text-[#a1a1aa]">Aprobadas hoy</p>
                <p class="mt-1 text-[22px] font-semibold text-emerald-600">{{ stats.approved_today }}</p>
            </div>
            <div class="rounded-lg border border-[#e4e4e7] bg-white px-4 py-3">
                <p class="text-[11px] font-medium uppercase tracking-wide text-[#a1a1aa]">Denegadas hoy</p>
                <p class="mt-1 text-[22px] font-semibold" :class="stats.denied_today > 0 ? 'text-red-600' : 'text-[#09090b]'">{{ stats.denied_today }}</p>
            </div>
            <div class="rounded-lg border border-[#e4e4e7] bg-white px-4 py-3">
                <p class="text-[11px] font-medium uppercase tracking-wide text-[#a1a1aa]">Semana</p>
                <p class="mt-1 text-[22px] font-semibold text-[#09090b]">{{ stats.approved_week + stats.denied_week }}</p>
                <p class="text-[11px] text-[#a1a1aa]">{{ stats.approved_week }} aprob · {{ stats.denied_week }} deneg</p>
            </div>
            <div class="rounded-lg border border-[#e4e4e7] bg-white px-4 py-3">
                <p class="text-[11px] font-medium uppercase tracking-wide text-[#a1a1aa]">Tiempo resp.</p>
                <p class="mt-1 font-mono text-[20px] font-semibold text-[#09090b]">{{ avgResponseTime !== null ? `${avgResponseTime}h` : '—' }}</p>
                <p class="text-[11px] text-[#a1a1aa]">media últimos 7d</p>
            </div>
            <div class="rounded-lg border border-[#e4e4e7] bg-white px-4 py-3">
                <p class="text-[11px] font-medium uppercase tracking-wide text-[#a1a1aa]">Total histórico</p>
                <p class="mt-1 text-[22px] font-semibold text-[#09090b]">{{ stats.total }}</p>
            </div>
            <div class="rounded-lg border border-[#e4e4e7] bg-white px-4 py-3">
                <p class="text-[11px] font-medium uppercase tracking-wide text-[#a1a1aa]">Ratio aprobación</p>
                <p class="mt-1 text-[22px] font-semibold text-[#09090b]">
                    {{ stats.approved_week + stats.denied_week > 0 ? Math.round((stats.approved_week / (stats.approved_week + stats.denied_week)) * 100) + '%' : '—' }}
                </p>
            </div>
        </div>

        <!-- ===== MAIN GRID ===== -->
        <div class="mt-6 grid grid-cols-1 gap-5 lg:grid-cols-12">

            <!-- LEFT: Queue (8 cols) -->
            <div class="lg:col-span-8">
                <!-- Tabs + Filters -->
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-1 border-b border-[#e4e4e7]">
                        <button
                            v-for="tab in (['pending', 'approved', 'denied'] as const)"
                            :key="tab"
                            :class="[
                                '-mb-px border-b-2 px-4 py-2 text-[13px] font-medium transition',
                                activeTab === tab ? 'border-[#09090b] text-[#09090b]' : 'border-transparent text-[#a1a1aa] hover:text-[#71717a]',
                            ]"
                            @click="switchTab(tab)"
                        >
                            {{ t(`approvals.status.${tab}`) }}
                            <span v-if="tab === 'pending' && stats.pending > 0" class="ml-1 text-[11px] text-amber-600">{{ stats.pending }}</span>
                        </button>
                    </div>
                    <button v-if="hasFilters" class="text-[12px] text-[#71717a] hover:text-[#09090b]" @click="clearFilters">Limpiar</button>
                </div>

                <!-- Level + Agent filters -->
                <div class="mt-3 flex flex-wrap gap-4">
                    <!-- By level -->
                    <div class="flex items-center gap-1.5">
                        <span class="text-[11px] text-[#a1a1aa]">Nivel:</span>
                        <button
                            v-for="(info, level) in levelInfo"
                            :key="level"
                            :class="[
                                'flex items-center gap-1 rounded-md border px-2 py-0.5 text-[11px] font-medium transition',
                                filters.level === level
                                    ? 'border-[#09090b] bg-[#09090b] text-white'
                                    : 'border-[#e4e4e7] text-[#71717a] hover:border-[#a1a1aa]',
                            ]"
                            @click="filterLevel(level)"
                        >
                            <span :class="['h-[5px] w-[5px] rounded-full', filters.level === level ? 'bg-white' : info.color]" />
                            {{ level }}
                        </button>
                    </div>

                    <!-- By agent -->
                    <div v-if="Object.keys(byAgent).length > 0" class="flex items-center gap-1.5">
                        <span class="text-[11px] text-[#a1a1aa]">Agente:</span>
                        <button
                            v-for="(count, type) in byAgent"
                            :key="type"
                            :class="[
                                'rounded-md border px-2 py-0.5 text-[11px] font-medium transition',
                                filters.agent_type === type
                                    ? 'border-[#09090b] bg-[#09090b] text-white'
                                    : 'border-[#e4e4e7] text-[#71717a] hover:border-[#a1a1aa]',
                            ]"
                            @click="filterAgent(type as string)"
                        >
                            {{ t(`agents.${type}`) }}
                            <span class="ml-0.5 text-[10px] opacity-60">{{ count }}</span>
                        </button>
                    </div>
                </div>

                <!-- Queue items -->
                <div class="mt-4 space-y-1">
                    <div v-if="approvals.data.length === 0" class="rounded-lg border border-[#e4e4e7] bg-white py-12 text-center">
                        <p class="text-[14px] text-[#a1a1aa]">
                            {{ activeTab === 'pending' ? 'Sin aprobaciones pendientes' : 'Sin resultados para estos filtros' }}
                        </p>
                        <p v-if="activeTab === 'pending'" class="mt-1 text-[12px] text-[#d4d4d8]">Los agentes enviarán solicitudes cuando tengan acciones N3</p>
                    </div>
                    <ApprovalRow v-for="approval in approvals.data" :key="approval.id" :approval="approval" />
                </div>

                <!-- Pagination -->
                <div v-if="approvals.last_page > 1" class="mt-4 flex items-center justify-between text-[12px]">
                    <span class="text-[#a1a1aa]">{{ approvals.total }} total</span>
                    <div class="flex gap-0.5">
                        <button
                            v-for="page in approvals.last_page"
                            :key="page"
                            :class="['h-7 w-7 rounded-md font-medium transition', page === approvals.current_page ? 'bg-[#09090b] text-white' : 'text-[#71717a] hover:bg-[#f4f4f5]']"
                            @click="router.get('/approvals', { ...filters, page }, { preserveState: true })"
                        >{{ page }}</button>
                    </div>
                </div>
            </div>

            <!-- RIGHT: Context (4 cols) -->
            <div class="space-y-5 lg:col-span-4">

                <!-- Governance levels explanation -->
                <div class="rounded-lg border border-[#e4e4e7] bg-white px-5 py-4">
                    <h3 class="mb-3 text-[13px] font-medium text-[#09090b]">Niveles de gobernanza</h3>
                    <div class="space-y-3">
                        <div v-for="(info, level) in levelInfo" :key="level" class="flex gap-3">
                            <span :class="['mt-1 h-2 w-2 shrink-0 rounded-full', info.color]" />
                            <div>
                                <p class="text-[12px] font-medium text-[#09090b]">{{ level }} — {{ info.desc }}</p>
                                <p class="text-[11px] text-[#a1a1aa]">
                                    {{ level === 'N1' ? 'Análisis, borradores, scoring. No requiere acción.' :
                                       level === 'N2' ? 'Blog propio, staging, drip. Revisa si quieres.' :
                                       'Redes, producción, contacto externo. Siempre tú.' }}
                                </p>
                                <p v-if="byLevel[level]" class="mt-0.5 text-[11px] font-medium text-amber-600">{{ byLevel[level] }} pendientes</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Audit trail: Recent decisions -->
                <div class="rounded-lg border border-[#e4e4e7] bg-white px-5 py-4">
                    <button class="mb-3 flex w-full items-center gap-1.5 text-[13px] font-medium text-[#09090b]" @click="showDecisions = !showDecisions">
                        <svg :class="['h-3 w-3 text-[#a1a1aa] transition-transform', showDecisions ? 'rotate-90' : '']" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                        </svg>
                        Decisiones recientes
                    </button>
                    <div v-show="showDecisions">
                        <div v-if="recentDecisions.length === 0" class="py-4 text-center text-[12px] text-[#a1a1aa]">Sin decisiones aún</div>
                        <div v-else class="space-y-0">
                            <div v-for="d in recentDecisions" :key="d.id" class="border-b border-[#f4f4f5] py-2.5 last:border-0">
                                <div class="flex items-start justify-between gap-2">
                                    <div class="min-w-0 flex-1">
                                        <div class="flex items-center gap-1.5">
                                            <span :class="['h-[6px] w-[6px] rounded-full', d.status === 'approved' ? 'bg-emerald-500' : 'bg-red-500']" />
                                            <span class="truncate text-[12px] font-medium text-[#09090b]">{{ d.action }}</span>
                                        </div>
                                        <div class="mt-0.5 flex items-center gap-2 text-[11px] text-[#a1a1aa]">
                                            <span v-if="d.decider">{{ d.decider.name }}</span>
                                            <span v-if="d.agent_run">· {{ t(`agents.${d.agent_run.agent_type}`) }}</span>
                                        </div>
                                        <p v-if="d.decision_note" class="mt-0.5 text-[11px] italic text-[#a1a1aa]">"{{ d.decision_note }}"</p>
                                    </div>
                                    <span class="shrink-0 text-[11px] text-[#a1a1aa]">{{ timeAgo(d.decided_at) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick stats panel -->
                <div class="rounded-lg border border-[#e4e4e7] bg-white px-5 py-4">
                    <h3 class="mb-3 text-[13px] font-medium text-[#09090b]">Resumen semanal</h3>
                    <div class="space-y-2">
                        <div class="flex items-center justify-between">
                            <span class="text-[12px] text-[#71717a]">Aprobadas</span>
                            <div class="flex items-center gap-2">
                                <div class="h-1.5 w-20 overflow-hidden rounded-full bg-[#f4f4f5]">
                                    <div class="h-full rounded-full bg-emerald-500" :style="{ width: `${stats.approved_week + stats.denied_week > 0 ? (stats.approved_week / (stats.approved_week + stats.denied_week)) * 100 : 0}%` }" />
                                </div>
                                <span class="w-8 text-right text-[12px] font-medium text-[#09090b]">{{ stats.approved_week }}</span>
                            </div>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-[12px] text-[#71717a]">Denegadas</span>
                            <div class="flex items-center gap-2">
                                <div class="h-1.5 w-20 overflow-hidden rounded-full bg-[#f4f4f5]">
                                    <div class="h-full rounded-full bg-red-500" :style="{ width: `${stats.approved_week + stats.denied_week > 0 ? (stats.denied_week / (stats.approved_week + stats.denied_week)) * 100 : 0}%` }" />
                                </div>
                                <span class="w-8 text-right text-[12px] font-medium text-[#09090b]">{{ stats.denied_week }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
