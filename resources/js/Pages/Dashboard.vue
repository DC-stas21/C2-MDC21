<script setup lang="ts">
import { ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import AppLayout from '@/Layouts/AppLayout.vue';
import type { Approval } from '@/types';
import AgentActivityChart from '@/Components/Charts/AgentActivityChart.vue';
import AgentStatusGrid from '@/Components/AgentStatusGrid.vue';
import ActivityTimeline from '@/Components/ActivityTimeline.vue';
import ApprovalRow from '@/Components/ApprovalRow.vue';
import { useRealtimeDashboard } from '@/composables/useRealtimeDashboard';

const { t } = useI18n();
const { isConnected } = useRealtimeDashboard();

const props = defineProps<{
    stats: {
        agents_active: number;
        agents_completed_today: number;
        agents_failed_today: number;
        approvals_pending: number;
        webs_total: number;
        webs_live: number;
        webs_building: number;
        webs_staging: number;
        webs_failed: number;
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
    assets: Array<{
        id: string; domain: string; vertical: string;
        build_status: string; description: string;
        last_build: string | null; error: string | null;
    }>;
    timeline: Array<{ type: 'agent_run' | 'approval'; agent_type?: string; status: string; error?: string | null; action?: string; level?: string; at: string }>;
}>();

const sections = ref({ agents: true, approvals: true, activity: true });

const buildStatusStyle: Record<string, { label: string; dot: string; text: string }> = {
    pending: { label: 'Pendiente', dot: 'bg-[#d4d4d8]', text: 'text-[#a1a1aa]' },
    building: { label: 'Construyendo...', dot: 'bg-amber-9500 animate-pulse', text: 'text-amber-600' },
    staging: { label: 'En staging', dot: 'bg-blue-9500', text: 'text-blue-600' },
    live: { label: 'Publicado', dot: 'bg-emerald-500', text: 'text-emerald-600' },
    failed: { label: 'Error', dot: 'bg-red-500', text: 'text-red-400' },
};
</script>

<template>
    <AppLayout>
        <template #header>
            <h1 class="text-[14px] font-medium text-[#fafafa]">Panel de Control</h1>
        </template>

        <!-- ===== TOP METRICS ===== -->
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-6">
            <!-- Webs overview -->
            <div class="rounded-lg border border-[#1f1f23] bg-[#0c0c0f] px-4 py-3">
                <p class="text-[11px] font-medium uppercase tracking-wide text-[#a1a1aa]">Webs</p>
                <p class="mt-1 text-[22px] font-semibold text-[#fafafa]">{{ stats.webs_total }}</p>
                <p class="text-[11px] text-[#a1a1aa]">
                    <span v-if="stats.webs_live > 0" class="text-emerald-600">{{ stats.webs_live }} live</span>
                    <span v-if="stats.webs_building > 0" class="ml-1 text-amber-600">{{ stats.webs_building }} build</span>
                    <span v-if="stats.webs_staging > 0" class="ml-1 text-blue-600">{{ stats.webs_staging }} staging</span>
                </p>
            </div>

            <!-- Agentes -->
            <div class="rounded-lg border border-[#1f1f23] bg-[#0c0c0f] px-4 py-3">
                <p class="text-[11px] font-medium uppercase tracking-wide text-[#a1a1aa]">Agentes</p>
                <p class="mt-1 text-[22px] font-semibold text-[#fafafa]">{{ stats.agents_active }}</p>
                <p class="text-[11px] text-[#a1a1aa]">{{ stats.agents_completed_today }} completados hoy</p>
            </div>

            <!-- Tasa éxito -->
            <div class="rounded-lg border border-[#1f1f23] bg-[#0c0c0f] px-4 py-3">
                <p class="text-[11px] font-medium uppercase tracking-wide text-[#a1a1aa]">Tasa éxito</p>
                <p class="mt-1 text-[22px] font-semibold" :class="stats.success_rate !== null && stats.success_rate >= 90 ? 'text-emerald-600' : 'text-[#fafafa]'">
                    {{ stats.success_rate !== null ? `${stats.success_rate}%` : '—' }}
                </p>
                <p class="text-[11px] text-[#a1a1aa]">últimos 7 días</p>
            </div>

            <!-- Fallidos -->
            <div class="rounded-lg border border-[#1f1f23] bg-[#0c0c0f] px-4 py-3">
                <p class="text-[11px] font-medium uppercase tracking-wide text-[#a1a1aa]">Fallidos hoy</p>
                <p class="mt-1 text-[22px] font-semibold" :class="stats.agents_failed_today > 0 ? 'text-red-400' : 'text-[#fafafa]'">{{ stats.agents_failed_today }}</p>
            </div>

            <!-- Aprobaciones -->
            <div class="rounded-lg border border-[#1f1f23] bg-[#0c0c0f] px-4 py-3">
                <p class="text-[11px] font-medium uppercase tracking-wide text-[#a1a1aa]">Aprobaciones</p>
                <p class="mt-1 text-[22px] font-semibold" :class="stats.approvals_pending > 0 ? 'text-amber-600' : 'text-[#fafafa]'">{{ stats.approvals_pending }}</p>
                <p class="text-[11px] text-[#a1a1aa]">pendientes</p>
            </div>

            <!-- Webs con error -->
            <div class="rounded-lg border border-[#1f1f23] bg-[#0c0c0f] px-4 py-3">
                <p class="text-[11px] font-medium uppercase tracking-wide text-[#a1a1aa]">Errores</p>
                <p class="mt-1 text-[22px] font-semibold" :class="stats.webs_failed > 0 ? 'text-red-400' : 'text-[#fafafa]'">{{ stats.webs_failed }}</p>
                <p class="text-[11px] text-[#a1a1aa]">webs con error</p>
            </div>
        </div>

        <!-- ===== PIPELINE STATUS ===== -->
        <div class="mt-6">
            <div class="mb-3 flex items-center justify-between">
                <h3 class="text-[13px] font-medium text-[#fafafa]">
                    Pipeline de webs
                    <span class="text-[11px] font-normal text-[#a1a1aa]">— estado de cada activo</span>
                </h3>
                <Link href="/assets" class="text-[12px] text-[#a1a1aa] hover:text-[#fafafa]">Ver portafolio</Link>
            </div>
            <div v-if="assets.length === 0" class="rounded-lg border border-[#1f1f23] bg-[#0c0c0f] py-8 text-center text-[13px] text-[#a1a1aa]">
                Sin activos. Crea uno en /admin → Activos.
            </div>
            <div v-else class="grid gap-2">
                <div
                    v-for="asset in assets"
                    :key="asset.id"
                    class="flex items-center justify-between rounded-lg border border-[#1f1f23] bg-[#0c0c0f] px-4 py-3"
                >
                    <div class="flex items-center gap-3">
                        <span :class="['h-2.5 w-2.5 rounded-full', buildStatusStyle[asset.build_status]?.dot ?? 'bg-[#d4d4d8]']" />
                        <div>
                            <p class="text-[13px] font-medium text-[#fafafa]">{{ asset.domain }}</p>
                            <p class="text-[11px] text-[#a1a1aa]">{{ asset.vertical }} · {{ asset.description?.substring(0, 60) }}</p>
                        </div>
                    </div>
                    <div class="text-right">
                        <span :class="['text-[12px] font-medium', buildStatusStyle[asset.build_status]?.text ?? 'text-[#a1a1aa]']">
                            {{ buildStatusStyle[asset.build_status]?.label ?? asset.build_status }}
                        </span>
                        <p v-if="asset.error" class="max-w-[200px] truncate text-[10px] text-red-500">{{ asset.error }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- ===== AGENTS ===== -->
        <div class="mt-6">
            <div class="mb-3 flex items-center justify-between">
                <button class="flex items-center gap-1.5 text-[13px] font-medium text-[#fafafa]" @click="sections.agents = !sections.agents">
                    <svg :class="['h-3 w-3 text-[#a1a1aa] transition-transform', sections.agents ? 'rotate-90' : '']" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                    </svg>
                    Agentes del pipeline
                    <span class="text-[11px] font-normal text-[#a1a1aa]">— pipeline de creación</span>
                </button>
                <Link href="/agent-runs" class="text-[12px] text-[#a1a1aa] hover:text-[#fafafa]">Historial</Link>
            </div>
            <div v-show="sections.agents">
                <AgentStatusGrid :agents="agentStatuses" />
            </div>
        </div>

        <!-- ===== MAIN GRID ===== -->
        <div class="mt-6 grid grid-cols-1 gap-5 lg:grid-cols-12">
            <!-- LEFT -->
            <div class="space-y-5 lg:col-span-8">
                <div class="rounded-lg border border-[#1f1f23] bg-[#0c0c0f] px-5 py-4">
                    <div class="mb-3">
                        <h3 class="text-[13px] font-medium text-[#fafafa]">Actividad semanal</h3>
                        <p class="text-[11px] text-[#a1a1aa]">Ejecuciones de agentes por día</p>
                    </div>
                    <AgentActivityChart :data="agentActivity" />
                </div>

                <div class="rounded-lg border border-[#1f1f23] bg-[#0c0c0f] px-5 py-4">
                    <div class="mb-3 flex items-center justify-between">
                        <button class="flex items-center gap-1.5 text-[13px] font-medium text-[#fafafa]" @click="sections.approvals = !sections.approvals">
                            <svg :class="['h-3 w-3 text-[#a1a1aa] transition-transform', sections.approvals ? 'rotate-90' : '']" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                            </svg>
                            Pendientes de aprobación
                            <span v-if="stats.approvals_pending > 0" class="ml-1 inline-flex h-[18px] min-w-[18px] items-center justify-center rounded-full bg-amber-900/30 px-1 text-[10px] font-semibold text-amber-400">{{ stats.approvals_pending }}</span>
                        </button>
                        <Link href="/approvals" class="text-[12px] text-[#a1a1aa] hover:text-[#fafafa]">Ver todas</Link>
                    </div>
                    <div v-show="sections.approvals">
                        <div v-if="pendingApprovals.length === 0" class="py-6 text-center text-[13px] text-[#a1a1aa]">
                            Sin tareas pendientes.
                        </div>
                        <div v-else class="-mx-3">
                            <ApprovalRow v-for="approval in pendingApprovals" :key="approval.id" :approval="approval" />
                        </div>
                    </div>
                </div>
            </div>

            <!-- RIGHT -->
            <div class="space-y-5 lg:col-span-4">
                <div class="rounded-lg border border-[#1f1f23] bg-[#0c0c0f] px-5 py-4">
                    <button class="mb-3 flex w-full items-center gap-1.5 text-[13px] font-medium text-[#fafafa]" @click="sections.activity = !sections.activity">
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
