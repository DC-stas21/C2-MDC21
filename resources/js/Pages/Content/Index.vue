<script setup lang="ts">
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import AppLayout from '@/Layouts/AppLayout.vue';
import type { PaginatedResponse } from '@/types';

const { t } = useI18n();

type Post = {
    id: string; title: string; slug: string; author: string | null;
    body: string; sources: string[] | null; methodology: string | null;
    status: string; asset: string; published_at: string | null; created_at: string;
};

const props = defineProps<{
    posts: PaginatedResponse<Post>;
    filters: { status?: string; asset?: string };
    stats: {
        total: number; published: number; pending_review: number;
        draft: number; rejected: number; published_this_week: number;
    };
    statusCounts: Record<string, number>;
    assetCounts: Record<string, number>;
}>();

const activeTab = ref(props.filters.status || 'all');
const expandedPost = ref<string | null>(null);

const statusTabs = [
    { key: 'all', label: 'Todos' },
    { key: 'draft', label: 'Borradores' },
    { key: 'pending_review', label: 'En revisión' },
    { key: 'published', label: 'Publicados' },
    { key: 'rejected', label: 'Rechazados' },
];

function switchTab(status: string) {
    activeTab.value = status;
    const params: Record<string, string> = {};
    if (status !== 'all') params.status = status;
    if (props.filters.asset) params.asset = props.filters.asset;
    router.get('/content', params, { preserveState: true, preserveScroll: true });
}

function filterAsset(asset: string) {
    const params: Record<string, string> = {};
    if (activeTab.value !== 'all') params.status = activeTab.value;
    if (props.filters.asset !== asset) params.asset = asset;
    router.get('/content', params, { preserveState: true, preserveScroll: true });
}

function clearFilters() {
    activeTab.value = 'all';
    router.get('/content', {}, { preserveState: true, preserveScroll: true });
}

const statusStyle: Record<string, { dot: string; label: string; bg: string }> = {
    draft: { dot: 'bg-[#d4d4d8]', label: 'Borrador', bg: 'bg-gray-50 text-gray-600' },
    pending_review: { dot: 'bg-amber-500', label: 'En revisión', bg: 'bg-amber-50 text-amber-700' },
    published: { dot: 'bg-emerald-500', label: 'Publicado', bg: 'bg-emerald-50 text-emerald-700' },
    rejected: { dot: 'bg-red-500', label: 'Rechazado', bg: 'bg-red-50 text-red-700' },
};

function formatDate(dateStr: string | null): string {
    if (!dateStr) return '—';
    const d = new Date(dateStr);
    return `${d.getDate()}/${d.getMonth() + 1}/${d.getFullYear()}`;
}

function timeAgo(dateStr: string): string {
    const diff = Date.now() - new Date(dateStr).getTime();
    const days = Math.floor(diff / 86400000);
    if (days === 0) return 'hoy';
    if (days === 1) return 'ayer';
    if (days < 7) return `hace ${days}d`;
    return `hace ${Math.floor(days / 7)}sem`;
}
</script>

<template>
    <AppLayout>
        <template #header>
            <h1 class="text-[14px] font-medium text-[#09090b]">{{ t('nav.content') }}</h1>
        </template>

        <!-- ===== METRICS ===== -->
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-6">
            <div class="rounded-lg border border-[#e4e4e7] bg-white px-4 py-3">
                <p class="text-[11px] font-medium uppercase tracking-wide text-[#a1a1aa]">Total</p>
                <p class="mt-1 text-[22px] font-semibold text-[#09090b]">{{ stats.total }}</p>
                <p class="text-[11px] text-[#a1a1aa]">artículos</p>
            </div>
            <div class="rounded-lg border border-[#e4e4e7] bg-white px-4 py-3">
                <p class="text-[11px] font-medium uppercase tracking-wide text-[#a1a1aa]">Publicados</p>
                <p class="mt-1 text-[22px] font-semibold text-emerald-600">{{ stats.published }}</p>
                <p class="text-[11px] text-[#a1a1aa]">{{ stats.published_this_week }} esta semana</p>
            </div>
            <div class="rounded-lg border border-[#e4e4e7] bg-white px-4 py-3">
                <p class="text-[11px] font-medium uppercase tracking-wide text-[#a1a1aa]">En revisión</p>
                <p class="mt-1 text-[22px] font-semibold" :class="stats.pending_review > 0 ? 'text-amber-600' : 'text-[#09090b]'">{{ stats.pending_review }}</p>
            </div>
            <div class="rounded-lg border border-[#e4e4e7] bg-white px-4 py-3">
                <p class="text-[11px] font-medium uppercase tracking-wide text-[#a1a1aa]">Borradores</p>
                <p class="mt-1 text-[22px] font-semibold text-[#09090b]">{{ stats.draft }}</p>
            </div>
            <div class="rounded-lg border border-[#e4e4e7] bg-white px-4 py-3">
                <p class="text-[11px] font-medium uppercase tracking-wide text-[#a1a1aa]">Rechazados</p>
                <p class="mt-1 text-[22px] font-semibold" :class="stats.rejected > 0 ? 'text-red-600' : 'text-[#09090b]'">{{ stats.rejected }}</p>
            </div>
            <div class="rounded-lg border border-[#e4e4e7] bg-white px-4 py-3">
                <p class="text-[11px] font-medium uppercase tracking-wide text-[#a1a1aa]">Por activo</p>
                <div class="mt-1 flex flex-wrap gap-1">
                    <button
                        v-for="(count, asset) in assetCounts"
                        :key="asset"
                        :class="['rounded px-1.5 py-0.5 text-[10px] font-medium transition', filters.asset === asset ? 'bg-[#09090b] text-white' : 'bg-[#f4f4f5] text-[#71717a] hover:bg-[#e4e4e7]']"
                        @click="filterAsset(asset as string)"
                    >{{ (asset as string).split('.')[0] }} <span class="opacity-60">{{ count }}</span></button>
                </div>
            </div>
        </div>

        <!-- ===== MAIN ===== -->
        <div class="mt-6">
            <!-- Tabs -->
            <div class="flex items-center justify-between border-b border-[#e4e4e7]">
                <div class="-mb-px flex gap-0">
                    <button
                        v-for="tab in statusTabs"
                        :key="tab.key"
                        :class="['border-b-2 px-4 py-2 text-[13px] font-medium transition', activeTab === tab.key ? 'border-[#09090b] text-[#09090b]' : 'border-transparent text-[#a1a1aa] hover:text-[#71717a]']"
                        @click="switchTab(tab.key)"
                    >
                        {{ tab.label }}
                        <span v-if="tab.key !== 'all' && (statusCounts[tab.key] ?? 0) > 0" class="ml-1 text-[11px] opacity-50">{{ statusCounts[tab.key] }}</span>
                    </button>
                </div>
                <button v-if="filters.status || filters.asset" class="text-[12px] text-[#71717a] hover:text-[#09090b]" @click="clearFilters">Limpiar</button>
            </div>

            <!-- Posts list -->
            <div class="mt-4 space-y-1">
                <div v-if="posts.data.length === 0" class="rounded-lg border border-[#e4e4e7] bg-white py-12 text-center text-[13px] text-[#a1a1aa]">
                    Sin artículos para estos filtros
                </div>

                <div
                    v-for="post in posts.data"
                    :key="post.id"
                    class="rounded-lg border border-[#e4e4e7] bg-white transition hover:border-[#a1a1aa]"
                >
                    <div class="cursor-pointer px-5 py-3.5" @click="expandedPost = expandedPost === post.id ? null : post.id">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-2">
                                    <span :class="['h-2 w-2 shrink-0 rounded-full', statusStyle[post.status]?.dot ?? 'bg-[#d4d4d8]']" />
                                    <h3 class="truncate text-[14px] font-medium text-[#09090b]">{{ post.title }}</h3>
                                </div>
                                <div class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 pl-4 text-[11px] text-[#a1a1aa]">
                                    <span>{{ post.asset }}</span>
                                    <span v-if="post.author">{{ post.author }}</span>
                                    <span>{{ timeAgo(post.created_at) }}</span>
                                </div>
                            </div>
                            <div class="flex shrink-0 items-center gap-2">
                                <span :class="['rounded-full px-2 py-0.5 text-[10px] font-medium', statusStyle[post.status]?.bg ?? 'bg-gray-50 text-gray-600']">
                                    {{ statusStyle[post.status]?.label ?? post.status }}
                                </span>
                                <svg :class="['h-4 w-4 text-[#a1a1aa] transition-transform', expandedPost === post.id ? 'rotate-180' : '']" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                                </svg>
                            </div>
                        </div>
                    </div>

                    <!-- Expanded details -->
                    <div v-if="expandedPost === post.id" class="border-t border-[#f4f4f5] px-5 py-3.5">
                        <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                            <div>
                                <p class="text-[10px] font-medium uppercase tracking-wide text-[#a1a1aa]">Slug</p>
                                <p class="mt-0.5 font-mono text-[12px] text-[#09090b]">{{ post.slug }}</p>
                            </div>
                            <div>
                                <p class="text-[10px] font-medium uppercase tracking-wide text-[#a1a1aa]">Publicado</p>
                                <p class="mt-0.5 text-[12px] text-[#09090b]">{{ formatDate(post.published_at) }}</p>
                            </div>
                            <div>
                                <p class="text-[10px] font-medium uppercase tracking-wide text-[#a1a1aa]">Fuentes</p>
                                <p class="mt-0.5 text-[12px] text-[#09090b]">{{ post.sources?.join(', ') || '—' }}</p>
                            </div>
                            <div>
                                <p class="text-[10px] font-medium uppercase tracking-wide text-[#a1a1aa]">E-E-A-T</p>
                                <span :class="['mt-0.5 inline-block rounded px-1.5 py-0.5 text-[10px] font-medium', post.methodology ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-700']">
                                    {{ post.methodology ? 'Metodología definida' : 'Sin metodología' }}
                                </span>
                            </div>
                        </div>
                        <div v-if="post.methodology" class="mt-2 rounded bg-[#fafafa] px-3 py-2 text-[12px] text-[#71717a]">
                            {{ post.methodology }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pagination -->
            <div v-if="posts.last_page > 1" class="mt-4 flex items-center justify-between text-[12px]">
                <span class="text-[#a1a1aa]">{{ posts.total }} artículos</span>
                <div class="flex gap-0.5">
                    <button
                        v-for="page in posts.last_page"
                        :key="page"
                        :class="['h-7 w-7 rounded-md font-medium transition', page === posts.current_page ? 'bg-[#09090b] text-white' : 'text-[#71717a] hover:bg-[#f4f4f5]']"
                        @click="router.get('/content', { ...filters, page }, { preserveState: true })"
                    >{{ page }}</button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
