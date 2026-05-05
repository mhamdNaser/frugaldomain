<?php

namespace App\Modules\Shopify\OutboundSync\Enums;

final class OutboundSyncStatus
{
    public const PENDING = 'pending';
    public const PROCESSING = 'processing';
    public const RETRYING = 'retrying';
    public const SYNCED = 'synced';
    public const FAILED = 'failed';
    public const DEAD = 'dead';

    public const TERMINAL = [
        self::SYNCED,
        self::DEAD,
    ];
}

