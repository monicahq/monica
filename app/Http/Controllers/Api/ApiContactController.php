<?php

namespace App\Http\Controllers\Api;

use DB;
use Validator;
use App\Contact;
use Illuminate\Http\Request;
use App\Helpers\SearchHelper;
use Illuminate\Database\QueryException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Http\Resources\Contact\Contact as ContactResource;
use App\Http\Resources\Contact\ContactWithContactFields as ContactWithContactFieldsResource;

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
        if ($request->get('query')) {
            $needle = $request->get('query');

            try {
                $contacts = SearchHelper::searchContacts(
                    $needle,
                    $this->getLimitPerPage(),
                    $this->sort.' '.$this->sortDirection
                );
            } catch (QueryException $e) {
                return $this->respondInvalidQuery();
            }

            return ContactResource::collection($contacts)->additional(['meta' => [
                    'query' => $needle,
                ]]);
        }

        try {
            $contacts = auth()->user()->account->contacts()->real()
                            ->orderBy($this->sort, $this->sortDirection)
                            ->paginate($this->getLimitPerPage());
        } catch (QueryException $e) {
            return $this->respondInvalidQuery();
        }

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

        if ($this->getWithParameter() == 'contactfields') {
            return new ContactWithContactFieldsResource($contact);
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
        $isvalid = $this->validateUpdate($request);
        if ($isvalid !== true) {
            return $isvalid;
        }

        // Create the contact
        try {
            $contact = Contact::create(
                $request->only([
                    'first_name',
                    'last_name',
                    'gender_id',
                    'job',
                    'company',
                    'food_preferencies',
                    'linkedin_profile_url',
                    'first_met_through_contact_id',
                    'is_partial',
                    'is_dead',
                    'deceased_date',
                ]) + [
                'avatar_external_url' => $request->get('avatar_url'),
            ]);
        } catch (QueryException $e) {
            return $this->respondNotTheRightParameters();
        }

        if ($request->get('avatar_url')) {
            $contact->has_avatar = true;
            $contact->avatar_location = 'external';
        }

        if ($request->get('first_met_information')) {
            $contact->first_met_additional_info = $request->get('first_met_information');
        }

        $contact->account_id = auth()->user()->account->id;
        $contact->save();

        // birthdate
        if ($request->get('birthdate')) {

            // in this case, we know the month and day, but not necessarily the year
            $date = \Carbon\Carbon::parse($request->get('birthdate'));

            if ($request->get('birthdate_is_year_unknown')) {
                $specialDate = $contact->setSpecialDate('birthdate', 0, $date->month, $date->day);
            } else {
                $specialDate = $contact->setSpecialDate('birthdate', $date->year, $date->month, $date->day);
                $specialDate->setReminder('year', 1, trans('people.people_add_birthday_reminder', ['name' => $contact->first_name]));
            }
        } elseif ($request->get('birthdate_is_age_based')) {
            $specialDate = $contact->setSpecialDateFromAge('birthdate', $request->input('birthdate_age'));
        }

        // first met date
        if ($request->get('first_met_date')) {

            // in this case, we know the month and day, but not necessarily the year
            $date = \Carbon\Carbon::parse($request->get('first_met_date'));

            if ($request->get('first_met_date_is_year_unknown')) {
                $specialDate = $contact->setSpecialDate('first_met', 0, $date->month, $date->day);
            } else {
                $specialDate = $contact->setSpecialDate('first_met', $date->year, $date->month, $date->day);
                $specialDate->setReminder('year', 1, trans('people.people_add_birthday_reminder', ['name' => $contact->first_name]));
            }
        } elseif ($request->get('first_met_date_is_age_based')) {
            $specialDate = $contact->setSpecialDateFromAge('first_met', $request->input('first_met_date_age'));
        }

        // deceased date
        if ($request->get('deceased_date')) {

            // in this case, we know the month and day, but not necessarily the year
            $date = \Carbon\Carbon::parse($request->get('deceased_date'));

            if ($request->get('deceased_date_is_year_unknown')) {
                $specialDate = $contact->setSpecialDate('deceased_date', 0, $date->month, $date->day);
            } else {
                $specialDate = $contact->setSpecialDate('deceased_date', $date->year, $date->month, $date->day);
                $specialDate->setReminder('year', 1, trans('people.people_add_birthday_reminder', ['name' => $contact->first_name]));
            }
        } elseif ($request->get('deceased_date_is_age_based')) {
            $specialDate = $contact->setSpecialDateFromAge('deceased_date', $request->input('deceased_date_age'));
        }

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

        $isvalid = $this->validateUpdate($request);
        if ($isvalid !== true) {
            return $isvalid;
        }

        // Update the contact
        try {
            $contact->update($request->all());
        } catch (QueryException $e) {
            return $this->respondNotTheRightParameters();
        }

        if ($request->get('first_met_information')) {
            $contact->first_met_additional_info = $request->get('first_met_information');
        } else {
            $contact->first_met_additional_info = null;
        }

        $contact->save();

        // birthdate
        $contact->removeSpecialDate('birthdate');
        if ($request->get('birthdate')) {

            // in this case, we know the month and day, but not necessarily the year
            $date = \Carbon\Carbon::parse($request->get('birthdate'));

            if ($request->get('birthdate_is_year_unknown')) {
                $specialDate = $contact->setSpecialDate('birthdate', 0, $date->month, $date->day);
            } else {
                $specialDate = $contact->setSpecialDate('birthdate', $date->year, $date->month, $date->day);
                $specialDate->setReminder('year', 1, trans('people.people_add_birthday_reminder', ['name' => $contact->first_name]));
            }
        } elseif ($request->get('birthdate_is_age_based')) {
            $specialDate = $contact->setSpecialDateFromAge('birthdate', $request->input('birthdate_age'));
        }

        // first met date
        $contact->removeSpecialDate('first_met');
        if ($request->get('first_met_date')) {

            // in this case, we know the month and day, but not necessarily the year
            $date = \Carbon\Carbon::parse($request->get('first_met_date'));

            if ($request->get('first_met_date_is_year_unknown')) {
                $specialDate = $contact->setSpecialDate('first_met', 0, $date->month, $date->day);
            } else {
                $specialDate = $contact->setSpecialDate('first_met', $date->year, $date->month, $date->day);
                $specialDate->setReminder('year', 1, trans('people.people_add_birthday_reminder', ['name' => $contact->first_name]));
            }
        } elseif ($request->get('first_met_date_is_age_based')) {
            $specialDate = $contact->setSpecialDateFromAge('first_met', $request->input('first_met_date_age'));
        }

        // deceased date
        $contact->removeSpecialDate('deceased_date');
        if ($request->get('deceased_date')) {

            // in this case, we know the month and day, but not necessarily the year
            $date = \Carbon\Carbon::parse($request->get('deceased_date'));

            if ($request->get('deceased_date_is_year_unknown')) {
                $specialDate = $contact->setSpecialDate('deceased_date', 0, $date->month, $date->day);
            } else {
                $specialDate = $contact->setSpecialDate('deceased_date', $date->year, $date->month, $date->day);
                $specialDate->setReminder('year', 1, trans('people.people_add_birthday_reminder', ['name' => $contact->first_name]));
            }
        } elseif ($request->get('deceased_date_is_age_based')) {
            $specialDate = $contact->setSpecialDateFromAge('deceased_date', $request->input('deceased_date_age'));
        }

        $contact->logEvent('contact', $contact->id, 'update');

        return new ContactResource($contact);
    }

    /**
     * Validate the request for update.
     *
     * @param  Request $request
     * @return mixed
     */
    private function validateUpdate(Request $request)
    {
        // Validates basic fields to create the entry
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|max:50',
            'last_name' => 'nullable|max:100',
            'gender_id' => 'integer|required',
            'birthdate' => 'nullable|date',
            'birthdate_is_age_based' => 'boolean',
            'birthdate_is_year_unknown' => 'boolean',
            'birthdate_age' => 'nullable|integer',
            'job' => 'nullable|max:255',
            'company' => 'nullable|max:255',
            'food_preferencies' => 'nullable|max:100000',
            'linkedin_profile_url' => 'nullable|max:255',
            'first_met_information' => 'nullable|max:1000000',
            'first_met_date' => 'nullable|date',
            'first_met_date_is_age_based' => 'boolean',
            'first_met_date_is_year_unknown' => 'boolean',
            'first_met_date_age' => 'nullable|integer',
            'first_met_through_contact_id' => 'nullable|integer',
            'is_partial' => 'required|boolean',
            'is_dead' => 'required|boolean',
            'deceased_date' => 'nullable|date',
            'deceased_date_is_age_based' => 'boolean',
            'deceased_date_is_year_unknown' => 'boolean',
            'deceased_date_age' => 'nullable|integer',
            'avatar_url' => 'nullable|max:400',
        ]);

        if ($validator->fails()) {
            return $this->setErrorCode(32)
                        ->respondWithError($validator->errors()->all());
        }

        // Make sure the `first_met_through_contact_id` is a contact id that the
        // user is authorized to access
        if ($request->get('first_met_through_contact_id')) {
            try {
                Contact::where('account_id', auth()->user()->account_id)
                    ->where('id', $request->input('first_met_through_contact_id'))
                    ->firstOrFail();
            } catch (ModelNotFoundException $e) {
                return $this->respondNotFound();
            }
        }

        return true;
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

        $tables = DB::select('SELECT table_name FROM information_schema.tables WHERE table_schema="monica"');
        foreach ($tables as $table) {
            $tableName = $table->table_name;
            $tableData = DB::table($tableName)->get();

            $contactIdRowExists = false;
            foreach ($tableData as $data) {
                foreach ($data as $columnName => $value) {
                    if ($columnName == 'contact_id') {
                        $contactIdRowExists = true;
                    }
                }
            }

            if ($contactIdRowExists) {
                DB::table($tableName)->where('contact_id', $contact->id)->delete();
            }
        }

        $contact->delete();

        return $this->respondObjectDeleted($contact->id);
    }

    /**
     * Link a partner to an existing contact.
     */
    public function partners(Request $request, $contactId)
    {
        $validate = $this->validatePartners($request, $contactId);
        if (! is_array($validate)) {
            return $validate;
        }
        list($contact, $partner) = $validate;

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
        $validate = $this->validatePartners($request, $contactId);
        if (! is_array($validate)) {
            return $validate;
        }
        list($contact, $partner) = $validate;

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
     * Validate the request for update Partners.
     *
     * @param  Request $request
     * @param  int $contactId
     * @return mixed
     */
    private function validatePartners(Request $request, $contactId)
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

        return [$contact, $partner];
    }

    /**
     * Link a child to an existing contact.
     */
    public function kids(Request $request, $contactId)
    {
        $validate = $this->validatePartners($request, $contactId);
        if (! is_array($validate)) {
            return $validate;
        }
        list($contact, $kid) = $validate;

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
        $validate = $this->validatePartners($request, $contactId);
        if (! is_array($validate)) {
            return $validate;
        }
        list($contact, $kid) = $validate;

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

    /**
     * Validate the request for update kids.
     *
     * @param  Request $request
     * @param  int $contactId
     * @return mixed
     */
    private function validateKids(Request $request, $contactId)
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

        return [$contact, $kid];
    }
}
