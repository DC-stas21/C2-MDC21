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
    name: string;
    domain: string;
    vertical: string;
    config: Record<string, unknown>;
    is_active: boolean;
}

export interface BlogPost {
    id: string;
    niche_config_id: string;
    title: string;
    slug: string;
    content: string;
    status: BlogPostStatus;
    author: string | null;
    sources: string[] | null;
    methodology: string | null;
    published_at: string | null;
    created_at: string;
}

export interface Lead {
    id: string;
    niche_config_id: string;
    score: number;
    status: LeadStatus;
    data: Record<string, unknown>;
    provider: string | null;
    created_at: string;
}

export interface ScoreComposite {
    score: number;
    classification: 'excellent' | 'good' | 'average' | 'poor' | 'critical';
    dimensions: Record<string, number>;
    alerts: ScoreAlert[];
}

export interface ScoreAlert {
    type: string;
    severity: 'low' | 'medium' | 'high' | 'critical';
    message: string;
}

export type AgentType =
    | 'orchestrator'
    | 'policy_brand'
    | 'seo_content'
    | 'distribution'
    | 'engagement_retention'
    | 'monetization_leads'
    | 'build_release'
    | 'infra_reliability'
    | 'qa_experimentation';

export type AgentStatus = 'pending' | 'running' | 'completed' | 'failed';

export type ApprovalLevel = 'N1' | 'N2' | 'N3';

export type ApprovalStatus = 'pending' | 'approved' | 'denied';

export type BlogPostStatus = 'draft' | 'pending_review' | 'published' | 'rejected';

export type LeadStatus = 'new' | 'qualified' | 'sent' | 'discarded';

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
