<?php

namespace App\Logging;

use App\Models\AddressBookSubscription;
use App\Models\Log;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\LogRecord;

class LoggingHandler extends AbstractProcessingHandler
{
    protected function write(LogRecord $record): void
    {
        $context = $record->context;

        try {
            if (isset($context['addressbook_subscription_id'])) {
                $subscription = AddressBookSubscription::findOrFail($context['addressbook_subscription_id']);
                $this->logAddressBookSubscription($record, $subscription);
            }
        } catch (ModelNotFoundException) {
            // ignore log
        }
    }

    private function logAddressBookSubscription(LogRecord $record, AddressBookSubscription $subscription): void
    {
        Log::create([
            'group_id' => $subscription->current_logid ?? 0,
            'level' => $record->level->value,
            'level_name' => $record->level->getName(),
            'channel' => $record->channel,
            'message' => $record->message,
            'context' => json_encode($record->context),
            'extra' => json_encode($record->extra),
            'formatted' => (string) $record->formatted,
            'logged_at' => $record->datetime,
            'loggable_type' => AddressBookSubscription::class,
            'loggable_id' => $subscription->id,
        ]);
    }
}
