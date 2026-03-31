<script setup lang="ts">
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

function submit() {
    form.post('/login', {
        onFinish: () => form.reset('password'),
    });
}
</script>

<template>
    <div class="flex min-h-screen items-center justify-center bg-[#09090b] px-4">
        <div class="w-full max-w-sm">
            <!-- Logo -->
            <div class="mb-8 text-center">
                <div class="inline-flex items-center gap-2.5">
                    <div class="flex h-8 w-8 items-center justify-center rounded-md bg-white text-xs font-bold text-[#09090b]">C2</div>
                    <span class="text-lg font-semibold text-white">MDC21</span>
                </div>
                <p class="mt-2 text-[13px] text-[#a1a1aa]">Panel de Control</p>
            </div>

            <!-- Form -->
            <form @submit.prevent="submit" class="space-y-4">
                <div>
                    <label class="mb-1.5 block text-[12px] font-medium text-[#a1a1aa]">Email</label>
                    <input
                        v-model="form.email"
                        type="email"
                        autofocus
                        required
                        class="w-full rounded-lg border border-[#27272a] bg-[#18181b] px-3.5 py-2.5 text-[14px] text-white placeholder-[#52525b] outline-none transition focus:border-[#3f3f46] focus:ring-1 focus:ring-[#3f3f46]"
                        placeholder="admin@mdc21.com"
                    />
                </div>

                <div>
                    <label class="mb-1.5 block text-[12px] font-medium text-[#a1a1aa]">Contraseña</label>
                    <input
                        v-model="form.password"
                        type="password"
                        required
                        class="w-full rounded-lg border border-[#27272a] bg-[#18181b] px-3.5 py-2.5 text-[14px] text-white placeholder-[#52525b] outline-none transition focus:border-[#3f3f46] focus:ring-1 focus:ring-[#3f3f46]"
                        placeholder="••••••••"
                    />
                </div>

                <div class="flex items-center justify-between">
                    <label class="flex items-center gap-2 text-[12px] text-[#a1a1aa]">
                        <input v-model="form.remember" type="checkbox" class="rounded border-[#27272a] bg-[#18181b] accent-white" />
                        Recordarme
                    </label>
                </div>

                <!-- Error -->
                <div v-if="form.errors.email" class="rounded-lg border border-red-500/20 bg-red-500/10 px-3 py-2 text-[12px] text-red-400">
                    {{ form.errors.email }}
                </div>

                <button
                    type="submit"
                    :disabled="form.processing"
                    class="w-full rounded-lg bg-white px-4 py-2.5 text-[13px] font-semibold text-[#09090b] transition hover:bg-[#e4e4e7] disabled:opacity-50"
                >
                    {{ form.processing ? 'Entrando...' : 'Entrar' }}
                </button>
            </form>
        </div>
    </div>
</template>
