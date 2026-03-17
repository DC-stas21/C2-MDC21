<script setup lang="ts">
import { useI18n } from 'vue-i18n';

const { t } = useI18n();
</script>

<template>
    <div class="min-h-screen bg-gray-950 text-gray-100">
        <header class="border-b border-white/10 px-8 py-4">
            <h1 class="font-mono text-lg font-bold tracking-wide">
                <span class="rounded bg-indigo-600 px-2 py-0.5 text-sm">C2</span>
                <span class="ml-2 text-gray-400">MDC21 Agency</span>
            </h1>
        </header>

        <main class="p-8">
            <h2 class="mb-8 text-2xl font-semibold">{{ t('dashboard.title') }}</h2>

            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
                <div class="rounded-xl border border-white/10 bg-white/5 p-6">
                    <p class="text-sm text-gray-400">{{ t('dashboard.agents_active') }}</p>
                    <p class="mt-2 font-mono text-3xl font-bold">—</p>
                </div>
                <div class="rounded-xl border border-white/10 bg-white/5 p-6">
                    <p class="text-sm text-gray-400">{{ t('dashboard.approvals_pending') }}</p>
                    <p class="mt-2 font-mono text-3xl font-bold">—</p>
                </div>
                <div class="rounded-xl border border-white/10 bg-white/5 p-6">
                    <p class="text-sm text-gray-400">{{ t('dashboard.assets_total') }}</p>
                    <p class="mt-2 font-mono text-3xl font-bold">—</p>
                </div>
                <div class="rounded-xl border border-white/10 bg-white/5 p-6">
                    <p class="text-sm text-gray-400">{{ t('dashboard.score_avg') }}</p>
                    <p class="mt-2 font-mono text-3xl font-bold">—</p>
                </div>
            </div>
        </main>
    </div>
</template>
