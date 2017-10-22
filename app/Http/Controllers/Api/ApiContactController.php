<?php

namespace App\Http\Controllers\Api;

use Validator;
use App\Contact;
use App\Offspring;
use App\Progenitor;
use App\Relationship;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Database\QueryException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Http\Resources\Contact\Contact as ContactResource;

class ApiContactController extends ApiController
{
    /**
     * Get the list of the contacts.
     * We will only retrieve the contacts that are "real", not the partials
     * ones.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $contacts = auth()->user()->account->contacts()->real()
                                            ->paginate($this->getLimitPerPage());

        return ContactResource::collection($contacts);
    }

    /**
     * Get the detail of a given contact.
     * @param  Request $request
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, $id)
    {
        try {
            $contact = Contact::where('account_id', auth()->user()->account_id)
                ->where('id', $id)
                ->firstOrFail();
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        }

        return new ContactResource($contact);
    }

    /**
     * Store the contact.
     * @param  Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        // Validates basic fields to create the entry
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|max:50',
            'last_name' => 'nullable|max:100',
            'gender' => 'required',
            'birthdate' => 'nullable|date',
            'is_birthdate_approximate' => [
                'nullable',
                Rule::in(['exact', 'approximate', 'unknown']),
            ],
            'age' => 'nullable|integer',
            'email' => 'nullable|email|max:255',
            'phone_number' => 'nullable|max:255',
            'job' => 'nullable|max:255',
            'company' => 'nullable|max:255',
            'street' => 'nullable|max:255',
            'city' => 'nullable|max:255',
            'province' => 'nullable|max:255',
            'postal_code' => 'nullable|max:255',
            'country_id' => 'nullable|integer|max:255',
            'food_preferencies' => 'nullable|max:100000',
            'facebook_profile_url' => 'nullable|max:255',
            'twitter_profile_url' => 'nullable|max:255',
            'linkedin_profile_url' => 'nullable|max:255',
            'is_partial' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return $this->setErrorCode(32)
                        ->respondWithError($validator->errors()->all());
        }

        if ($request->input('email') != '') {
            $otherContact = Contact::where('email', $request->input('email'))
                                    ->count();

            if ($otherContact > 0) {
                return $this->setErrorCode(35)
                        ->setHTTPStatusCode(500)
                        ->respondWithError(trans('people.people_edit_email_error'));
            }
        }

        try {
            $contact = Contact::create($request->all());
        } catch (QueryException $e) {
            return $this->respondNotTheRightParameters();
        }

        $contact->account_id = auth()->user()->account->id;
        $contact->save();

        $contact->setBirthday(
            $request->get('is_birthdate_approximate'),
            $request->get('birthdate'),
            $request->get('age')
        );

        $contact->setAvatarColor();

        $contact->logEvent('contact', $contact->id, 'create');

        return new ContactResource($contact);
    }

    /**
     * Update the contact.
     * @param  Request $request
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $contactId)
    {
        try {
            $contact = Contact::where('account_id', auth()->user()->account_id)
                ->where('id', $contactId)
                ->firstOrFail();
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        }

        // Validates basic fields to create the entry
        $validator = Validator::make($request->all(), [
            'first_name' => 'nullable|max:50',
            'last_name' => 'nullable|max:100',
            'gender' => 'nullable',
            'birthdate' => 'nullable|date',
            'is_birthdate_approximate' => [
                'nullable',
                Rule::in(['exact', 'approximate', 'unknown']),
            ],
            'age' => 'nullable|integer',
            'email' => 'nullable|email|max:255',
            'phone_number' => 'nullable|max:255',
            'job' => 'nullable|max:255',
            'company' => 'nullable|max:255',
            'street' => 'nullable|max:255',
            'city' => 'nullable|max:255',
            'province' => 'nullable|max:255',
            'postal_code' => 'nullable|max:255',
            'country_id' => 'nullable|integer|max:255',
            'food_preferencies' => 'nullable|max:100000',
            'facebook_profile_url' => 'nullable|max:255',
            'twitter_profile_url' => 'nullable|max:255',
            'linkedin_profile_url' => 'nullable|max:255',
            'is_partial' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return $this->setErrorCode(32)
                        ->respondWithError($validator->errors()->all());
        }

        try {
            $contact->update($request->all());
        } catch (QueryException $e) {
            return $this->respondNotTheRightParameters();
        }

        $contact->setBirthday(
            $request->get('is_birthdate_approximate'),
            $request->get('birthdate'),
            $request->get('age')
        );

        $contact->logEvent('contact', $contact->id, 'update');

        return new ContactResource($contact);
    }

    /**
     * Delete a contact.
     * @param  Request $request
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, $id)
    {
        try {
            $contact = Contact::where('account_id', auth()->user()->account_id)
                ->where('id', $id)
                ->firstOrFail();
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        }

        $contact->activities->each->delete();
        $contact->calls->each->delete();
        $contact->debts->each->delete();
        $contact->events->each->delete();
        $contact->gifts->each->delete();
        $contact->notes->each->delete();
        $contact->reminders->each->delete();
        $contact->tags->each->delete();
        $contact->tasks->each->delete();

        // delete all relationships
        $relationships = Relationship::where('contact_id', $contact->id)
                                    ->orWhere('with_contact_id', $contact->id)
                                    ->get();

        foreach ($relationships as $relationship) {
            $relationship->delete();
        }

        // delete all offsprings
        $offsprings = Offspring::where('contact_id', $contact->id)
                                ->orWhere('is_the_child_of', $contact->id)
                                ->get();

        foreach ($offsprings as $offspring) {
            $offspring->delete();
        }

        // delete all progenitors
        $progenitors = Progenitor::where('contact_id', $contact->id)
                                ->orWhere('is_the_parent_of', $contact->id)
                                ->get();

        foreach ($progenitors as $progenitor) {
            $progenitor->delete();
        }

        $contact->delete();

        return $this->respondObjectDeleted($contact->id);
    }

    /**
     * Link a partner to an existing contact.
     */
    public function partners(Request $request, $contactId)
    {
        try {
            $contact = Contact::where('account_id', auth()->user()->account_id)
                ->where('id', $contactId)
                ->firstOrFail();
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        }

        // Make sure the contact is not a partial contact so we can actually
        // associate him/her a partner
        if ($contact->is_partial) {
            return $this->setErrorCode(36)
                        ->respondWithError('You can\'t set a partner or a child to a partial contact');
        }

        // Validates basic fields to create the entry
        $validator = Validator::make($request->all(), [
            'partner_id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return $this->setErrorCode(32)
                        ->respondWithError($validator->errors()->all());
        }

        try {
            $partner = Contact::where('account_id', auth()->user()->account_id)
                ->where('id', $request->input('partner_id'))
                ->firstOrFail();
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        }

        if ($partner->is_partial) {
            $contact->setRelationshipWith($partner);
        } else {
            $contact->setRelationshipWith($partner, true);
            $partner->logEvent('contact', $partner->id, 'create');
        }

        return new ContactResource($contact);
    }

    /**
     * Unlink a partner from an existing contact.
     */
    public function unsetPartners(Request $request, $contactId)
    {
        try {
            $contact = Contact::where('account_id', auth()->user()->account_id)
                ->where('id', $contactId)
                ->firstOrFail();
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        }

        // Validates basic fields to create the entry
        $validator = Validator::make($request->all(), [
            'partner_id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return $this->setErrorCode(32)
                        ->respondWithError($validator->errors()->all());
        }

        try {
            $partner = Contact::where('account_id', auth()->user()->account_id)
                ->where('id', $request->input('partner_id'))
                ->firstOrFail();
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        }

        if ($partner->is_partial) {
            if ($partner->reminders) {
                $partner->reminders()->get()->each->delete();
            }

            $contact->unsetRelationshipWith($partner);
            $partner->delete();
        } else {
            $contact->unsetRelationshipWith($partner, true);
        }

        return new ContactResource($contact);
    }

    /**
     * Link a child to an existing contact.
     */
    public function kids(Request $request, $contactId)
    {
        try {
            $contact = Contact::where('account_id', auth()->user()->account_id)
                ->where('id', $contactId)
                ->firstOrFail();
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        }

        // Make sure the contact is not a partial contact so we can actually
        // associate him/her a partner
        if ($contact->is_partial) {
            return $this->setErrorCode(36)
                        ->respondWithError('You can\'t set a partner or a child to a partial contact');
        }

        // Validates basic fields to create the entry
        $validator = Validator::make($request->all(), [
            'child_id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return $this->setErrorCode(32)
                        ->respondWithError($validator->errors()->all());
        }

        try {
            $kid = Contact::where('account_id', auth()->user()->account_id)
                ->where('id', $request->input('child_id'))
                ->firstOrFail();
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        }

        if ($kid->is_partial) {
            $kid->isTheOffspringOf($contact);
        } else {
            $kid->isTheOffspringOf($contact, true);
            $kid->logEvent('contact', $kid->id, 'create');
        }

        return new ContactResource($contact);
    }

    /**
     * Unlink a partner from an existing contact.
     */
    public function unsetKids(Request $request, $contactId)
    {
        try {
            $contact = Contact::where('account_id', auth()->user()->account_id)
                ->where('id', $contactId)
                ->firstOrFail();
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        }

        // Validates basic fields to create the entry
        $validator = Validator::make($request->all(), [
            'child_id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return $this->setErrorCode(32)
                        ->respondWithError($validator->errors()->all());
        }

        try {
            $kid = Contact::where('account_id', auth()->user()->account_id)
                ->where('id', $request->input('child_id'))
                ->firstOrFail();
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        }

        if ($kid->is_partial) {
            if ($kid->reminders) {
                $kid->reminders()->get()->each->delete();
            }

            $contact->unsetOffspring($kid);
            $kid->delete();
        } else {
            $contact->unsetOffspring($kid, true);
        }

        return new ContactResource($contact);
    }
}
