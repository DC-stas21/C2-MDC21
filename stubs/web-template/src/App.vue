<script setup lang="ts">
import { getConfig } from '@/config';
import { provideConfig } from '@/composables/useConfig';
import { useTheme } from '@/composables/useTheme';
import DefaultLayout from '@/layouts/DefaultLayout.vue';

const config = getConfig();
provideConfig(config);
useTheme();
</script>

<template>
    <DefaultLayout>
        <router-view />
    </DefaultLayout>
</template>
