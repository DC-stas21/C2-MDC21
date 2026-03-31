<script setup lang="ts">
import { ref, computed } from 'vue';
import { useI18n } from 'vue-i18n';
import AppLayout from '@/Layouts/AppLayout.vue';

const { t } = useI18n();

type Asset = {
    id: string; domain: string; vertical: string;
    is_active: boolean; build_status: string;
    build_metadata: Record<string, any> | null;
    description: string; audience: string; tone: string;
    created_at: string;
};

const props = defineProps<{
    assets: Asset[];
    stats: {
        total: number; active: number; live: number;
        building: number; staging: number; failed: number; pending: number;
    };
}>();

const selectedAsset = ref<string | null>(null);
const selected = computed(() => selectedAsset.value ? props.assets.find((a) => a.id === selectedAsset.value) : null);

const statusStyle: Record<string, { label: string; dot: string; text: string; bg: string }> = {
    pending: { label: 'Pendiente', dot: 'bg-[#d4d4d8]', text: 'text-[#71717a]', bg: 'bg-gray-50 text-gray-600' },
    building: { label: 'Construyendo...', dot: 'bg-amber-500 animate-pulse', text: 'text-amber-600', bg: 'bg-amber-50 text-amber-700' },
    staging: { label: 'En staging', dot: 'bg-blue-500', text: 'text-blue-600', bg: 'bg-blue-50 text-blue-700' },
    live: { label: 'Publicado', dot: 'bg-emerald-500', text: 'text-emerald-600', bg: 'bg-emerald-50 text-emerald-700' },
    failed: { label: 'Error', dot: 'bg-red-500', text: 'text-red-600', bg: 'bg-red-50 text-red-700' },
};

function timeAgo(dateStr: string | null): string {
    if (!dateStr) return '—';
    const diff = Date.now() - new Date(dateStr).getTime();
    const mins = Math.floor(diff / 60000);
    if (mins < 1) return 'ahora';
    if (mins < 60) return `${mins}m`;
    const h = Math.floor(mins / 60);
    return h < 24 ? `${h}h` : `${Math.floor(h / 24)}d`;
}
</script>

<template>
    <AppLayout>
        <template #header>
            <h1 class="text-[14px] font-medium text-[#09090b]">Portafolio de webs</h1>
        </template>

        <!-- Metrics -->
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-4 lg:grid-cols-7">
            <div class="rounded-lg border border-[#e4e4e7] bg-white px-4 py-3">
                <p class="text-[11px] font-medium uppercase tracking-wide text-[#a1a1aa]">Total</p>
                <p class="mt-1 text-[22px] font-semibold text-[#09090b]">{{ stats.total }}</p>
            </div>
            <div class="rounded-lg border border-[#e4e4e7] bg-white px-4 py-3">
                <p class="text-[11px] font-medium uppercase tracking-wide text-[#a1a1aa]">Publicadas</p>
                <p class="mt-1 text-[22px] font-semibold text-emerald-600">{{ stats.live }}</p>
            </div>
            <div class="rounded-lg border border-[#e4e4e7] bg-white px-4 py-3">
                <p class="text-[11px] font-medium uppercase tracking-wide text-[#a1a1aa]">En staging</p>
                <p class="mt-1 text-[22px] font-semibold text-blue-600">{{ stats.staging }}</p>
            </div>
            <div class="rounded-lg border border-[#e4e4e7] bg-white px-4 py-3">
                <p class="text-[11px] font-medium uppercase tracking-wide text-[#a1a1aa]">Construyendo</p>
                <p class="mt-1 text-[22px] font-semibold text-amber-600">{{ stats.building }}</p>
            </div>
            <div class="rounded-lg border border-[#e4e4e7] bg-white px-4 py-3">
                <p class="text-[11px] font-medium uppercase tracking-wide text-[#a1a1aa]">Pendientes</p>
                <p class="mt-1 text-[22px] font-semibold text-[#09090b]">{{ stats.pending }}</p>
            </div>
            <div class="rounded-lg border border-[#e4e4e7] bg-white px-4 py-3">
                <p class="text-[11px] font-medium uppercase tracking-wide text-[#a1a1aa]">Con error</p>
                <p class="mt-1 text-[22px] font-semibold" :class="stats.failed > 0 ? 'text-red-600' : 'text-[#09090b]'">{{ stats.failed }}</p>
            </div>
            <div class="rounded-lg border border-[#e4e4e7] bg-white px-4 py-3">
                <p class="text-[11px] font-medium uppercase tracking-wide text-[#a1a1aa]">Activas</p>
                <p class="mt-1 text-[22px] font-semibold text-[#09090b]">{{ stats.active }}</p>
            </div>
        </div>

        <!-- Asset list -->
        <div class="mt-6">
            <h3 class="mb-3 text-[13px] font-medium text-[#09090b]">
                Webs
                <span class="text-[11px] font-normal text-[#a1a1aa]">— click para ver detalle</span>
            </h3>

            <div v-if="assets.length === 0" class="rounded-lg border border-[#e4e4e7] bg-white py-12 text-center text-[13px] text-[#a1a1aa]">
                Sin webs. Crea un activo en /admin → Activos.
            </div>

            <div v-else class="space-y-2">
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
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <span :class="['h-2.5 w-2.5 shrink-0 rounded-full', statusStyle[asset.build_status]?.dot ?? 'bg-[#d4d4d8]']" />
                            <div>
                                <p class="text-[14px] font-semibold text-[#09090b]">{{ asset.domain }}</p>
                                <p class="text-[12px] text-[#a1a1aa]">{{ asset.vertical }} · {{ asset.description?.substring(0, 80) }}</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <span :class="['rounded-full px-2 py-0.5 text-[10px] font-medium', statusStyle[asset.build_status]?.bg ?? 'bg-gray-50 text-gray-600']">
                                {{ statusStyle[asset.build_status]?.label ?? asset.build_status }}
                            </span>
                            <span class="text-[11px] text-[#a1a1aa]">{{ timeAgo(asset.created_at) }}</span>
                        </div>
                    </div>

                    <!-- Error -->
                    <div v-if="asset.build_metadata?.error && asset.build_status === 'failed'" class="mt-2 rounded bg-red-50 px-3 py-1.5 text-[11px] text-red-600">
                        {{ asset.build_metadata.error }}
                    </div>
                </div>
            </div>

            <!-- Selected detail -->
            <div v-if="selected" class="mt-4 rounded-lg border border-[#09090b] bg-white px-5 py-4">
                <div class="flex items-center justify-between">
                    <h3 class="text-[15px] font-semibold text-[#09090b]">{{ selected.domain }}</h3>
                    <button class="text-[12px] text-[#a1a1aa] hover:text-[#09090b]" @click="selectedAsset = null">Cerrar</button>
                </div>
                <div class="mt-3 grid grid-cols-2 gap-4 border-t border-[#f4f4f5] pt-3 sm:grid-cols-4">
                    <div>
                        <p class="text-[10px] font-medium uppercase tracking-wide text-[#a1a1aa]">Vertical</p>
                        <p class="mt-0.5 text-[13px] font-medium text-[#09090b]">{{ selected.vertical }}</p>
                    </div>
                    <div>
                        <p class="text-[10px] font-medium uppercase tracking-wide text-[#a1a1aa]">Estado</p>
                        <p :class="['mt-0.5 text-[13px] font-medium', statusStyle[selected.build_status]?.text]">
                            {{ statusStyle[selected.build_status]?.label }}
                        </p>
                    </div>
                    <div>
                        <p class="text-[10px] font-medium uppercase tracking-wide text-[#a1a1aa]">Audiencia</p>
                        <p class="mt-0.5 text-[12px] text-[#09090b]">{{ selected.audience || '—' }}</p>
                    </div>
                    <div>
                        <p class="text-[10px] font-medium uppercase tracking-wide text-[#a1a1aa]">Tono</p>
                        <p class="mt-0.5 text-[12px] text-[#09090b]">{{ selected.tone || '—' }}</p>
                    </div>
                </div>
                <div v-if="selected.description" class="mt-3 border-t border-[#f4f4f5] pt-3">
                    <p class="text-[10px] font-medium uppercase tracking-wide text-[#a1a1aa]">Descripción</p>
                    <p class="mt-0.5 text-[12px] text-[#71717a]">{{ selected.description }}</p>
                </div>
                <div v-if="selected.build_status === 'live'" class="mt-3 border-t border-[#f4f4f5] pt-3">
                    <a :href="`https://${selected.domain}`" target="_blank" class="text-[12px] font-medium text-indigo-600 hover:underline">
                        Visitar web →
                    </a>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
