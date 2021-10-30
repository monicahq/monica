<?php

namespace App\Http\Controllers\Api\Contact;

use Illuminate\Http\Request;
use Illuminate\Database\QueryException;
use App\Http\Controllers\Api\ApiController;
use App\Services\Contact\Avatar\UpdateAvatar;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Http\Resources\Contact\Contact as ContactResource;

class ApiAvatarController extends ApiController
{
    /**
     * Update a contact's avatar.
     *
     * @param  Request  $request
     * @param  int  $contactId
     * @return ContactResource|\Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $contactId)
    {
        try {
            $contact = app(UpdateAvatar::class)->execute(
                $request->except(['account_id', 'contact_id'])
                +
                [
                    'account_id' => auth()->user()->account_id,
                    'contact_id' => $contactId,
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
