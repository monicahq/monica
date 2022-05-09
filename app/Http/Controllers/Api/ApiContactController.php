<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Helpers\SearchHelper;
use App\Models\Contact\Contact;
use Illuminate\Http\JsonResponse;
use App\Jobs\UpdateLastConsultedDate;
use Illuminate\Database\QueryException;
use Illuminate\Validation\ValidationException;
use App\Services\Contact\Contact\CreateContact;
use App\Services\Contact\Contact\UpdateContact;
use App\Services\Contact\Contact\DestroyContact;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Services\Contact\Contact\UpdateWorkInformation;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Http\Resources\Contact\Contact as ContactResource;
use App\Services\Contact\Contact\UpdateContactIntroduction;
use App\Services\Contact\Contact\UpdateContactFoodPreferences;

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
     * @param  Request  $request
     * @return JsonResource|JsonResponse
     */
    public function index(Request $request)
    {
        if ($request->input('query')) {
            $needle = rawurldecode($request->input('query'));

            try {
                $contacts = SearchHelper::searchContacts(
                    $needle,
                    $this->sort,
                    $this->sortDirection
                )
                    ->real()
                    ->paginate($this->getLimitPerPage());
            } catch (QueryException $e) {
                return $this->respondInvalidQuery();
            }

            return ContactResource::collection($contacts)->additional([
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

        return ContactResource::collection($contacts);
    }

    /**
     * Get the detail of a given contact.
     *
     * @param  Request  $request
     * @param  int  $id
     * @return ContactResource|JsonResponse
     */
    public function show(Request $request, int $id)
    {
        try {
            $contact = Contact::where('account_id', auth()->user()->account_id)
                ->where('id', $id)
                ->firstOrFail();
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        }

        UpdateLastConsultedDate::dispatch($contact);

        return new ContactResource($contact);
    }

    /**
     * Store the contact.
     *
     * @param  Request  $request
     * @return ContactResource|JsonResponse
     */
    public function store(Request $request)
    {
        try {
            $contact = app(CreateContact::class)->execute(
                $request->except(['account_id'])
                    +
                    [
                        'account_id' => auth()->user()->account_id,
                        'author_id' => auth()->user()->id,
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
     * @param  Request  $request
     * @return ContactResource|JsonResponse
     */
    public function update(Request $request, $contactId)
    {
        try {
            $contact = app(UpdateContact::class)->execute(
                $request->except(['account_id', 'contact_id'])
                    +
                    [
                        'contact_id' => $contactId,
                        'account_id' => auth()->user()->account_id,
                        'author_id' => auth()->user()->id,
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
     * @param  Request  $request
     * @return JsonResponse
     */
    public function destroy(Request $request, $contactId)
    {
        $data = [
            'account_id' => auth()->user()->account_id,
            'contact_id' => $contactId,
        ];
        DestroyContact::dispatch($data);

        return $this->respondObjectDeleted($contactId);
    }

    /**
     * Set the contact career.
     *
     * @param  Request  $request
     * @param  int  $contactId
     * @return ContactResource|JsonResponse
     */
    public function updateWork(Request $request, $contactId)
    {
        try {
            $contact = app(UpdateWorkInformation::class)->execute(
                $request->except(['account_id', 'contact_id'])
                + [
                    'contact_id' => $contactId,
                    'account_id' => auth()->user()->account_id,
                    'author_id' => auth()->user()->id,
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
     * @param  Request  $request
     * @param  int  $contactId
     * @return ContactResource|JsonResponse
     */
    public function updateFoodPreferences(Request $request, $contactId)
    {
        try {
            $contact = app(UpdateContactFoodPreferences::class)->execute(
                $request->except(['account_id', 'contact_id'])
                + [
                    'contact_id' => $contactId,
                    'account_id' => auth()->user()->account_id,
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
     * @param  Request  $request
     * @param  int  $contactId
     * @return ContactResource|JsonResponse
     */
    public function updateIntroduction(Request $request, $contactId)
    {
        try {
            $contact = app(UpdateContactIntroduction::class)->execute(
                $request->except(['account_id', 'contact_id'])
                + [
                    'contact_id' => $contactId,
                    'account_id' => auth()->user()->account_id,
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
