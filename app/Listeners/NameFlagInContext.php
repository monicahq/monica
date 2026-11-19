<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Models\Account;
use Illuminate\Support\Facades\Context;
use Laravel\Pennant\Events\FeatureRetrieved;

class NameFlagInContext
{
    public function handle(FeatureRetrieved $event): void
    {
        if (! $event->scope instanceof Account || $event->value === false) {
            return;
        }

        if (Context::stackContains('flags', $event->feature)) {
            return;
        }

        Context::push('flags', $event->feature);
    }
}
