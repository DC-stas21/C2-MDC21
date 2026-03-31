<script setup lang="ts">
import { provideConfig } from '@/composables/useConfig';
import { useTheme } from '@/composables/useTheme';
import { useAdsense } from '@/composables/useAdsense';
import { useSeo } from '@/composables/useSeo';
import DefaultLayout from '@/layouts/DefaultLayout.vue';
import CookieConsent from '@/components/CookieConsent.vue';

provideConfig();
useTheme();
useAdsense();
useSeo();
</script>

<template>
    <DefaultLayout>
        <router-view />
    </DefaultLayout>
    <CookieConsent />
</template>
