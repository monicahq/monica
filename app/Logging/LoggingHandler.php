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
                $this->logRecord($record, $subscription);
            }
        } catch (ModelNotFoundException) {
            // ignore log
        }
    }

    private function logRecord(LogRecord $record, Loggable $loggable): void
    {
        Log::create([
            'group_id' => $loggable->current_logid ?? 0,
            'level' => $record->level->value,
            'level_name' => $record->level->getName(),
            'channel' => $record->channel,
            'message' => $record->message,
            'context' => count($record->context) > 0 ? $record->context : null,
            'extra' => count($record->extra) > 0 ? $record->extra : null,
            'formatted' => (string) $record->formatted,
            'logged_at' => $record->datetime,
            'loggable_type' => $loggable::class,
            'loggable_id' => $loggable->id,
        ]);
    }
}
