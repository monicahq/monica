<?php

declare(strict_types=1);

use App\Models\Contact;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Contact::withTrashed()
            ->with('account')
            ->chunkById(200, function ($contacts): void {
                foreach ($contacts as $contact) {
                    $contact->save();
                }
            });
    }
};
