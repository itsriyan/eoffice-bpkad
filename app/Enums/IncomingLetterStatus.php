<?php

namespace App\Enums;

enum IncomingLetterStatus: string
{
    case New = 'new';
    case Disposed = 'disposed';
    case FollowedUp = 'followed_up';
    case Rejected = 'rejected';
    case Completed = 'completed';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::New => 'New',
            self::Disposed => 'Disposed',
            self::FollowedUp => 'Followed Up',
            self::Rejected => 'Rejected',
            self::Completed => 'Completed',
            self::Archived => 'Archived',
        };
    }
}
