<script setup lang="ts">
import { ref, computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import type { SharedProps } from '@/types';

const { t } = useI18n();
const page = usePage<SharedProps>();
const sidebarOpen = ref(false);
const user = computed(() => page.props.auth?.user);
const flash = computed(() => page.props.flash);
const currentPath = computed(() => page.url);

function isActive(href: string): boolean {
    if (href === '/') return currentPath.value === '/';
    return currentPath.value.startsWith(href);
}

const nav = [
    { name: 'nav.dashboard', href: '/', icon: 'M4 5a1 1 0 011-1h4a1 1 0 011 1v5a1 1 0 01-1 1H5a1 1 0 01-1-1V5zm10-1a1 1 0 00-1 1v3a1 1 0 001 1h5a1 1 0 001-1V5a1 1 0 00-1-1h-5zm-10 9a1 1 0 011-1h4a1 1 0 011 1v3a1 1 0 01-1 1H5a1 1 0 01-1-1v-3zm10 0a1 1 0 011-1h4a1 1 0 011 1v5a1 1 0 01-1 1h-4a1 1 0 01-1-1v-5z' },
    { name: 'nav.agents', href: '/agent-runs', icon: 'M5 12h14M12 5l7 7-7 7' },
    { name: 'nav.approvals', href: '/approvals', icon: 'M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z' },
    { name: 'nav.assets', href: '/assets', icon: 'M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z' },
    { name: 'nav.content', href: '/content', icon: 'M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z' },
    // Leads oculto hasta implementación futura
    // { name: 'nav.leads', href: '/leads', icon: 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z' },
];
</script>

<template>
    <div class="flex min-h-screen bg-[#fafafa]">
        <!-- Mobile overlay -->
        <div v-if="sidebarOpen" class="fixed inset-0 z-40 bg-black/20 lg:hidden" @click="sidebarOpen = false" />

        <!-- Sidebar -->
        <aside
            :class="[
                'fixed inset-y-0 left-0 z-50 flex w-[220px] flex-col bg-white lg:translate-x-0',
                sidebarOpen ? 'translate-x-0' : '-translate-x-full',
            ]"
            style="transition: transform 150ms ease"
        >
            <div class="flex h-12 items-center px-4">
                <div class="flex items-center gap-2">
                    <div class="flex h-6 w-6 items-center justify-center rounded bg-[#111] text-[10px] font-bold text-white">C2</div>
                    <span class="text-[13px] font-medium text-[#111]">MDC21</span>
                </div>
            </div>

            <nav class="flex-1 px-2 pt-1">
                <Link
                    v-for="item in nav"
                    :key="item.href"
                    :href="item.href"
                    :class="[
                        'mb-px flex items-center gap-2 rounded-md px-2 py-[6px] text-[13px] transition-colors',
                        isActive(item.href)
                            ? 'bg-[#f4f4f5] font-medium text-[#111]'
                            : 'text-[#71717a] hover:bg-[#f4f4f5] hover:text-[#111]',
                    ]"
                    @click="sidebarOpen = false"
                >
                    <svg class="h-4 w-4 shrink-0" :class="isActive(item.href) ? 'text-[#111]' : 'text-[#a1a1aa]'" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" :d="item.icon" />
                    </svg>
                    {{ t(item.name) }}
                </Link>
            </nav>

            <div class="border-t border-[#f4f4f5] px-3 py-3">
                <div v-if="user" class="flex items-center gap-2">
                    <div class="flex h-6 w-6 items-center justify-center rounded-full bg-[#f4f4f5] text-[10px] font-medium text-[#71717a]">
                        {{ user.name?.charAt(0)?.toUpperCase() }}
                    </div>
                    <span class="truncate text-[12px] text-[#71717a]">{{ user.name }}</span>
                </div>
            </div>
        </aside>

        <!-- Main -->
        <div class="flex-1 lg:pl-[220px]">
            <header class="sticky top-0 z-30 flex h-12 items-center gap-3 border-b border-[#e4e4e7] bg-white/80 px-4 backdrop-blur-sm sm:px-6">
                <button class="text-[#a1a1aa] hover:text-[#111] lg:hidden" @click="sidebarOpen = true">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                    </svg>
                </button>
                <div class="flex-1"><slot name="header" /></div>
                <div class="flex items-center gap-1.5 text-[11px] text-[#a1a1aa]">
                    <span class="h-[6px] w-[6px] rounded-full bg-emerald-500" />
                    Online
                </div>
            </header>

            <!-- Flash -->
            <div v-if="flash?.success" class="mx-4 mt-3 rounded-md border border-emerald-200 bg-emerald-50 px-3 py-2 text-[13px] text-emerald-800 sm:mx-6">
                {{ flash.success }}
            </div>
            <div v-if="flash?.error" class="mx-4 mt-3 rounded-md border border-red-200 bg-red-50 px-3 py-2 text-[13px] text-red-800 sm:mx-6">
                {{ flash.error }}
            </div>

            <main class="p-4 sm:p-6"><slot /></main>
        </div>
    </div>
</template>
