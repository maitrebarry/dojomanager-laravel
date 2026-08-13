<?php

namespace App\Shared\Enums;

/**
 * Énumération des statuts utilisateur
 */
enum UserStatus: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
    case PENDING = 'pending';
    case SUSPENDED = 'suspended';
    case BANNED = 'banned';

    public function label(): string
    {
        return __("messages.statuses.{$this->value}");
    }

    public function color(): string
    {
        return match ($this) {
            self::ACTIVE => 'success',
            self::INACTIVE => 'secondary',
            self::PENDING => 'warning',
            self::SUSPENDED => 'danger',
            self::BANNED => 'dark',
        };
    }
}
