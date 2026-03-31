<script setup lang="ts">
import { ref, onMounted } from 'vue';

const visible = ref(false);

onMounted(() => {
    if (!localStorage.getItem('cookie-consent')) {
        visible.value = true;
    }
});

function accept() {
    localStorage.setItem('cookie-consent', 'accepted');
    visible.value = false;
}

function reject() {
    localStorage.setItem('cookie-consent', 'rejected');
    visible.value = false;
}
</script>

<template>
    <div
        v-if="visible"
        class="fixed bottom-0 left-0 right-0 z-50 border-t p-4 sm:p-6"
        style="background-color: var(--site-color-background); border-color: var(--site-color-surface)"
    >
        <div class="mx-auto flex max-w-4xl flex-col items-center justify-between gap-4 sm:flex-row">
            <p class="text-sm" style="color: var(--site-color-text_muted)">
                Utilizamos cookies propias y de terceros para mejorar tu experiencia y mostrar publicidad.
                <router-link to="/cookies" class="underline" style="color: var(--site-color-primary)">Más información</router-link>
            </p>
            <div class="flex shrink-0 gap-2">
                <button
                    class="rounded-lg px-4 py-2 text-sm font-medium transition hover:opacity-90"
                    style="background-color: var(--site-color-primary); color: #fff"
                    @click="accept"
                >
                    Aceptar
                </button>
                <button
                    class="rounded-lg border px-4 py-2 text-sm font-medium transition hover:opacity-80"
                    style="border-color: var(--site-color-surface); color: var(--site-color-text_muted)"
                    @click="reject"
                >
                    Rechazar
                </button>
            </div>
        </div>
    </div>
</template>
