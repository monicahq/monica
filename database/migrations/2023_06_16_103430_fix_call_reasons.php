<?php

use App\Models\CallReasonType;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        CallReasonType::all()->each(function (CallReasonType $type) {
            $reasons = $type->callReasons()->get();
            if ($type->label_translation_key === 'Personal') {
                $reasons[0]->updateQuietly([
                    'label_translation_key' => 'For advice',
                ]);
                $reasons[1]->updateQuietly([
                    'label_translation_key' => 'Just to say hello',
                ]);
                $reasons[2]->updateQuietly([
                    'label_translation_key' => 'To see if they need anything',
                ]);
                $reasons[3]->updateQuietly([
                    'label_translation_key' => 'Out of respect and appreciation',
                ]);
                $reasons[4]->updateQuietly([
                    'label_translation_key' => 'To hear their story',
                ]);
                try {
                    $reasons[5]->delete();
                } catch (\Exception $e) {
                    // ignore
                }
            } elseif ($type->label_translation_key === 'Business') {
                $reasons[0]->updateQuietly([
                    'label_translation_key' => 'Discuss recent purchases',
                ]);
                $reasons[1]->updateQuietly([
                    'label_translation_key' => 'Discuss partnership',
                ]);
            }
        });
    }
};
