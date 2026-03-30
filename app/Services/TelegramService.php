<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramService
{
    private string $botToken;

    private array $groups;

    public function __construct()
    {
        $this->botToken = config('services.telegram.bot_token', '');
        $this->groups = config('services.telegram.groups', []);
    }

    /**
     * Send a message to a specific Telegram group.
     *
     * @param  string  $group  Group key: 'infra', 'content', 'negocio'
     */
    public function send(string $group, string $message): bool
    {
        $chatId = $this->groups[$group] ?? null;

        if (empty($this->botToken) || empty($chatId)) {
            Log::info('[telegram] Message not sent (token or group not configured)', [
                'group' => $group,
                'message' => mb_substr($message, 0, 100),
            ]);

            return false;
        }

        try {
            $response = Http::post("https://api.telegram.org/bot{$this->botToken}/sendMessage", [
                'chat_id' => $chatId,
                'text' => $message,
                'parse_mode' => 'HTML',
                'disable_web_page_preview' => true,
            ]);

            if ($response->failed()) {
                Log::error('[telegram] Send failed', [
                    'group' => $group,
                    'status' => $response->status(),
                    'body' => $response->json(),
                ]);

                return false;
            }

            return true;
        } catch (\Throwable $e) {
            Log::error('[telegram] Exception', ['error' => $e->getMessage()]);

            return false;
        }
    }

    /**
     * Send an alert to the infrastructure group.
     */
    public function infraAlert(string $severity, string $message, array $details = []): bool
    {
        $emoji = match ($severity) {
            'critical' => '🔴',
            'warning' => '🟡',
            default => 'ℹ️',
        };

        $text = "{$emoji} <b>[INFRA] {$severity}</b>\n\n{$message}";

        if (! empty($details)) {
            $text .= "\n\n<b>Detalles:</b>";
            foreach ($details as $key => $value) {
                $text .= "\n• {$key}: {$value}";
            }
        }

        return $this->send('infra', $text);
    }

    /**
     * Send a content notification.
     */
    public function contentNotify(string $action, string $domain, array $details = []): bool
    {
        $text = "📝 <b>[CONTENIDO]</b> {$action}\n\nActivo: {$domain}";

        foreach ($details as $key => $value) {
            $text .= "\n• {$key}: {$value}";
        }

        return $this->send('content', $text);
    }

    /**
     * Send a business/leads notification.
     */
    public function businessNotify(string $action, string $domain, array $details = []): bool
    {
        $text = "💼 <b>[NEGOCIO]</b> {$action}\n\nActivo: {$domain}";

        foreach ($details as $key => $value) {
            $text .= "\n• {$key}: {$value}";
        }

        return $this->send('negocio', $text);
    }

    /**
     * Notify about a pending N3 approval.
     */
    public function approvalNeeded(string $action, string $agentType, string $reason): bool
    {
        $text = "⚠️ <b>[APROBACIÓN N3]</b>\n\n<b>Acción:</b> {$action}\n<b>Agente:</b> {$agentType}\n<b>Razón:</b> {$reason}\n\n👉 Revisa en el Panel de Control";

        // Send to the most relevant group
        return $this->send('infra', $text);
    }

    /**
     * Check if Telegram is configured.
     */
    public function isConfigured(): bool
    {
        return ! empty($this->botToken) && ! empty(array_filter($this->groups));
    }
}
