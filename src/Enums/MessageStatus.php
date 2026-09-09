<?php

namespace SimplyConnect\Laravel\Enums;

enum MessageStatus: string
{
    case Queued = 'QUEUED';
    case Scheduled = 'SCHEDULED';
    case Leased = 'LEASED';
    case Acknowledged = 'ACKNOWLEDGED';
    case Sending = 'SENDING';
    case Sent = 'SENT';
    case Delivered = 'DELIVERED';
    case Received = 'RECEIVED';
    case Failed = 'FAILED';
    case Unknown = 'UNKNOWN';
    case DeadLetter = 'DEAD_LETTER';

    public static function fromApi(string $status): self
    {
        return self::tryFrom($status) ?? self::Unknown;
    }

    public function isTerminal(): bool
    {
        return in_array($this, [self::Delivered, self::Received, self::Failed, self::Unknown, self::DeadLetter], true);
    }

    public function hasFailed(): bool
    {
        return in_array($this, [self::Failed, self::Unknown, self::DeadLetter], true);
    }
}
