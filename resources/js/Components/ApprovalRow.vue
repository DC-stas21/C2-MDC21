<script setup lang="ts">
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import type { Approval } from '@/types';

const { t } = useI18n();
const props = defineProps<{ approval: Approval }>();
const loading = ref(false);
const noteInput = ref('');
const showNote = ref(false);

const isDeployApproval = computed(() =>
    props.approval.action?.includes('Deploy a producción') || props.approval.action?.includes('Deploy a staging')
);

const previewDomain = computed(() => props.approval.context?.domain ?? null);

function handleAction(action: 'approve' | 'deny') {
    if (action === 'deny' && !noteInput.value?.trim()) {
        return; // Require feedback when denying
    }
    loading.value = true;
    router.post(`/approvals/${props.approval.id}/${action}`, {
        note: noteInput.value || null,
    }, {
        preserveScroll: true,
        onFinish: () => { loading.value = false; showNote.value = false; noteInput.value = ''; },
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
    <div class="group flex items-start gap-3 rounded-md border border-transparent px-3 py-2.5 transition hover:border-[#1f1f23] hover:bg-[#0c0c0f]">
        <div class="mt-1 flex flex-col items-center gap-1">
            <span :class="['h-2 w-2 rounded-full', levelDot[approval.level] ?? 'bg-gray-400']" />
            <span class="text-[10px] font-medium text-[#a1a1aa]">{{ approval.level }}</span>
        </div>

        <div class="min-w-0 flex-1">
            <p class="text-[13px] font-medium text-[#fafafa]">{{ approval.action }}</p>
            <p class="mt-0.5 text-[12px] text-[#a1a1aa]">{{ approval.reason }}</p>
            <div class="mt-1 flex items-center gap-3">
                <span v-if="approval.agent_run" class="text-[11px] text-[#a1a1aa]">{{ t(`agents.${approval.agent_run.agent_type}`) }}</span>
                <span class="text-[11px] text-[#a1a1aa]">{{ timeAgo(approval.created_at) }}</span>
                <!-- Preview link for deploy approvals -->
                <a
                    v-if="isDeployApproval && previewDomain"
                    :href="`http://${previewDomain}`"
                    target="_blank"
                    class="inline-flex items-center gap-1 text-[11px] font-medium text-indigo-400 hover:text-indigo-300"
                >
                    <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" />
                    </svg>
                    Ver preview
                </a>
            </div>
        </div>

        <!-- Action buttons -->
        <div v-if="!showNote" class="flex shrink-0 gap-1.5 opacity-0 transition group-hover:opacity-100">
            <button
                class="rounded-md border border-[#1f1f23] bg-[#0c0c0f] px-2.5 py-1 text-[12px] font-medium text-[#fafafa] transition hover:bg-[#1f1f23]"
                :disabled="loading"
                @click="handleAction('approve')"
            >
                {{ t('approvals.actions.approve') }}
            </button>
            <button
                class="rounded-md border border-red-800 bg-[#0c0c0f] px-2.5 py-1 text-[12px] font-medium text-red-400 transition hover:bg-red-950"
                :disabled="loading"
                @click="showNote = true"
            >
                {{ t('approvals.actions.deny') }}
            </button>
        </div>

        <!-- Deny form with required feedback -->
        <div v-if="showNote" class="flex shrink-0 flex-col gap-1.5">
            <textarea
                v-model="noteInput"
                rows="2"
                :placeholder="isDeployApproval ? 'Qué cambios necesitas? (obligatorio)' : t('approvals.deny_reason')"
                class="w-64 rounded-md border border-[#1f1f23] bg-[#0c0c0f] px-2.5 py-1.5 text-[12px] text-[#fafafa] placeholder-[#52525b] outline-none focus:border-[#3f3f46]"
            />
            <div class="flex gap-1.5">
                <button
                    class="flex-1 rounded-md bg-red-600 px-2.5 py-1 text-[12px] font-medium text-white hover:bg-red-700 disabled:opacity-30"
                    :disabled="loading || !noteInput?.trim()"
                    @click="handleAction('deny')"
                >Denegar con feedback</button>
                <button
                    class="rounded-md border border-[#1f1f23] px-2.5 py-1 text-[12px] text-[#a1a1aa] hover:bg-[#1f1f23]"
                    @click="showNote = false; noteInput = ''"
                >{{ t('common.cancel') }}</button>
            </div>
        </div>
    </div>
</template>
