import { ref, onMounted, onUnmounted } from 'vue';
import { router } from '@inertiajs/vue3';

interface AgentEvent {
    agent_run_id: string;
    agent_type: string;
    status: string;
    error: string | null;
    timestamp: string;
}

interface ApprovalEvent {
    approval_id: string;
    action: string;
    level: string;
    reason: string;
    timestamp: string;
}

interface InfraEvent {
    severity: string;
    alerts_count: number;
    checks: Record<string, unknown>;
    timestamp: string;
}

export function useRealtimeDashboard() {
    const lastAgentEvent = ref<AgentEvent | null>(null);
    const lastApprovalEvent = ref<ApprovalEvent | null>(null);
    const lastInfraEvent = ref<InfraEvent | null>(null);
    const isConnected = ref(false);
    const eventCount = ref(0);

    let channel: any = null;

    onMounted(() => {
        if (typeof window === 'undefined' || !window.Echo) {
            return;
        }

        try {
            channel = window.Echo.channel('c2-dashboard');

            channel
                .listen('.agent.updated', (event: AgentEvent) => {
                    lastAgentEvent.value = event;
                    eventCount.value++;

                    // Refresh Inertia page data after a short delay
                    // to let the DB update settle
                    setTimeout(() => {
                        router.reload({ only: ['stats', 'agentStatuses', 'recentRuns', 'timeline', 'agentActivity'] });
                    }, 500);
                })
                .listen('.approval.created', (event: ApprovalEvent) => {
                    lastApprovalEvent.value = event;
                    eventCount.value++;

                    setTimeout(() => {
                        router.reload({ only: ['stats', 'pendingApprovals'] });
                    }, 500);
                })
                .listen('.infra.updated', (event: InfraEvent) => {
                    lastInfraEvent.value = event;
                    eventCount.value++;
                });

            isConnected.value = true;
        } catch (e) {
            // Echo not available (no Reverb running) — fail silently
            isConnected.value = false;
        }
    });

    onUnmounted(() => {
        if (channel && window.Echo) {
            window.Echo.leave('c2-dashboard');
        }
    });

    return {
        lastAgentEvent,
        lastApprovalEvent,
        lastInfraEvent,
        isConnected,
        eventCount,
    };
}
