<script setup lang="ts">
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import type { Approval } from '@/types';

const { t } = useI18n();
const props = defineProps<{ approval: Approval }>();
const loading = ref(false);
const noteInput = ref('');
const showNote = ref(false);

function handleAction(action: 'approve' | 'deny') {
    loading.value = true;
    router.post(`/approvals/${props.approval.id}/${action}`, {
        note: noteInput.value || null,
    }, {
        preserveScroll: true,
        onFinish: () => { loading.value = false; showNote.value = false; },
    });
}

function timeAgo(dateStr: string): string {
    const diff = Date.now() - new Date(dateStr).getTime();
    const mins = Math.floor(diff / 60000);
    if (mins < 1) return 'ahora';
    if (mins < 60) return `hace ${mins}m`;
    const hours = Math.floor(mins / 60);
    if (hours < 24) return `hace ${hours}h`;
    return `hace ${Math.floor(hours / 24)}d`;
}

const levelDot: Record<string, string> = {
    N1: 'bg-emerald-500',
    N2: 'bg-amber-500',
    N3: 'bg-red-500',
};
</script>

<template>
    <div class="group flex items-start gap-3 rounded-md border border-transparent px-3 py-2.5 transition hover:border-[#e4e4e7] hover:bg-white">
        <div class="mt-1 flex flex-col items-center gap-1">
            <span :class="['h-2 w-2 rounded-full', levelDot[approval.level] ?? 'bg-gray-400']" />
            <span class="text-[10px] font-medium text-[#a1a1aa]">{{ approval.level }}</span>
        </div>

        <div class="min-w-0 flex-1">
            <p class="text-[13px] font-medium text-[#09090b]">{{ approval.action }}</p>
            <p class="mt-0.5 text-[12px] text-[#71717a]">{{ approval.reason }}</p>
            <div class="mt-1 flex items-center gap-3">
                <span v-if="approval.agent_run" class="text-[11px] text-[#a1a1aa]">{{ t(`agents.${approval.agent_run.agent_type}`) }}</span>
                <span class="text-[11px] text-[#a1a1aa]">{{ timeAgo(approval.created_at) }}</span>
            </div>
        </div>

        <div v-if="!showNote" class="flex shrink-0 gap-1.5 opacity-0 transition group-hover:opacity-100">
            <button
                class="rounded-md border border-[#e4e4e7] bg-white px-2.5 py-1 text-[12px] font-medium text-[#09090b] transition hover:bg-[#f4f4f5]"
                :disabled="loading"
                @click="handleAction('approve')"
            >
                {{ t('approvals.actions.approve') }}
            </button>
            <button
                class="rounded-md border border-red-200 bg-white px-2.5 py-1 text-[12px] font-medium text-red-600 transition hover:bg-red-50"
                :disabled="loading"
                @click="showNote = true"
            >
                {{ t('approvals.actions.deny') }}
            </button>
        </div>

        <div v-if="showNote" class="flex shrink-0 gap-1.5">
            <input
                v-model="noteInput"
                type="text"
                :placeholder="t('approvals.deny_reason')"
                class="w-48 rounded-md border border-[#e4e4e7] px-2.5 py-1 text-[12px] text-[#09090b] placeholder-[#a1a1aa] outline-none focus:border-[#a1a1aa]"
            />
            <button
                class="rounded-md bg-red-600 px-2.5 py-1 text-[12px] font-medium text-white hover:bg-red-700"
                :disabled="loading"
                @click="handleAction('deny')"
            >{{ t('common.confirm') }}</button>
            <button
                class="rounded-md border border-[#e4e4e7] px-2.5 py-1 text-[12px] text-[#71717a] hover:bg-[#f4f4f5]"
                @click="showNote = false"
            >{{ t('common.cancel') }}</button>
        </div>
    </div>
</template>
