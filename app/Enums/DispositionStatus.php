<?php

namespace App\Enums;

enum DispositionStatus: string
{
    case New = 'new';
    case Sent = 'sent';
    case Received = 'received';
    case Rejected = 'rejected';
    case FollowedUp = 'followed_up';
    case Completed = 'completed';

    public function label(): string
    {
        return match ($this) {
            self::New => 'New',
            self::Sent => 'Sent',
            self::Received => 'Received',
            self::Rejected => 'Rejected',
            self::FollowedUp => 'Followed Up',
            self::Completed => 'Completed',
        };
    }
}
