<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;

class NginxConfigService
{
    private string $stubPath;

    private string $sitesAvailable;

    private string $sitesEnabled;

    public function __construct()
    {
        $this->stubPath = base_path('stubs/nginx/site.conf.stub');
        $this->sitesAvailable = '/etc/nginx/sites-available';
        $this->sitesEnabled = '/etc/nginx/sites-enabled';
    }

    /**
     * Generate Nginx config content from stub.
     */
    public function generateConfig(string $domain): string
    {
        $stub = file_get_contents($this->stubPath);

        return str_replace('{{DOMAIN}}', $domain, $stub);
    }

    /**
     * Deploy Nginx config for a domain.
     */
    public function deploy(string $domain): array
    {
        $config = $this->generateConfig($domain);
        $configPath = "{$this->sitesAvailable}/{$domain}";
        $enabledPath = "{$this->sitesEnabled}/{$domain}";

        try {
            // Write config
            $result = Process::run("echo '{$config}' | sudo tee {$configPath}");
            if ($result->failed()) {
                return ['status' => 'failed', 'message' => 'Failed to write config: '.$result->errorOutput()];
            }

            // Create symlink
            if (! file_exists($enabledPath)) {
                Process::run("sudo ln -sf {$configPath} {$enabledPath}");
            }

            // Test config
            $test = Process::run('sudo nginx -t 2>&1');
            if ($test->failed()) {
                // Rollback
                Process::run("sudo rm -f {$enabledPath} {$configPath}");
                Log::error('[nginx] Config test failed, rolled back', ['domain' => $domain, 'output' => $test->errorOutput()]);

                return ['status' => 'failed', 'message' => 'Nginx config test failed: '.$test->errorOutput()];
            }

            // Reload
            Process::run('sudo systemctl reload nginx');

            Log::info('[nginx] Config deployed', ['domain' => $domain]);

            return ['status' => 'ok', 'config_path' => $configPath, 'message' => "Nginx configured for {$domain}"];
        } catch (\Throwable $e) {
            Log::error('[nginx] Deploy failed', ['domain' => $domain, 'error' => $e->getMessage()]);

            return ['status' => 'failed', 'message' => $e->getMessage()];
        }
    }

    /**
     * Setup SSL with Certbot.
     */
    public function setupSsl(string $domain): array
    {
        try {
            $result = Process::timeout(120)->run("sudo certbot --nginx -d {$domain} --non-interactive --agree-tos --redirect");

            if ($result->failed()) {
                return ['status' => 'failed', 'message' => 'Certbot failed: '.$result->errorOutput()];
            }

            Log::info('[nginx] SSL configured', ['domain' => $domain]);

            return ['status' => 'ok', 'message' => "SSL active for {$domain}"];
        } catch (\Throwable $e) {
            return ['status' => 'failed', 'message' => $e->getMessage()];
        }
    }

    /**
     * Check if Nginx is available and we have permissions (production only).
     */
    public function isAvailable(): bool
    {
        // In local dev, skip Nginx entirely
        if (! app()->environment('production')) {
            return false;
        }

        try {
            $result = Process::run('which nginx 2>/dev/null');

            return $result->successful() && ! empty(trim($result->output()));
        } catch (\Throwable) {
            return false;
        }
    }
}
