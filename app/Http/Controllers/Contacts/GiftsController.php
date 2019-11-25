<?php

namespace App\Http\Controllers\Contacts;

use App\Helpers\DateHelper;
use App\Helpers\MoneyHelper;
use App\Models\Contact\Gift;
use App\Models\Contact\Contact;
use App\Http\Controllers\Controller;
use App\Http\Requests\People\GiftsRequest;

class GiftsController extends Controller
{
    /**
     * List all the gifts for the given contact.
     *
     * @param Contact $contact
     *
     * @return \Illuminate\Support\Collection
     */
    public function index(Contact $contact)
    {
        $giftsCollection = collect([]);
        $gifts = $contact->gifts()->get();

        foreach ($gifts as $gift) {
            $value = $gift->value;

            if ($gift->value == '') {
                $value = 0;
            }

            $data = [
                'contact_hash' => $contact->hashID(),
                'id' => $gift->id,
                'name' => $gift->name,
                'recipient_name' => $gift->recipient_name,
                'comment' => $gift->comment,
                'url' => $gift->url,
                'value' => MoneyHelper::format($value),
                'does_value_exist' => (bool) $value,
                'is_an_idea' => $gift->is_an_idea,
                'has_been_offered' => $gift->has_been_offered,
                'has_been_received' => $gift->has_been_received,
                'offered_at' => DateHelper::getShortDate($gift->offered_at),
                'received_at' => DateHelper::getShortDate($gift->received_at),
                'created_at' => DateHelper::getShortDate($gift->created_at),
                'edit' => false,
                'show_comment' => false,
            ];
            $giftsCollection->push($data);
        }

        return $giftsCollection;
    }

    /**
     * Mark a gift as being offered.
     * @param  Contact $contact
     * @param  Gift    $gift
     * @return Gift
     */
    public function toggle(Contact $contact, Gift $gift)
    {
        $gift->toggle();

        return $gift;
    }

    /**
     * Show the form for creating a new resource.
     *
     * @param Contact $contact
     *
     * @return \Illuminate\View\View
     */
    public function create(Contact $contact)
    {
        $familyRelationships = $contact->getRelationshipsByRelationshipTypeGroup('family');

        return view('people.gifts.add')
            ->withContact($contact)
            ->withFamilyRelationships($familyRelationships)
            ->withGift(new Gift);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param GiftsRequest $request
     * @param Contact $contact
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(GiftsRequest $request, Contact $contact)
    {
        $this->updateOrCreate($request, $contact);

        return redirect()->route('people.show', $contact)
            ->with('success', trans('people.gifts_add_success'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param Contact $contact
     * @param Gift $gift
     *
     * @return \Illuminate\View\View
     */
    public function edit(Contact $contact, Gift $gift)
    {
        $familyRelationships = $contact->getRelationshipsByRelationshipTypeGroup('family');

        return view('people.gifts.edit')
            ->withContact($contact)
            ->withFamilyRelationships($familyRelationships)
            ->withGift($gift);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param GiftsRequest $request
     * @param Contact $contact
     * @param Gift $gift
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(GiftsRequest $request, Contact $contact, Gift $gift)
    {
        $this->updateOrCreate($request, $contact, $gift);

        return redirect()->route('people.show', $contact)
            ->with('success', trans('people.gifts_update_success'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Contact $contact
     * @param Gift $gift
     *
     * @return void
     */
    public function destroy(Contact $contact, Gift $gift): void
    {
        $gift->delete();
    }

    /**
     * Save resource in storage.
     *
     * @param GiftsRequest $request
     * @param Contact $contact
     *
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function updateOrCreate(GiftsRequest $request, Contact $contact, Gift $gift = null)
    {
        $array =
            $request->only([
                'name',
                'comment',
                'url',
                'value',
            ])
            + [
                'account_id' => $contact->account_id,
                'is_an_idea' => ($request->get('offered') == 'idea' ? 1 : 0),
                'has_been_offered' => ($request->get('offered') == 'offered' ? 1 : 0),
                'has_been_received' => ($request->get('offered') == 'received' ? 1 : 0),
            ];

        if (is_null($gift)) {
            $gift = $contact->gifts()->create($array);
        } else {
            $gift->update($array);
        }

        if ($request->get('has_recipient')
            && Contact::where('account_id', auth()->user()->account_id)
                ->find($request->get('recipient')) != null) {
            $gift->recipient = $request->get('recipient');
            $gift->save();
        }

        return $gift;
    }
}
