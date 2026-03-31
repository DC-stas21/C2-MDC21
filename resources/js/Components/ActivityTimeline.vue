<script setup lang="ts">
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

defineProps<{
    events: Array<{
        type: 'agent_run' | 'approval';
        agent_type?: string;
        status: string;
        error?: string | null;
        action?: string;
        level?: string;
        at: string;
    }>;
}>();

function timeAgo(dateStr: string): string {
    const diff = Date.now() - new Date(dateStr).getTime();
    const mins = Math.floor(diff / 60000);
    if (mins < 1) return 'ahora';
    if (mins < 60) return `hace ${mins}m`;
    const hours = Math.floor(mins / 60);
    if (hours < 24) return `hace ${hours}h`;
    return `hace ${Math.floor(hours / 24)}d`;
}

function eventIcon(event: { type: string; status: string }): { color: string; char: string } {
    if (event.type === 'approval') {
        return event.status === 'approved'
            ? { color: 'bg-emerald-500', char: '✓' }
            : { color: 'bg-red-500', char: '✕' };
    }
    const map: Record<string, { color: string; char: string }> = {
        completed: { color: 'bg-emerald-500', char: '✓' },
        failed: { color: 'bg-red-500', char: '!' },
        running: { color: 'bg-indigo-500', char: '▸' },
        pending: { color: 'bg-[#d4d4d8]', char: '○' },
    };
    return map[event.status] ?? { color: 'bg-[#d4d4d8]', char: '·' };
}
</script>

<template>
    <div v-if="events.length === 0" class="py-6 text-center text-[13px] text-[#a1a1aa]">Sin actividad reciente</div>
    <div v-else class="relative">
        <!-- Timeline line -->
        <div class="absolute bottom-0 left-[11px] top-0 w-px bg-[#1f1f23]" />

        <div
            v-for="(event, i) in events"
            :key="i"
            class="relative flex gap-3 pb-3 last:pb-0"
        >
            <!-- Dot -->
            <div class="relative z-10 mt-0.5 flex h-[22px] w-[22px] shrink-0 items-center justify-center rounded-full border-2 border-white" :class="eventIcon(event).color">
                <span class="text-[9px] font-bold text-white">{{ eventIcon(event).char }}</span>
            </div>

            <!-- Content -->
            <div class="min-w-0 flex-1 pt-px">
                <div class="flex items-baseline justify-between gap-2">
                    <p class="text-[12px] text-[#fafafa]">
                        <template v-if="event.type === 'agent_run'">
                            <span class="font-medium">{{ event.agent_type ? t(`agents.${event.agent_type}`) : 'Agente' }}</span>
                            <span class="text-[#a1a1aa]">
                                {{ event.status === 'completed' ? ' completó ejecución' :
                                   event.status === 'failed' ? ' falló' :
                                   event.status === 'running' ? ' inició ejecución' : ' en cola' }}
                            </span>
                        </template>
                        <template v-else>
                            <span class="text-[#a1a1aa]">{{ event.status === 'approved' ? 'Aprobado:' : 'Denegado:' }}</span>
                            {{ ' ' }}
                            <span class="font-medium">{{ event.action }}</span>
                        </template>
                    </p>
                    <span class="shrink-0 text-[11px] text-[#a1a1aa]">{{ timeAgo(event.at) }}</span>
                </div>
                <p v-if="event.error" class="mt-0.5 truncate text-[11px] text-red-500">{{ event.error }}</p>
            </div>
        </div>
    </div>
</template>
