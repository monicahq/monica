<?php

namespace App\Http\Controllers\Auth\ViewHelpers;

class AcceptInvitationShowViewHelper
{
    public static function data(string $code): array
    {
        return [
            'invitation_code' => $code,
            'url' => [
                'store' => route('invitation.store'),
            ],
        ];
    }
}
