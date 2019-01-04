<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Helpers\SearchHelper;
use App\Models\Contact\Contact;
use Illuminate\Support\Collection;
use Illuminate\Database\QueryException;
use Illuminate\Validation\ValidationException;
use App\Services\Contact\Contact\CreateContact;
use App\Services\Contact\Contact\UpdateContact;
use App\Services\Contact\Contact\DestroyContact;
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
            $needle = rawurldecode($request->get('query'));

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
     *
     * @param  Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        try {
            $contact = (new CreateContact)->execute(
                $request->all()
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
     * @param  Request $request
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $contactId)
    {
        try {
            $contact = (new UpdateContact)->execute(
                $request->all()
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
     * @param  Request $request
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, $contactId)
    {
        $data = [
            'contact_id' => $contactId,
            'account_id' => auth()->user()->account->id,
        ];
        (new DestroyContact)->execute($data);

        return $this->respondObjectDeleted($contactId);
    }

    /**
     * Apply the `?with=` parameter.
     * @param  Collection $contacts
     * @return Collection
     */
    private function applyWithParameter($contacts, string $parameter = null)
    {
        if ($parameter == 'contactfields') {
            return ContactWithContactFieldsResource::collection($contacts);
        }

        return ContactResource::collection($contacts);
    }
}
