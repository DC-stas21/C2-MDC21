export interface User {
    id: string;
    name: string;
    email: string;
}

export interface AgentRun {
    id: string;
    agent_type: AgentType;
    status: AgentStatus;
    input: Record<string, unknown> | null;
    output: Record<string, unknown> | null;
    metadata: Record<string, unknown> | null;
    error: string | null;
    started_at: string | null;
    finished_at: string | null;
    created_at: string;
}

export interface Approval {
    id: string;
    agent_run_id: string;
    action: string;
    level: ApprovalLevel;
    status: ApprovalStatus;
    requested_by: string | null;
    decided_by: string | null;
    reason: string;
    decision_note: string | null;
    context: Record<string, unknown> | null;
    decided_at: string | null;
    created_at: string;
    agent_run?: AgentRun;
}

export interface NicheConfig {
    id: string;
    domain: string;
    vertical: string;
    config: Record<string, unknown>;
    colors: Record<string, string> | null;
    is_active: boolean;
    build_status: 'pending' | 'building' | 'staging' | 'live' | 'failed';
    build_metadata: Record<string, unknown> | null;
}

export type AgentType =
    | 'orchestrator'
    | 'web_builder'
    | 'policy_brand'
    | 'seo_content'
    | 'distribution'
    | 'build_release'
    | 'infra_reliability'
    | 'qa_experimentation';

export type AgentStatus = 'pending' | 'running' | 'completed' | 'failed';

export type ApprovalLevel = 'N1' | 'N2' | 'N3';

export type ApprovalStatus = 'pending' | 'approved' | 'denied';

export interface PaginatedResponse<T> {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
}

export interface SharedProps {
    auth: {
        user: User;
    };
    flash: {
        success?: string;
        error?: string;
    };
}
