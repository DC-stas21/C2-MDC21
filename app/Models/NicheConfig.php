<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class NicheConfig extends Model
{
    use HasUuids;

    public const STATUS_PENDING = 'pending';

    public const STATUS_BUILDING = 'building';

    public const STATUS_STAGING = 'staging';

    public const STATUS_LIVE = 'live';

    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'domain',
        'vertical',
        'cpl',
        'colors',
        'config',
        'is_active',
        'build_status',
        'build_metadata',
    ];

    protected function casts(): array
    {
        return [
            'cpl' => 'decimal:2',
            'colors' => 'array',
            'config' => 'array',
            'is_active' => 'boolean',
            'build_metadata' => 'array',
        ];
    }

    public function isLive(): bool
    {
        return $this->build_status === self::STATUS_LIVE;
    }

    public function isBuilding(): bool
    {
        return $this->build_status === self::STATUS_BUILDING;
    }

    public function isPending(): bool
    {
        return $this->build_status === self::STATUS_PENDING;
    }

    public function siteConfigPath(): string
    {
        return "/var/www/{$this->domain}/site.config.json";
    }

    public function sitePath(): string
    {
        return "/var/www/{$this->domain}";
    }

    public function siteUrl(): string
    {
        return "https://{$this->domain}";
    }
}
