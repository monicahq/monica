<?php

namespace App\Http\Controllers\Contacts;

use App\Models\Contact\Contact;
use App\Models\Contact\Interest;
use Illuminate\Support\Collection;
use App\Http\Controllers\Controller;
use App\Http\Requests\People\InterestsRequest;

class InterestsController extends Controller
{
    /**
     * @param Contact $contact
     * @return Collection
     */
    public function index(Contact $contact): Collection
    {
        return $contact
            ->interests
            ->map(
                function ($interest) {
                    return [
                        'id' => $interest->id,
                        'name' => $interest->name,
                        'edit' => false,
                    ];
                }
            );
    }

    /**
     * @param InterestsRequest $request
     * @param Contact $contact
     * @return array
     */
    public function store(
        InterestsRequest $request,
        Contact $contact
    ) {
        $interest = $contact
            ->interests()
            ->create(
                $request->merge(
                    [
                        'account_id' => auth()->user()->account_id,
                    ]
                )->only([
                    'name',
                    'account_id',
                ])
            );

        return [
            'id' => $interest->id,
            'name' => $interest->name,
            'edit' => false,
        ];
    }

    /**
     * @param InterestsRequest $request
     * @param Interest $interest
     * @return array
     */
    public function update(
        InterestsRequest $request,
        Interest $interest
    ) {
        $interest->update($request->all());

        return [
            'id' => $interest->id,
            'name' => $interest->name,
            'edit' => false,
        ];
    }

    /**
     * @param Interest $interest
     * @throws \Exception
     */
    public function destroy(
        Interest $interest
    ) {
        $interest->delete();
    }
}
