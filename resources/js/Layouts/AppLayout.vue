<script setup lang="ts">
import { ref, computed } from 'vue';
import { Link, usePage, router } from '@inertiajs/vue3';
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

function logout() {
    router.post('/logout');
}

const nav = [
    { name: 'nav.dashboard', href: '/', icon: 'M4 5a1 1 0 011-1h4a1 1 0 011 1v5a1 1 0 01-1 1H5a1 1 0 01-1-1V5zm10-1a1 1 0 00-1 1v3a1 1 0 001 1h5a1 1 0 001-1V5a1 1 0 00-1-1h-5zm-10 9a1 1 0 011-1h4a1 1 0 011 1v3a1 1 0 01-1 1H5a1 1 0 01-1-1v-3zm10 0a1 1 0 011-1h4a1 1 0 011 1v5a1 1 0 01-1 1h-4a1 1 0 01-1-1v-5z' },
    { name: 'nav.agents', href: '/agent-runs', icon: 'M5 12h14M12 5l7 7-7 7' },
    { name: 'nav.approvals', href: '/approvals', icon: 'M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z' },
    { name: 'nav.assets', href: '/assets', icon: 'M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z' },
];
</script>

<template>
    <div class="flex min-h-screen bg-[#09090b] text-[#fafafa]">
        <!-- Mobile overlay -->
        <div v-if="sidebarOpen" class="fixed inset-0 z-40 bg-black/50 lg:hidden" @click="sidebarOpen = false" />

        <!-- Sidebar -->
        <aside
            :class="[
                'fixed inset-y-0 left-0 z-50 flex w-[220px] flex-col border-r border-[#1f1f23] bg-[#0c0c0f] lg:translate-x-0',
                sidebarOpen ? 'translate-x-0' : '-translate-x-full',
            ]"
            style="transition: transform 150ms ease"
        >
            <div class="flex h-12 items-center px-4">
                <div class="flex items-center gap-2">
                    <div class="flex h-6 w-6 items-center justify-center rounded bg-white text-[10px] font-bold text-[#09090b]">C2</div>
                    <span class="text-[13px] font-medium text-white">MDC21</span>
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
                            ? 'bg-[#1f1f23] font-medium text-white'
                            : 'text-[#71717a] hover:bg-[#1f1f23] hover:text-white',
                    ]"
                    @click="sidebarOpen = false"
                >
                    <svg class="h-4 w-4 shrink-0" :class="isActive(item.href) ? 'text-white' : 'text-[#52525b]'" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" :d="item.icon" />
                    </svg>
                    {{ t(item.name) }}
                </Link>

                <!-- Separator -->
                <div class="my-3 border-t border-[#1f1f23]" />

                <!-- Filament link -->
                <a
                    href="/admin"
                    class="mb-px flex items-center gap-2 rounded-md px-2 py-[6px] text-[13px] text-[#71717a] transition-colors hover:bg-[#1f1f23] hover:text-white"
                >
                    <svg class="h-4 w-4 shrink-0 text-[#52525b]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.241-.438.613-.43.992a7.723 7.723 0 010 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.991a6.932 6.932 0 010-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.28z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    Configuración
                </a>
            </nav>

            <!-- User + logout -->
            <div class="border-t border-[#1f1f23] px-3 py-3">
                <div v-if="user" class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <div class="flex h-6 w-6 items-center justify-center rounded-full bg-[#1f1f23] text-[10px] font-medium text-[#71717a]">
                            {{ user.name?.charAt(0)?.toUpperCase() }}
                        </div>
                        <span class="truncate text-[12px] text-[#71717a]">{{ user.name }}</span>
                    </div>
                    <button class="text-[#52525b] hover:text-[#a1a1aa]" title="Cerrar sesión" @click="logout">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9" />
                        </svg>
                    </button>
                </div>
            </div>
        </aside>

        <!-- Main -->
        <div class="flex-1 lg:pl-[220px]">
            <header class="sticky top-0 z-30 flex h-12 items-center gap-3 border-b border-[#1f1f23] bg-[#09090b]/80 px-4 backdrop-blur-sm sm:px-6">
                <button class="text-[#52525b] hover:text-white lg:hidden" @click="sidebarOpen = true">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                    </svg>
                </button>
                <div class="flex-1"><slot name="header" /></div>
                <div class="flex items-center gap-1.5 text-[11px] text-[#52525b]">
                    <span class="h-[6px] w-[6px] rounded-full bg-emerald-500" />
                    Online
                </div>
            </header>

            <!-- Flash -->
            <div v-if="flash?.success" class="mx-4 mt-3 rounded-md border border-emerald-500/20 bg-emerald-500/10 px-3 py-2 text-[13px] text-emerald-400 sm:mx-6">
                {{ flash.success }}
            </div>
            <div v-if="flash?.error" class="mx-4 mt-3 rounded-md border border-red-500/20 bg-red-500/10 px-3 py-2 text-[13px] text-red-400 sm:mx-6">
                {{ flash.error }}
            </div>

            <main class="p-4 sm:p-6"><slot /></main>
        </div>
    </div>
</template>
