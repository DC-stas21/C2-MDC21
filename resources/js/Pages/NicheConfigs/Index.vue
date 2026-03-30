<script setup lang="ts">
import { ref, computed } from 'vue';
import { useI18n } from 'vue-i18n';
import AppLayout from '@/Layouts/AppLayout.vue';
import ScoreGaugeChart from '@/Components/Charts/ScoreGaugeChart.vue';

const { t } = useI18n();

type Asset = {
    id: string; domain: string; vertical: string; cpl: string;
    is_active: boolean; score: number | null; classification: string | null;
    config: Record<string, unknown>; colors: Record<string, string> | null;
    posts_total: number; posts_published: number; created_at: string;
};

const props = defineProps<{
    assets: Asset[];
    stats: {
        total: number; active: number; inactive: number;
        total_posts: number; avg_cpl: number | null;
    };
    scoreDistribution: Record<string, number>;
}>();

const selectedAsset = ref<string | null>(null);
const viewMode = ref<'cards' | 'table'>('cards');

const selected = computed(() =>
    selectedAsset.value ? props.assets.find((a) => a.id === selectedAsset.value) : null
);

const avgScore = computed(() => {
    const scores = props.assets.filter((a) => a.score !== null && a.is_active).map((a) => a.score!);
    return scores.length > 0 ? Math.round(scores.reduce((a, b) => a + b, 0) / scores.length * 10) / 10 : null;
});

function scoreColor(c: string | null): string {
    const m: Record<string, string> = {
        excellent: 'text-emerald-600', good: 'text-blue-600', average: 'text-amber-600',
        poor: 'text-orange-600', critical: 'text-red-600',
    };
    return m[c ?? ''] ?? 'text-[#a1a1aa]';
}

function scoreBg(c: string | null): string {
    const m: Record<string, string> = {
        excellent: 'bg-emerald-50 text-emerald-700', good: 'bg-blue-50 text-blue-700',
        average: 'bg-amber-50 text-amber-700', poor: 'bg-orange-50 text-orange-700',
        critical: 'bg-red-50 text-red-700',
    };
    return m[c ?? ''] ?? 'bg-[#f4f4f5] text-[#71717a]';
}

function daysSince(dateStr: string): string {
    const days = Math.floor((Date.now() - new Date(dateStr).getTime()) / 86400000);
    if (days === 0) return 'hoy';
    if (days === 1) return 'ayer';
    return `hace ${days}d`;
}
</script>

<template>
    <AppLayout>
        <template #header>
            <h1 class="text-[14px] font-medium text-[#09090b]">{{ t('nav.assets') }}</h1>
        </template>

        <!-- ===== TOP METRICS ===== -->
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5">
            <div class="rounded-lg border border-[#e4e4e7] bg-white px-4 py-3">
                <p class="text-[11px] font-medium uppercase tracking-wide text-[#a1a1aa]">Activos</p>
                <p class="mt-1 text-[22px] font-semibold text-[#09090b]">{{ stats.active }}</p>
                <p v-if="stats.inactive > 0" class="text-[11px] text-[#a1a1aa]">{{ stats.inactive }} inactivos</p>
            </div>
            <div class="rounded-lg border border-[#e4e4e7] bg-white px-4 py-3">
                <p class="text-[11px] font-medium uppercase tracking-wide text-[#a1a1aa]">Score medio</p>
                <p class="mt-1 text-[22px] font-semibold" :class="avgScore !== null ? 'text-[#09090b]' : 'text-[#d4d4d8]'">{{ avgScore ?? '—' }}</p>
            </div>
            <div class="rounded-lg border border-[#e4e4e7] bg-white px-4 py-3">
                <p class="text-[11px] font-medium uppercase tracking-wide text-[#a1a1aa]">CPL medio</p>
                <p class="mt-1 font-mono text-[20px] font-semibold text-[#09090b]">${{ stats.avg_cpl ? Number(stats.avg_cpl).toFixed(2) : '—' }}</p>
            </div>
            <div class="rounded-lg border border-[#e4e4e7] bg-white px-4 py-3">
                <p class="text-[11px] font-medium uppercase tracking-wide text-[#a1a1aa]">Contenido</p>
                <p class="mt-1 text-[22px] font-semibold text-[#09090b]">{{ stats.total_posts }}</p>
                <p class="text-[11px] text-[#a1a1aa]">artículos</p>
            </div>
            <div class="rounded-lg border border-[#e4e4e7] bg-white px-4 py-3">
                <p class="text-[11px] font-medium uppercase tracking-wide text-[#a1a1aa]">Salud</p>
                <div class="mt-1 flex gap-1">
                    <span v-for="(count, cls) in scoreDistribution" :key="cls" :title="cls" :class="['h-5 rounded-sm', count > 0 ? {
                        excellent: 'bg-emerald-500', good: 'bg-blue-500', average: 'bg-amber-500',
                        poor: 'bg-orange-500', critical: 'bg-red-500', no_data: 'bg-[#e4e4e7]'
                    }[cls] : 'bg-transparent']" :style="{ width: count > 0 ? `${Math.max(count * 20, 8)}px` : '0' }" />
                </div>
                <p class="mt-1 text-[11px] text-[#a1a1aa]">distribución de scores</p>
            </div>
        </div>

        <!-- ===== MAIN GRID ===== -->
        <div class="mt-6 grid grid-cols-1 gap-5 lg:grid-cols-12">

            <!-- LEFT: Assets (8 cols) -->
            <div class="lg:col-span-8">
                <div class="mb-3 flex items-center justify-between">
                    <h3 class="text-[13px] font-medium text-[#09090b]">
                        Portafolio
                        <span class="text-[11px] font-normal text-[#a1a1aa]">— click en un activo para ver detalle</span>
                    </h3>
                    <div class="flex rounded-md border border-[#e4e4e7] p-0.5">
                        <button
                            :class="['rounded px-2 py-0.5 text-[11px] font-medium transition', viewMode === 'cards' ? 'bg-[#09090b] text-white' : 'text-[#71717a]']"
                            @click="viewMode = 'cards'"
                        >Cards</button>
                        <button
                            :class="['rounded px-2 py-0.5 text-[11px] font-medium transition', viewMode === 'table' ? 'bg-[#09090b] text-white' : 'text-[#71717a]']"
                            @click="viewMode = 'table'"
                        >Tabla</button>
                    </div>
                </div>

                <!-- CARDS VIEW -->
                <div v-if="viewMode === 'cards'" class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                    <div
                        v-for="asset in assets"
                        :key="asset.id"
                        :class="[
                            'cursor-pointer rounded-lg border bg-white p-4 transition',
                            selectedAsset === asset.id ? 'border-[#09090b] ring-1 ring-[#09090b]' : 'border-[#e4e4e7] hover:border-[#a1a1aa]',
                            !asset.is_active ? 'opacity-50' : '',
                        ]"
                        @click="selectedAsset = selectedAsset === asset.id ? null : asset.id"
                    >
                        <div class="flex items-start justify-between">
                            <div>
                                <h4 class="text-[14px] font-semibold text-[#09090b]">{{ asset.domain }}</h4>
                                <p class="mt-0.5 text-[12px] text-[#a1a1aa]">{{ asset.vertical }}</p>
                            </div>
                            <div class="flex items-center gap-1.5">
                                <span :class="['h-2 w-2 rounded-full', asset.is_active ? 'bg-emerald-500' : 'bg-[#d4d4d8]']" />
                                <span class="text-[11px]" :class="asset.is_active ? 'text-emerald-600' : 'text-[#a1a1aa]'">
                                    {{ asset.is_active ? 'Activo' : 'Inactivo' }}
                                </span>
                            </div>
                        </div>

                        <div class="mt-3 grid grid-cols-3 gap-3 border-t border-[#f4f4f5] pt-3">
                            <div>
                                <p class="text-[10px] font-medium uppercase tracking-wide text-[#a1a1aa]">Score</p>
                                <div v-if="asset.score !== null" class="mt-0.5 flex items-center gap-1">
                                    <span class="text-[15px] font-semibold text-[#09090b]">{{ asset.score }}</span>
                                    <span :class="['rounded px-1 py-px text-[9px] font-medium', scoreBg(asset.classification)]">{{ asset.classification }}</span>
                                </div>
                                <span v-else class="mt-0.5 text-[15px] text-[#d4d4d8]">—</span>
                            </div>
                            <div>
                                <p class="text-[10px] font-medium uppercase tracking-wide text-[#a1a1aa]">CPL</p>
                                <p class="mt-0.5 font-mono text-[15px] font-semibold text-[#09090b]">${{ asset.cpl }}</p>
                            </div>
                            <div>
                                <p class="text-[10px] font-medium uppercase tracking-wide text-[#a1a1aa]">Posts</p>
                                <p class="mt-0.5 text-[15px] font-semibold text-[#09090b]">{{ asset.posts_total }}</p>
                                <p v-if="asset.posts_published > 0" class="text-[10px] text-emerald-600">{{ asset.posts_published }} publ.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- TABLE VIEW -->
                <div v-else class="overflow-hidden rounded-lg border border-[#e4e4e7] bg-white">
                    <table class="w-full text-[13px]">
                        <thead>
                            <tr class="border-b border-[#e4e4e7]">
                                <th class="px-4 py-2.5 text-left text-[11px] font-medium uppercase tracking-wide text-[#a1a1aa]">Dominio</th>
                                <th class="px-4 py-2.5 text-left text-[11px] font-medium uppercase tracking-wide text-[#a1a1aa]">Vertical</th>
                                <th class="px-4 py-2.5 text-left text-[11px] font-medium uppercase tracking-wide text-[#a1a1aa]">Score</th>
                                <th class="hidden px-4 py-2.5 text-left text-[11px] font-medium uppercase tracking-wide text-[#a1a1aa] sm:table-cell">CPL</th>
                                <th class="hidden px-4 py-2.5 text-left text-[11px] font-medium uppercase tracking-wide text-[#a1a1aa] md:table-cell">Posts</th>
                                <th class="px-4 py-2.5 text-right text-[11px] font-medium uppercase tracking-wide text-[#a1a1aa]">Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="asset in assets"
                                :key="asset.id"
                                :class="['border-b border-[#f4f4f5] last:border-0 cursor-pointer transition', selectedAsset === asset.id ? 'bg-[#f4f4f5]' : 'hover:bg-[#fafafa]']"
                                @click="selectedAsset = selectedAsset === asset.id ? null : asset.id"
                            >
                                <td class="px-4 py-2.5 font-medium text-[#09090b]">{{ asset.domain }}</td>
                                <td class="px-4 py-2.5 text-[#71717a]">{{ asset.vertical }}</td>
                                <td class="px-4 py-2.5">
                                    <span v-if="asset.score !== null" class="inline-flex items-center gap-1">
                                        <span class="font-semibold text-[#09090b]">{{ asset.score }}</span>
                                        <span :class="['rounded px-1 py-px text-[9px] font-medium', scoreBg(asset.classification)]">{{ asset.classification }}</span>
                                    </span>
                                    <span v-else class="text-[#d4d4d8]">—</span>
                                </td>
                                <td class="hidden px-4 py-2.5 font-mono text-[#09090b] sm:table-cell">${{ asset.cpl }}</td>
                                <td class="hidden px-4 py-2.5 text-[#71717a] md:table-cell">{{ asset.posts_total }}</td>
                                <td class="px-4 py-2.5 text-right">
                                    <span class="inline-flex items-center gap-1 text-[12px]" :class="asset.is_active ? 'text-emerald-600' : 'text-[#a1a1aa]'">
                                        <span :class="['h-1.5 w-1.5 rounded-full', asset.is_active ? 'bg-emerald-500' : 'bg-[#d4d4d8]']" />
                                        {{ asset.is_active ? 'Activo' : 'Inactivo' }}
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Selected asset detail -->
                <div v-if="selected" class="mt-4 rounded-lg border border-[#09090b] bg-white px-5 py-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-[15px] font-semibold text-[#09090b]">{{ selected.domain }}</h3>
                            <p class="text-[12px] text-[#a1a1aa]">{{ selected.vertical }} · Creado {{ daysSince(selected.created_at) }}</p>
                        </div>
                        <button class="text-[12px] text-[#a1a1aa] hover:text-[#09090b]" @click="selectedAsset = null">Cerrar</button>
                    </div>
                    <div class="mt-3 grid grid-cols-2 gap-4 border-t border-[#f4f4f5] pt-3 sm:grid-cols-4">
                        <div>
                            <p class="text-[10px] font-medium uppercase tracking-wide text-[#a1a1aa]">Score</p>
                            <p class="mt-0.5 text-[18px] font-semibold" :class="scoreColor(selected.classification)">{{ selected.score ?? '—' }}</p>
                        </div>
                        <div>
                            <p class="text-[10px] font-medium uppercase tracking-wide text-[#a1a1aa]">CPL</p>
                            <p class="mt-0.5 font-mono text-[18px] font-semibold text-[#09090b]">${{ selected.cpl }}</p>
                        </div>
                        <div>
                            <p class="text-[10px] font-medium uppercase tracking-wide text-[#a1a1aa]">Posts</p>
                            <p class="mt-0.5 text-[18px] font-semibold text-[#09090b]">{{ selected.posts_total }}</p>
                            <p class="text-[10px] text-[#a1a1aa]">{{ selected.posts_published }} publicados</p>
                        </div>
                        <div>
                            <p class="text-[10px] font-medium uppercase tracking-wide text-[#a1a1aa]">Estado</p>
                            <p class="mt-0.5 text-[18px] font-semibold" :class="selected.is_active ? 'text-emerald-600' : 'text-[#a1a1aa]'">
                                {{ selected.is_active ? 'Activo' : 'Inactivo' }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- RIGHT (4 cols) -->
            <div class="space-y-5 lg:col-span-4">
                <div class="rounded-lg border border-[#e4e4e7] bg-white px-5 py-4">
                    <h3 class="text-[13px] font-medium text-[#09090b]">Score del portafolio</h3>
                    <p class="text-[11px] text-[#a1a1aa]">Promedio de {{ stats.active }} activos</p>
                    <ScoreGaugeChart :score="avgScore" />
                </div>
            </div>
        </div>
    </AppLayout>
</template>
