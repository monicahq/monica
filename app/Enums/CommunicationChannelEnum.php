<?php

declare(strict_types=1);

namespace App\Enums;

use App\Contracts\SuppliedValueListItem;
use Override;

enum CommunicationChannelEnum: string implements SuppliedValueListItem
{
    case PhoneCall = 'phone_call';
    case TextMessage = 'text_message';
    case Email = 'email';
    case InstantMessaging = 'instant_messaging';
    case VideoCall = 'video_call';
    case InPerson = 'in_person';
    case Post = 'post';

    #[Override]
    public function label(): string
    {
        return match ($this) {
            self::PhoneCall => 'Phone call',
            self::TextMessage => 'Text message',
            self::Email => 'Email',
            self::InstantMessaging => 'Instant messaging',
            self::VideoCall => 'Video call',
            self::InPerson => 'In person',
            self::Post => 'Post',
        };
    }

    /** @return list<string> */
    #[Override]
    public function translationKeys(): array
    {
        return [$this->label()];
    }
}
