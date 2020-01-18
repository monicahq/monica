<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Helpers\SearchHelper;
use App\Models\Contact\Contact;
use Illuminate\Support\Collection;
use Illuminate\Database\QueryException;
use App\Services\Contact\Contact\SetMeContact;
use Illuminate\Validation\ValidationException;
use App\Services\Contact\Contact\CreateContact;
use App\Services\Contact\Contact\UpdateContact;
use App\Services\Contact\Contact\DestroyContact;
use App\Services\Contact\Contact\DeleteMeContact;
use App\Services\Contact\Contact\UpdateContactWork;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Http\Resources\Contact\Contact as ContactResource;
use App\Services\Contact\Contact\UpdateContactIntroductions;
use App\Services\Contact\Contact\UpdateContactFoodPreferences;
use App\Http\Resources\Contact\ContactWithContactFields as ContactWithContactFieldsResource;

class ApiContactController extends ApiController
{
    /**
     * Instantiate a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('limitations')->only('setMe');
        parent::__construct();
    }

    /**
     * Get the list of the contacts.
     * We will only retrieve the contacts that are "real", not the partials
     * ones.
     *
     * @return \Illuminate\Http\Resources\Json\JsonResource|\Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        if ($request->input('query')) {
            $needle = rawurldecode($request->input('query'));

            try {
                $contacts = SearchHelper::searchContacts(
                    $needle,
                    $this->getLimitPerPage(),
                    $this->sort.' '.$this->sortDirection
                );
            } catch (QueryException $e) {
                return $this->respondInvalidQuery();
            }

            $collection = $this->applyWithParameter($contacts, $this->getWithParameter());

            return $collection->additional([
                'meta' => [
                    'query' => $needle,
                ],
            ]);
        }

        try {
            $contacts = auth()->user()->account->contacts()
                            ->real()
                            ->active()
                            ->orderBy($this->sort, $this->sortDirection)
                            ->paginate($this->getLimitPerPage());
        } catch (QueryException $e) {
            return $this->respondInvalidQuery();
        }

        return $this->applyWithParameter($contacts, $this->getWithParameter());
    }

    /**
     * Get the detail of a given contact.
     *
     * @param Request $request
     *
     * @return ContactResource|\Illuminate\Http\JsonResponse|ContactWithContactFieldsResource
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

        $contact->updateConsulted();

        if ($this->getWithParameter() == 'contactfields') {
            return new ContactWithContactFieldsResource($contact);
        }

        return new ContactResource($contact);
    }

    /**
     * Store the contact.
     *
     * @param Request $request
     *
     * @return ContactResource|\Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        try {
            $contact = app(CreateContact::class)->execute(
                $request->except(['account_id'])
                    +
                    [
                        'account_id' => auth()->user()->account->id,
                    ]
            );
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        } catch (ValidationException $e) {
            return $this->respondValidatorFailed($e->validator);
        } catch (QueryException $e) {
            return $this->respondInvalidQuery();
        }

        return new ContactResource($contact);
    }

    /**
     * Update the contact.
     *
     * @param Request $request
     *
     * @return ContactResource|\Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $contactId)
    {
        try {
            $contact = app(UpdateContact::class)->execute(
                $request->except(['account_id', 'contact_id'])
                    +
                    [
                        'contact_id' => $contactId,
                        'account_id' => auth()->user()->account->id,
                    ]
            );
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        } catch (ValidationException $e) {
            return $this->respondValidatorFailed($e->validator);
        } catch (QueryException $e) {
            return $this->respondInvalidQuery();
        }

        return new ContactResource($contact);
    }

    /**
     * Delete a contact.
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request, $contactId)
    {
        $data = [
            'contact_id' => $contactId,
            'account_id' => auth()->user()->account->id,
        ];
        app(DestroyContact::class)->execute($data);

        return $this->respondObjectDeleted($contactId);
    }

    /**
     * Apply the `?with=` parameter.
     * @param  Collection $contacts
     * @return \Illuminate\Http\Resources\Json\JsonResource
     */
    private function applyWithParameter($contacts, string $parameter = null)
    {
        if ($parameter == 'contactfields') {
            return ContactWithContactFieldsResource::collection($contacts);
        }

        return ContactResource::collection($contacts);
    }

    /**
     * Set a contact as 'me'.
     *
     * @param Request $request
     * @param int $contactId
     *
     * @return string
     */
    public function setMe(Request $request, $contactId)
    {
        $data = [
            'contact_id' => $contactId,
            'account_id' => auth()->user()->account_id,
            'user_id' => auth()->user()->id,
        ];

        try {
            app(SetMeContact::class)->execute($data);
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        } catch (ValidationException $e) {
            return $this->respondValidatorFailed($e->validator);
        }

        return $this->respond(['true']);
    }

    /**
     * Removes contact as 'me' association.
     *
     * @param Request $request
     *
     * @return string
     */
    public function removeMe(Request $request)
    {
        $data = [
            'account_id' => auth()->user()->account_id,
            'user_id' => auth()->user()->id,
        ];

        try {
            app(DeleteMeContact::class)->execute($data);
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        } catch (ValidationException $e) {
            return $this->respondValidatorFailed($e->validator);
        }

        return $this->respond(['true']);
    }

    /**
     * Set the contact career.
     *
     * @param Request $request
     * @param int $contactId
     *
     * @return ContactResource|\Illuminate\Http\JsonResponse
     */
    public function updateWork(Request $request, $contactId)
    {
        try {
            $contact = app(UpdateContactWork::class)->execute(
                $request->except(['account_id', 'contact_id'])
                + [
                    'contact_id' => $contactId,
                    'account_id' => auth()->user()->account->id,
                ]
            );
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        } catch (ValidationException $e) {
            return $this->respondValidatorFailed($e->validator);
        } catch (QueryException $e) {
            return $this->respondInvalidQuery();
        }

        return new ContactResource($contact);
    }

    /**
     * Set the contact food preferences.
     *
     * @param Request $request
     * @param int $contactId
     *
     * @return ContactResource|\Illuminate\Http\JsonResponse
     */
    public function updateFoodPreferences(Request $request, $contactId)
    {
        try {
            $contact = app(UpdateContactFoodPreferences::class)->execute(
                $request->except(['account_id', 'contact_id'])
                + [
                    'contact_id' => $contactId,
                    'account_id' => auth()->user()->account->id,
                ]
            );
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        } catch (ValidationException $e) {
            return $this->respondValidatorFailed($e->validator);
        } catch (QueryException $e) {
            return $this->respondInvalidQuery();
        }

        return new ContactResource($contact);
    }

    /**
     * Set how you met the contact.
     *
     * @param Request $request
     * @param int $contactId
     *
     * @return ContactResource|\Illuminate\Http\JsonResponse
     */
    public function updateIntroduction(Request $request, $contactId)
    {
        try {
            $contact = app(UpdateContactIntroductions::class)->execute(
                $request->except(['account_id', 'contact_id'])
                + [
                    'contact_id' => $contactId,
                    'account_id' => auth()->user()->account->id,
                ]
            );
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        } catch (ValidationException $e) {
            return $this->respondValidatorFailed($e->validator);
        } catch (QueryException $e) {
            return $this->respondInvalidQuery();
        }

        return new ContactResource($contact);
    }
}
