<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Models\NotificationChannel;

/**
 * A notification that knows which of the account's channels is carrying it.
 *
 * Where a message goes and what it signs in with are the account's own, held on
 * one `notification_channels` row, so the notifiable has to be able to find
 * that row from the notification it was handed. This is what lets `User` answer
 * Laravel's routing questions without knowing any notification by name.
 */
interface CarriesOnAccountChannel
{
    public function notificationChannel(): NotificationChannel;
}
