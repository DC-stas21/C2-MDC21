<script setup lang="ts">
import { ref } from 'vue';
import { useConfig } from '@/composables/useConfig';

const config = useConfig();
const mobileOpen = ref(false);
</script>

<template>
    <header class="border-b" style="border-color: var(--site-color-surface)">
        <div class="mx-auto flex h-16 max-w-6xl items-center justify-between px-4 sm:px-6">
            <router-link to="/" class="text-lg font-semibold" style="color: var(--site-color-primary)">
                {{ config.meta.title }}
            </router-link>

            <!-- Desktop nav -->
            <nav class="hidden gap-6 md:flex">
                <router-link
                    v-for="item in config.navigation"
                    :key="item.slug"
                    :to="item.slug"
                    class="text-sm font-medium transition-colors hover:opacity-80"
                    style="color: var(--site-color-text_muted)"
                    active-class="!opacity-100"
                    :style="{ color: $route.path === item.slug ? 'var(--site-color-primary)' : undefined }"
                >
                    {{ item.label }}
                </router-link>
            </nav>

            <!-- Mobile toggle -->
            <button class="md:hidden" @click="mobileOpen = !mobileOpen">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                </svg>
            </button>
        </div>

        <!-- Mobile nav -->
        <nav v-if="mobileOpen" class="border-t px-4 py-3 md:hidden" style="border-color: var(--site-color-surface)">
            <router-link
                v-for="item in config.navigation"
                :key="item.slug"
                :to="item.slug"
                class="block py-2 text-sm font-medium"
                @click="mobileOpen = false"
            >
                {{ item.label }}
            </router-link>
        </nav>
    </header>
</template>
