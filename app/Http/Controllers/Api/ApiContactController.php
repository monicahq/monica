<?php

namespace App\Http\Controllers\Api;

use Validator;
use App\Contact;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Http\Controllers\Controller;
use App\Http\Resources\Contact as ContactResource;
use \Illuminate\Database\Eloquent\ModelNotFoundException;

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
     * Get the detail of a given contact
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
     * Store the contact
     * @param  Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        // Validates basic fields to create the entry
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|max:50',
            'middle_name' => 'nullable|max:100',
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
            'linkedin_profile_url' => 'nullable|max:255'
        ]);

        if ($validator->fails()) {
           return $this->setErrorCode(32)
                        ->respondWithError($validator->errors()->all());
        }

        $contact = Contact::create($request->all());
        $contact->account_id = auth()->user()->account->id;
        $contact->save();

        $contact->setBirthday(
            $request->get('is_birthdate_approximate'),
            $request->get('birthdate'),
            $request->get('age')
        );

        $contact->setAvatarColor();

        $contact->logEvent('contact', $contact->id, 'update');

        return new ContactResource($contact);
    }
}
