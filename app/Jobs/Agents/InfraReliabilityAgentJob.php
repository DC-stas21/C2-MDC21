<?php

namespace App\Jobs\Agents;

use App\Models\AgentRun;
use App\Models\NicheConfig;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class InfraReliabilityAgentJob extends BaseAgentJob
{
    public int $timeout = 120;

    public function __construct(
        private readonly string $checkType = 'full'
    ) {
        $this->onQueue('agents-ops');
    }

    protected function agentType(): string
    {
        return 'infra_reliability';
    }

    protected function input(): array
    {
        return ['check_type' => $this->checkType];
    }

    protected function execute(AgentRun $run): void
    {
        $checks = [];
        $alerts = [];
        $severity = 'info'; // info → warning → critical

        // 1. Database health
        $checks['database'] = $this->checkDatabase();
        if ($checks['database']['status'] === 'critical') {
            $severity = 'critical';
            $alerts[] = $checks['database'];
        }

        // 2. Redis health
        $checks['redis'] = $this->checkRedis();
        if ($checks['redis']['status'] === 'critical') {
            $severity = 'critical';
            $alerts[] = $checks['redis'];
        } elseif ($checks['redis']['status'] === 'warning' && $severity !== 'critical') {
            $severity = 'warning';
            $alerts[] = $checks['redis'];
        }

        // 3. Queue health (Horizon)
        $checks['queue'] = $this->checkQueue();
        if ($checks['queue']['status'] !== 'ok') {
            if ($severity !== 'critical') {
                $severity = $checks['queue']['status'] === 'critical' ? 'critical' : 'warning';
            }
            $alerts[] = $checks['queue'];
        }

        // 4. Disk space
        $checks['disk'] = $this->checkDiskSpace();
        if ($checks['disk']['status'] !== 'ok') {
            if ($severity !== 'critical') {
                $severity = $checks['disk']['status'];
            }
            $alerts[] = $checks['disk'];
        }

        // 5. Active assets uptime
        if ($this->checkType === 'full') {
            $checks['assets'] = $this->checkAssets();
            foreach ($checks['assets'] as $assetCheck) {
                if ($assetCheck['status'] !== 'ok') {
                    if ($severity !== 'critical') {
                        $severity = $assetCheck['status'] === 'critical' ? 'critical' : 'warning';
                    }
                    $alerts[] = $assetCheck;
                }
            }
        }

        // Store results
        $this->updateOutput([
            'severity' => $severity,
            'checks' => $checks,
            'alerts_count' => count($alerts),
            'alerts' => $alerts,
            'checked_at' => now()->toIso8601String(),
        ]);

        $this->updateMetadata([
            'severity' => $severity,
            'check_type' => $this->checkType,
        ]);

        // Cache latest infra status for dashboard
        Cache::put('infra:latest_status', [
            'severity' => $severity,
            'alerts_count' => count($alerts),
            'checked_at' => now()->toIso8601String(),
        ], 7200);

        // Log based on severity
        match ($severity) {
            'critical' => Log::critical('[infra_reliability] CRITICAL alerts detected', ['alerts' => $alerts]),
            'warning' => Log::warning('[infra_reliability] Warnings detected', ['alerts' => $alerts]),
            default => Log::info('[infra_reliability] All checks passed'),
        };
    }

    private function checkDatabase(): array
    {
        try {
            $start = microtime(true);
            DB::select('SELECT 1');
            $latencyMs = round((microtime(true) - $start) * 1000, 2);

            $connectionCount = DB::select("SELECT count(*) as count FROM pg_stat_activity WHERE state = 'active'")[0]->count ?? 0;
            $dbSize = DB::select('SELECT pg_size_pretty(pg_database_size(current_database())) as size')[0]->size ?? 'unknown';

            return [
                'check' => 'database',
                'status' => $latencyMs > 500 ? 'warning' : 'ok',
                'latency_ms' => $latencyMs,
                'active_connections' => $connectionCount,
                'database_size' => $dbSize,
                'message' => $latencyMs > 500 ? "DB latency high: {$latencyMs}ms" : 'PostgreSQL healthy',
            ];
        } catch (\Throwable $e) {
            return [
                'check' => 'database',
                'status' => 'critical',
                'message' => 'PostgreSQL unreachable: '.$e->getMessage(),
            ];
        }
    }

    private function checkRedis(): array
    {
        try {
            $start = microtime(true);
            Redis::ping();
            $latencyMs = round((microtime(true) - $start) * 1000, 2);

            $info = Redis::info();
            $memoryUsed = $info['used_memory_human'] ?? 'unknown';
            $connectedClients = $info['connected_clients'] ?? 0;

            $status = 'ok';
            $message = 'Redis healthy';

            if ($latencyMs > 100) {
                $status = 'warning';
                $message = "Redis latency high: {$latencyMs}ms";
            }

            return [
                'check' => 'redis',
                'status' => $status,
                'latency_ms' => $latencyMs,
                'memory_used' => $memoryUsed,
                'connected_clients' => $connectedClients,
                'message' => $message,
            ];
        } catch (\Throwable $e) {
            return [
                'check' => 'redis',
                'status' => 'critical',
                'message' => 'Redis unreachable: '.$e->getMessage(),
            ];
        }
    }

    private function checkQueue(): array
    {
        try {
            $pendingJobs = DB::table('jobs')->count();
            $failedJobs = DB::table('failed_jobs')->count();

            $status = 'ok';
            $message = "Queue healthy: {$pendingJobs} pending, {$failedJobs} failed";

            if ($failedJobs > 10) {
                $status = 'warning';
                $message = "High failed jobs count: {$failedJobs}";
            }

            if ($pendingJobs > 100) {
                $status = 'warning';
                $message = "Queue backlog: {$pendingJobs} pending jobs";
            }

            return [
                'check' => 'queue',
                'status' => $status,
                'pending_jobs' => $pendingJobs,
                'failed_jobs' => $failedJobs,
                'message' => $message,
            ];
        } catch (\Throwable $e) {
            // Queue tables might not exist if using Redis driver
            return [
                'check' => 'queue',
                'status' => 'ok',
                'message' => 'Queue using Redis driver (no DB tables)',
            ];
        }
    }

    private function checkDiskSpace(): array
    {
        $totalBytes = disk_total_space(base_path());
        $freeBytes = disk_free_space(base_path());

        if ($totalBytes === false || $freeBytes === false) {
            return ['check' => 'disk', 'status' => 'warning', 'message' => 'Cannot read disk space'];
        }

        $usedPct = round((1 - $freeBytes / $totalBytes) * 100, 1);
        $freeGb = round($freeBytes / 1073741824, 1);

        $status = 'ok';
        $message = "Disk: {$usedPct}% used, {$freeGb}GB free";

        if ($usedPct > 90) {
            $status = 'critical';
            $message = "CRITICAL: Disk {$usedPct}% full, only {$freeGb}GB free";
        } elseif ($usedPct > 80) {
            $status = 'warning';
            $message = "Warning: Disk {$usedPct}% used, {$freeGb}GB free";
        }

        return [
            'check' => 'disk',
            'status' => $status,
            'used_pct' => $usedPct,
            'free_gb' => $freeGb,
            'message' => $message,
        ];
    }

    private function checkAssets(): array
    {
        $results = [];

        $assets = NicheConfig::where('is_active', true)->pluck('domain');

        foreach ($assets as $domain) {
            try {
                $start = microtime(true);
                $response = Http::timeout(10)->get("https://{$domain}");
                $latencyMs = round((microtime(true) - $start) * 1000, 0);

                $statusCode = $response->status();

                $status = 'ok';
                $message = "{$domain}: {$statusCode} in {$latencyMs}ms";

                if ($statusCode >= 500) {
                    $status = 'critical';
                    $message = "{$domain}: HTTP {$statusCode}";
                } elseif ($statusCode >= 400 || $latencyMs > 5000) {
                    $status = 'warning';
                    $message = $latencyMs > 5000
                        ? "{$domain}: Slow response {$latencyMs}ms"
                        : "{$domain}: HTTP {$statusCode}";
                }

                $results[] = [
                    'check' => 'asset_uptime',
                    'domain' => $domain,
                    'status' => $status,
                    'http_code' => $statusCode,
                    'latency_ms' => $latencyMs,
                    'message' => $message,
                ];
            } catch (\Throwable $e) {
                $results[] = [
                    'check' => 'asset_uptime',
                    'domain' => $domain,
                    'status' => 'critical',
                    'message' => "{$domain}: Unreachable — ".$e->getMessage(),
                ];
            }
        }

        return $results;
    }
}
