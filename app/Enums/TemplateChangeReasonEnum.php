<?php

declare(strict_types=1);

namespace App\Enums;

enum TemplateChangeReasonEnum: string
{
    case Deleted = 'deleted';
    case Archived = 'archived';
    case ChangedAfterPreview = 'changed_after_preview';
    case ConfirmedByDeletedUser = 'confirmed_by_deleted_user';
    case MoveFailed = 'move_failed';

    public function sentence(string $name): string
    {
        return match ($this) {
            self::Deleted => __('This :name was deleted after the preview.', ['name' => $name]),
            self::Archived => __('This :name was archived after the preview.', ['name' => $name]),
            self::ChangedAfterPreview => __('This :name’s template changed after the preview.', ['name' => $name]),
            self::ConfirmedByDeletedUser => __('The person who confirmed this change no longer has an account.'),
            self::MoveFailed => __('This :name could not be moved. Nothing was changed on it.', ['name' => $name]),
        };
    }
}
