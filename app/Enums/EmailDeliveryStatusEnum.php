<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Where an email we sent got to.
 *
 * Not a column. It is read off the three timestamps on the record, and it
 * exists so that the screen listing sent emails and the API answering for them
 * cannot reach different conclusions from the same row.
 *
 * There is no `label()` here on purpose. The sent emails screen says "On its
 * way" where this says `Sent`, because that is copy belonging to that screen,
 * and the badge needs its tone and its wording together anyway. Naming the case
 * after the fact rather than after the sentence also means rewording the badge
 * never moves the value out from under somebody calling the API.
 */
enum EmailDeliveryStatusEnum: string
{
    case Bounced = 'bounced';
    case Complained = 'complained';
    case Delivered = 'delivered';
    case Sent = 'sent';
}
