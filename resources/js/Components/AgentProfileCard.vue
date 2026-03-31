<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

const props = defineProps<{
    agent: {
        type: string;
        layer: number;
        model: string;
        queue: string;
        is_running: boolean;
        last_status: string | null;
        last_run_at: string | null;
        last_error: string | null;
        today_runs: number;
        today_failed: number;
        week_total: number;
        success_rate: number | null;
        avg_duration_sec: number | null;
    };
    selected: boolean;
}>();

defineEmits<{
    select: [type: string];
}>();

function timeAgo(dateStr: string | null): string {
    if (!dateStr) return 'nunca';
    const diff = Date.now() - new Date(dateStr).getTime();
    const mins = Math.floor(diff / 60000);
    if (mins < 1) return 'ahora';
    if (mins < 60) return `${mins}m`;
    const h = Math.floor(mins / 60);
    return h < 24 ? `${h}h` : `${Math.floor(h / 24)}d`;
}

function fmtDuration(sec: number | null): string {
    if (sec === null) return '—';
    if (sec < 60) return `${sec}s`;
    return `${Math.floor(sec / 60)}m ${sec % 60}s`;
}

const layerLabels = ['Núcleo', 'Funcional', 'Operativo'];
const layerColors = ['bg-violet-100 text-violet-700', 'bg-blue-100 text-blue-400', 'bg-gray-100 text-[#71717a]'];
</script>

<template>
    <div
        :class="[
            'cursor-pointer rounded-lg border bg-[#0c0c0f] p-4 transition',
            selected ? 'border-[#09090b] ring-1 ring-[#09090b]' : 'border-[#1f1f23] hover:border-[#3f3f46]',
        ]"
        @click="$emit('select', agent.type)"
    >
        <!-- Header -->
        <div class="flex items-start justify-between">
            <div class="flex items-center gap-2">
                <span
                    :class="[
                        'h-2.5 w-2.5 rounded-full',
                        agent.is_running ? 'animate-pulse bg-indigo-500' :
                        agent.last_status === 'completed' ? 'bg-emerald-500' :
                        agent.last_status === 'failed' ? 'bg-red-500' : 'bg-[#d4d4d8]',
                    ]"
                />
                <h3 class="text-[13px] font-semibold text-[#fafafa]">{{ t(`agents.${agent.type}`) }}</h3>
            </div>
            <span :class="['rounded-md px-1.5 py-0.5 text-[10px] font-medium', layerColors[agent.layer]]">
                {{ layerLabels[agent.layer] }}
            </span>
        </div>

        <!-- Model + Queue -->
        <p class="mt-1 text-[11px] text-[#a1a1aa]">{{ agent.model }} · cola: {{ agent.queue }}</p>

        <!-- Metrics grid -->
        <div class="mt-3 grid grid-cols-3 gap-3 border-t border-[#f4f4f5] pt-3">
            <div>
                <p class="text-[10px] font-medium uppercase tracking-wide text-[#a1a1aa]">Hoy</p>
                <p class="mt-0.5 text-[14px] font-semibold text-[#fafafa]">{{ agent.today_runs }}</p>
                <p v-if="agent.today_failed > 0" class="text-[10px] text-red-500">{{ agent.today_failed }} err</p>
            </div>
            <div>
                <p class="text-[10px] font-medium uppercase tracking-wide text-[#a1a1aa]">Éxito</p>
                <p class="mt-0.5 text-[14px] font-semibold" :class="agent.success_rate !== null && agent.success_rate >= 90 ? 'text-emerald-600' : agent.success_rate !== null && agent.success_rate >= 70 ? 'text-[#fafafa]' : 'text-amber-600'">
                    {{ agent.success_rate !== null ? `${agent.success_rate}%` : '—' }}
                </p>
            </div>
            <div>
                <p class="text-[10px] font-medium uppercase tracking-wide text-[#a1a1aa]">Duración</p>
                <p class="mt-0.5 font-mono text-[13px] font-medium text-[#fafafa]">{{ fmtDuration(agent.avg_duration_sec) }}</p>
            </div>
        </div>

        <!-- Status line -->
        <div class="mt-3 flex items-center justify-between border-t border-[#f4f4f5] pt-2.5">
            <span class="text-[11px] text-[#a1a1aa]">
                {{ agent.is_running ? 'Ejecutando ahora' : `Última: ${timeAgo(agent.last_run_at)}` }}
            </span>
            <span class="text-[11px] text-[#a1a1aa]">{{ agent.week_total }}x esta semana</span>
        </div>

        <!-- Error (if last run failed) -->
        <div v-if="agent.last_error && agent.last_status === 'failed'" class="mt-2 rounded bg-red-950 px-2.5 py-1.5 text-[11px] text-red-400">
            {{ agent.last_error }}
        </div>
    </div>
</template>
