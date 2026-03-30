<script setup lang="ts">
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import AppLayout from '@/Layouts/AppLayout.vue';
import LeadsPieChart from '@/Components/Charts/LeadsPieChart.vue';
import type { PaginatedResponse } from '@/types';

const { t } = useI18n();

type LeadItem = {
    id: string; asset: string; provider: string | null; score: number;
    status: string; data: Record<string, unknown>; created_at: string;
};

const props = defineProps<{
    leads: PaginatedResponse<LeadItem>;
    filters: { status?: string; asset?: string; score_min?: string };
    stats: {
        total: number; today: number; week: number; avg_score: number;
        qualified: number; sent: number; total_revenue: number;
    };
    pipeline: Record<string, { count: number; avg_score: number }>;
    byAsset: Record<string, Record<string, number>>;
    scoreDistribution: { high: number; medium: number; low: number };
    revenueByAsset: Array<{ domain: string; cpl: number; sent: number; revenue: number }>;
}>();

const activeStatus = ref(props.filters.status || 'all');
const expandedLead = ref<string | null>(null);

function filterStatus(status: string) {
    activeStatus.value = status;
    const params: Record<string, string> = {};
    if (status !== 'all') params.status = status;
    if (props.filters.asset) params.asset = props.filters.asset;
    if (props.filters.score_min) params.score_min = props.filters.score_min;
    router.get('/leads', params, { preserveState: true, preserveScroll: true });
}

function filterAsset(asset: string) {
    const params: Record<string, string> = {};
    if (activeStatus.value !== 'all') params.status = activeStatus.value;
    if (props.filters.asset !== asset) params.asset = asset;
    router.get('/leads', params, { preserveState: true, preserveScroll: true });
}

function filterScore(min: string) {
    const params: Record<string, string> = {};
    if (activeStatus.value !== 'all') params.status = activeStatus.value;
    if (props.filters.asset) params.asset = props.filters.asset;
    if (props.filters.score_min !== min) params.score_min = min;
    router.get('/leads', params, { preserveState: true, preserveScroll: true });
}

function clearFilters() {
    activeStatus.value = 'all';
    router.get('/leads', {}, { preserveState: true, preserveScroll: true });
}

const statusLabels: Record<string, string> = {
    new: 'Nuevo', qualified: 'Cualificado', sent: 'Enviado', discarded: 'Descartado',
};
const statusDot: Record<string, string> = {
    new: 'bg-indigo-500', qualified: 'bg-emerald-500', sent: 'bg-blue-500', discarded: 'bg-[#d4d4d8]',
};
const statusBg: Record<string, string> = {
    new: 'bg-indigo-50 text-indigo-700', qualified: 'bg-emerald-50 text-emerald-700',
    sent: 'bg-blue-50 text-blue-700', discarded: 'bg-gray-100 text-gray-500',
};

function scoreColor(score: number): string {
    if (score >= 70) return 'text-emerald-600';
    if (score >= 40) return 'text-amber-600';
    return 'text-red-500';
}

function scoreBg(score: number): string {
    if (score >= 70) return 'bg-emerald-500';
    if (score >= 40) return 'bg-amber-500';
    return 'bg-red-500';
}

function timeAgo(dateStr: string): string {
    const diff = Date.now() - new Date(dateStr).getTime();
    const h = Math.floor(diff / 3600000);
    if (h < 1) return 'ahora';
    if (h < 24) return `${h}h`;
    return `${Math.floor(h / 24)}d`;
}

const pipelineCounts: Record<string, number> = {};
for (const [k, v] of Object.entries(props.pipeline)) {
    pipelineCounts[k] = (v as any).count ?? v;
}

const hasFilters = props.filters.status || props.filters.asset || props.filters.score_min;
</script>

<template>
    <AppLayout>
        <template #header>
            <h1 class="text-[14px] font-medium text-[#09090b]">{{ t('nav.leads') }}</h1>
        </template>

        <!-- ===== METRICS ===== -->
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-7">
            <div class="rounded-lg border border-[#e4e4e7] bg-white px-4 py-3">
                <p class="text-[11px] font-medium uppercase tracking-wide text-[#a1a1aa]">Total</p>
                <p class="mt-1 text-[22px] font-semibold text-[#09090b]">{{ stats.total }}</p>
                <p class="text-[11px] text-[#a1a1aa]">{{ stats.week }} esta semana</p>
            </div>
            <div class="rounded-lg border border-[#e4e4e7] bg-white px-4 py-3">
                <p class="text-[11px] font-medium uppercase tracking-wide text-[#a1a1aa]">Hoy</p>
                <p class="mt-1 text-[22px] font-semibold text-[#09090b]">{{ stats.today }}</p>
            </div>
            <div class="rounded-lg border border-[#e4e4e7] bg-white px-4 py-3">
                <p class="text-[11px] font-medium uppercase tracking-wide text-[#a1a1aa]">Score medio</p>
                <p class="mt-1 text-[22px] font-semibold" :class="scoreColor(stats.avg_score)">{{ stats.avg_score }}</p>
            </div>
            <div class="rounded-lg border border-[#e4e4e7] bg-white px-4 py-3">
                <p class="text-[11px] font-medium uppercase tracking-wide text-[#a1a1aa]">Cualificados</p>
                <p class="mt-1 text-[22px] font-semibold text-emerald-600">{{ stats.qualified }}</p>
            </div>
            <div class="rounded-lg border border-[#e4e4e7] bg-white px-4 py-3">
                <p class="text-[11px] font-medium uppercase tracking-wide text-[#a1a1aa]">Enviados</p>
                <p class="mt-1 text-[22px] font-semibold text-blue-600">{{ stats.sent }}</p>
            </div>
            <div class="rounded-lg border border-[#e4e4e7] bg-white px-4 py-3">
                <p class="text-[11px] font-medium uppercase tracking-wide text-[#a1a1aa]">Revenue est.</p>
                <p class="mt-1 font-mono text-[20px] font-semibold text-emerald-600">${{ stats.total_revenue.toFixed(0) }}</p>
            </div>
            <div class="rounded-lg border border-[#e4e4e7] bg-white px-4 py-3">
                <p class="text-[11px] font-medium uppercase tracking-wide text-[#a1a1aa]">Score dist.</p>
                <div class="mt-1.5 flex h-4 gap-px overflow-hidden rounded-full">
                    <div class="bg-emerald-500" :style="{ width: `${stats.total > 0 ? (scoreDistribution.high / stats.total) * 100 : 0}%` }" :title="`Alto: ${scoreDistribution.high}`" />
                    <div class="bg-amber-500" :style="{ width: `${stats.total > 0 ? (scoreDistribution.medium / stats.total) * 100 : 0}%` }" :title="`Medio: ${scoreDistribution.medium}`" />
                    <div class="bg-red-400" :style="{ width: `${stats.total > 0 ? (scoreDistribution.low / stats.total) * 100 : 0}%` }" :title="`Bajo: ${scoreDistribution.low}`" />
                </div>
                <p class="mt-1 text-[10px] text-[#a1a1aa]">{{ scoreDistribution.high }} alto · {{ scoreDistribution.medium }} medio · {{ scoreDistribution.low }} bajo</p>
            </div>
        </div>

        <!-- ===== MAIN GRID ===== -->
        <div class="mt-6 grid grid-cols-1 gap-5 lg:grid-cols-12">

            <!-- LEFT: Lead list (8 cols) -->
            <div class="lg:col-span-8">
                <!-- Filters row -->
                <div class="flex flex-wrap items-center gap-3">
                    <!-- Status tabs -->
                    <div class="flex items-center gap-1">
                        <button
                            :class="['rounded-md border px-2.5 py-1 text-[11px] font-medium transition', activeStatus === 'all' ? 'border-[#09090b] bg-[#09090b] text-white' : 'border-[#e4e4e7] text-[#71717a] hover:border-[#a1a1aa]']"
                            @click="filterStatus('all')"
                        >Todos</button>
                        <button
                            v-for="(label, key) in statusLabels"
                            :key="key"
                            :class="['flex items-center gap-1 rounded-md border px-2.5 py-1 text-[11px] font-medium transition', activeStatus === key ? 'border-[#09090b] bg-[#09090b] text-white' : 'border-[#e4e4e7] text-[#71717a] hover:border-[#a1a1aa]']"
                            @click="filterStatus(key)"
                        >
                            <span :class="['h-[5px] w-[5px] rounded-full', activeStatus === key ? 'bg-white' : statusDot[key]]" />
                            {{ label }}
                        </button>
                    </div>

                    <!-- Score filter -->
                    <div class="flex items-center gap-1">
                        <span class="text-[11px] text-[#a1a1aa]">Score:</span>
                        <button
                            v-for="(label, min) in { '70': '≥70', '40': '≥40' }"
                            :key="min"
                            :class="['rounded-md border px-2 py-1 text-[11px] font-medium transition', filters.score_min === min ? 'border-[#09090b] bg-[#09090b] text-white' : 'border-[#e4e4e7] text-[#71717a] hover:border-[#a1a1aa]']"
                            @click="filterScore(min)"
                        >{{ label }}</button>
                    </div>

                    <button v-if="hasFilters" class="text-[11px] text-[#71717a] hover:text-[#09090b]" @click="clearFilters">Limpiar</button>
                </div>

                <!-- Table -->
                <div class="mt-4 overflow-hidden rounded-lg border border-[#e4e4e7] bg-white">
                    <table class="w-full text-[13px]">
                        <thead>
                            <tr class="border-b border-[#e4e4e7]">
                                <th class="px-4 py-2.5 text-left text-[11px] font-medium uppercase tracking-wide text-[#a1a1aa]">Score</th>
                                <th class="px-4 py-2.5 text-left text-[11px] font-medium uppercase tracking-wide text-[#a1a1aa]">Estado</th>
                                <th class="px-4 py-2.5 text-left text-[11px] font-medium uppercase tracking-wide text-[#a1a1aa]">Activo</th>
                                <th class="hidden px-4 py-2.5 text-left text-[11px] font-medium uppercase tracking-wide text-[#a1a1aa] sm:table-cell">Provider</th>
                                <th class="px-4 py-2.5 text-right text-[11px] font-medium uppercase tracking-wide text-[#a1a1aa]">Hace</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="lead in leads.data"
                                :key="lead.id"
                                :class="['border-b border-[#f4f4f5] last:border-0 cursor-pointer transition', expandedLead === lead.id ? 'bg-[#fafafa]' : 'hover:bg-[#fafafa]']"
                                @click="expandedLead = expandedLead === lead.id ? null : lead.id"
                            >
                                <td class="px-4 py-2.5">
                                    <div class="flex items-center gap-2">
                                        <div class="h-1.5 w-8 overflow-hidden rounded-full bg-[#f4f4f5]">
                                            <div :class="['h-full rounded-full', scoreBg(lead.score)]" :style="{ width: `${lead.score}%` }" />
                                        </div>
                                        <span :class="['font-mono text-[13px] font-semibold', scoreColor(lead.score)]">{{ lead.score }}</span>
                                    </div>
                                </td>
                                <td class="px-4 py-2.5">
                                    <span :class="['inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[10px] font-medium', statusBg[lead.status] ?? 'bg-gray-100 text-gray-500']">
                                        {{ statusLabels[lead.status] ?? lead.status }}
                                    </span>
                                </td>
                                <td class="px-4 py-2.5 text-[#09090b]">{{ lead.asset }}</td>
                                <td class="hidden px-4 py-2.5 text-[#71717a] sm:table-cell">{{ lead.provider || '—' }}</td>
                                <td class="px-4 py-2.5 text-right text-[12px] text-[#a1a1aa]">{{ timeAgo(lead.created_at) }}</td>
                            </tr>
                            <tr v-if="leads.data.length === 0">
                                <td colspan="5" class="px-4 py-10 text-center text-[#a1a1aa]">Sin leads</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Expanded lead detail -->
                <div v-if="expandedLead" class="mt-2 rounded-lg border border-[#e4e4e7] bg-white px-5 py-3">
                    <template v-for="lead in leads.data" :key="lead.id">
                        <div v-if="lead.id === expandedLead" class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                            <div v-if="lead.data?.name">
                                <p class="text-[10px] font-medium uppercase tracking-wide text-[#a1a1aa]">Nombre</p>
                                <p class="mt-0.5 text-[12px] text-[#09090b]">{{ lead.data.name }}</p>
                            </div>
                            <div v-if="lead.data?.email">
                                <p class="text-[10px] font-medium uppercase tracking-wide text-[#a1a1aa]">Email</p>
                                <p class="mt-0.5 text-[12px] text-[#09090b]">{{ lead.data.email }}</p>
                            </div>
                            <div v-if="lead.data?.phone">
                                <p class="text-[10px] font-medium uppercase tracking-wide text-[#a1a1aa]">Teléfono</p>
                                <p class="mt-0.5 text-[12px] text-[#09090b]">{{ lead.data.phone }}</p>
                            </div>
                            <div>
                                <p class="text-[10px] font-medium uppercase tracking-wide text-[#a1a1aa]">Scoring</p>
                                <p class="mt-0.5 text-[12px] text-[#71717a]">
                                    {{ lead.score >= 70 ? 'Auto → cualificado' : lead.score >= 40 ? 'Revisión humana' : 'Auto → descarte' }}
                                </p>
                            </div>
                        </div>
                    </template>
                </div>

                <!-- Pagination -->
                <div v-if="leads.last_page > 1" class="mt-4 flex items-center justify-between text-[12px]">
                    <span class="text-[#a1a1aa]">{{ leads.total }} leads</span>
                    <div class="flex gap-0.5">
                        <button
                            v-for="page in leads.last_page"
                            :key="page"
                            :class="['h-7 w-7 rounded-md font-medium transition', page === leads.current_page ? 'bg-[#09090b] text-white' : 'text-[#71717a] hover:bg-[#f4f4f5]']"
                            @click="router.get('/leads', { ...filters, page }, { preserveState: true })"
                        >{{ page }}</button>
                    </div>
                </div>
            </div>

            <!-- RIGHT: Analytics (4 cols) -->
            <div class="space-y-5 lg:col-span-4">
                <!-- Pipeline chart -->
                <div class="rounded-lg border border-[#e4e4e7] bg-white px-5 py-4">
                    <h3 class="text-[13px] font-medium text-[#09090b]">Pipeline</h3>
                    <p class="text-[11px] text-[#a1a1aa]">Distribución por estado</p>
                    <LeadsPieChart :data="pipelineCounts" />
                </div>

                <!-- Revenue by asset -->
                <div class="rounded-lg border border-[#e4e4e7] bg-white px-5 py-4">
                    <h3 class="mb-1 text-[13px] font-medium text-[#09090b]">Revenue por activo</h3>
                    <p class="mb-3 font-mono text-[20px] font-semibold text-emerald-600">${{ stats.total_revenue.toFixed(2) }}</p>
                    <div class="space-y-0">
                        <div v-for="r in revenueByAsset" :key="r.domain" class="flex items-center justify-between border-b border-[#f4f4f5] py-2 last:border-0">
                            <div>
                                <p class="text-[12px] font-medium text-[#09090b]">{{ r.domain }}</p>
                                <p class="text-[10px] text-[#a1a1aa]">{{ r.sent }} enviados × ${{ r.cpl }}</p>
                            </div>
                            <span class="font-mono text-[13px] font-semibold text-[#09090b]">${{ r.revenue.toFixed(0) }}</span>
                        </div>
                    </div>
                </div>

                <!-- By asset breakdown -->
                <div class="rounded-lg border border-[#e4e4e7] bg-white px-5 py-4">
                    <h3 class="mb-3 text-[13px] font-medium text-[#09090b]">Leads por activo</h3>
                    <div class="space-y-3">
                        <div v-for="(statuses, asset) in byAsset" :key="asset">
                            <div class="flex items-center justify-between">
                                <p class="text-[12px] font-medium text-[#09090b]">{{ asset }}</p>
                                <span class="text-[11px] text-[#a1a1aa]">{{ Object.values(statuses).reduce((a: number, b: number) => a + b, 0) }}</span>
                            </div>
                            <div class="mt-1 flex h-1.5 gap-px overflow-hidden rounded-full">
                                <div v-for="(count, st) in statuses" :key="st" :class="statusDot[st as string] ?? 'bg-[#d4d4d8]'" :style="{ flex: count }" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
