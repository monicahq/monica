<?php

declare(strict_types=1);

use App\Enums\ContactNameKindEnum;
use App\Models\Contact;
use App\Models\ContactNameRepresentation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/*
 * Give every contact with a name the one way of writing it that it already had, and show it.
 * Written quietly: the name each contact is shown under does not change, so there is nothing to
 * send to search and nothing new to compare for duplicates, and a save per contact would queue both.
 */
return new class extends Migration
{
    public function up(): void
    {
        Contact::withTrashed()
            ->with('account')
            ->whereNull('display_name_representation_id')
            ->where(function (Builder $query): void {
                foreach (['prefix', 'first_name', 'middle_name', 'last_name', 'suffix'] as $column) {
                    $query->orWhereNotNull($column);
                }
            })
            ->chunkById(500, function (Collection $contacts): void {
                foreach ($contacts as $contact) {
                    $this->backfill($contact);
                }
            });
    }

    private function backfill(Contact $contact): void
    {
        $representation = new ContactNameRepresentation([
            'kind' => ContactNameKindEnum::Written,
            'prefix' => $contact->prefix,
            'given_name' => $contact->first_name,
            'additional_name' => $contact->middle_name,
            'family_name' => $contact->last_name,
            'suffix' => $contact->suffix,
            'name_order' => $contact->name_order,
            'position' => 1,
        ]);
        $representation->contact()->associate($contact);
        $representation->uuid = $representation->newUniqueId();
        $representation->writeKeys();
        $representation->saveQuietly();

        DB::table('contacts')->where('id', $contact->id)->update(['display_name_representation_id' => $representation->id]);
    }
};
