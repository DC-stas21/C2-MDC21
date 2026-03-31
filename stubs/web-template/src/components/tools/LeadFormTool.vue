<script setup lang="ts">
import { ref } from 'vue';
import { useConfig } from '@/composables/useConfig';

const config = useConfig();
const form = ref<Record<string, string>>({});
const submitted = ref(false);
const error = ref('');

const fieldLabels: Record<string, string> = {
    name: 'Nombre completo',
    email: 'Email',
    phone: 'Teléfono',
    message: 'Mensaje',
};

async function submit() {
    if (!config.tools.lead_form.enabled || !config.tools.lead_form.endpoint) {
        submitted.value = true;
        return;
    }

    try {
        await fetch(config.tools.lead_form.endpoint, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                asset: config.tools.lead_form.asset_domain,
                data: form.value,
            }),
        });
        submitted.value = true;
    } catch (e) {
        error.value = 'Error al enviar. Inténtalo de nuevo.';
    }
}
</script>

<template>
    <section class="py-12 sm:py-16">
        <div class="mx-auto max-w-lg px-4 sm:px-6">
            <div v-if="submitted" class="rounded-xl p-8 text-center" style="background-color: var(--site-color-surface)">
                <p class="text-lg font-semibold" style="color: var(--site-color-primary)">¡Gracias!</p>
                <p class="mt-2 text-sm" style="color: var(--site-color-text_muted)">Hemos recibido tu consulta. Te contactaremos pronto.</p>
            </div>
            <form v-else class="space-y-4" @submit.prevent="submit">
                <div v-for="field in config.tools.lead_form.fields" :key="field">
                    <label class="mb-1 block text-sm font-medium" style="color: var(--site-color-text)">{{ fieldLabels[field] ?? field }}</label>
                    <input
                        v-if="field !== 'message'"
                        v-model="form[field]"
                        :type="field === 'email' ? 'email' : field === 'phone' ? 'tel' : 'text'"
                        required
                        class="w-full rounded-lg border px-4 py-2.5 text-sm outline-none focus:ring-2"
                        :style="{ borderColor: 'var(--site-color-surface)', '--tw-ring-color': 'var(--site-color-primary)' }"
                    />
                    <textarea
                        v-else
                        v-model="form[field]"
                        rows="3"
                        class="w-full rounded-lg border px-4 py-2.5 text-sm outline-none focus:ring-2"
                        :style="{ borderColor: 'var(--site-color-surface)', '--tw-ring-color': 'var(--site-color-primary)' }"
                    />
                </div>
                <p v-if="error" class="text-sm text-red-500">{{ error }}</p>
                <button
                    type="submit"
                    class="w-full rounded-lg px-6 py-3 text-sm font-semibold text-white transition hover:opacity-90"
                    style="background-color: var(--site-color-primary)"
                >
                    Enviar
                </button>
            </form>
        </div>
    </section>
</template>
