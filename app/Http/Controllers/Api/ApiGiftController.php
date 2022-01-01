<?php

namespace App\Http\Controllers\Api;

use App\Models\Contact\Gift;
use Illuminate\Http\Request;
use App\Models\Contact\Contact;
use Illuminate\Database\QueryException;
use App\Services\Contact\Gift\CreateGift;
use App\Services\Contact\Gift\UpdateGift;
use App\Services\Contact\Gift\DestroyGift;
use Illuminate\Validation\ValidationException;
use App\Http\Resources\Gift\Gift as GiftResource;
use App\Services\Contact\Gift\AssociatePhotoToGift;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ApiGiftController extends ApiController
{
    /**
     * Get the list of gifts.
     *
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection|\Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        try {
            $gifts = auth()->user()->account->gifts()
                ->orderBy($this->sort, $this->sortDirection)
                ->paginate($this->getLimitPerPage());

            return GiftResource::collection($gifts);
        } catch (QueryException $e) {
            return $this->respondInvalidQuery();
        }
    }

    /**
     * Get the detail of a given gift.
     *
     * @param  Request  $request
     * @return GiftResource|\Illuminate\Http\JsonResponse
     */
    public function show(Request $request, $id)
    {
        try {
            $gift = Gift::where('account_id', auth()->user()->account_id)
                ->findOrFail($id);
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        }

        return new GiftResource($gift);
    }

    /**
     * Store the gift.
     *
     * @param  Request  $request
     * @return GiftResource|\Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        try {
            $gift = app(CreateGift::class)->execute(
                $request->except(['account_id'])
                + ['account_id' => auth()->user()->account_id]
            );

            return new GiftResource($gift);
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        } catch (ValidationException $e) {
            return $this->respondValidatorFailed($e->validator);
        }
    }

    /**
     * Update the gift.
     *
     * @param  Request  $request
     * @param  int  $giftId
     * @return GiftResource|\Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $giftId)
    {
        try {
            $gift = app(UpdateGift::class)->execute(
                $request->except(['account_id', 'gift_id'])
                + [
                    'account_id' => auth()->user()->account_id,
                    'gift_id' => $giftId,
                ]
            );

            return new GiftResource($gift);
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        } catch (ValidationException $e) {
            return $this->respondValidatorFailed($e->validator);
        }
    }

    /**
     * Associate a photo to the gift.
     *
     * @param  Request  $request
     * @param  int  $giftId
     * @param  int  $photoId
     * @return GiftResource|\Illuminate\Http\JsonResponse
     */
    public function associate(Request $request, $giftId, $photoId)
    {
        try {
            $gift = app(AssociatePhotoToGift::class)->execute([
                'account_id' => auth()->user()->account_id,
                'gift_id' => $giftId,
                'photo_id' => $photoId,
            ]);

            return new GiftResource($gift);
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        } catch (ValidationException $e) {
            return $this->respondValidatorFailed($e->validator);
        }
    }

    /**
     * Delete a gift.
     *
     * @param  Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request, $giftId)
    {
        try {
            app(DestroyGift::class)->execute([
                'account_id' => auth()->user()->account_id,
                'gift_id' => $giftId,
            ]);

            return $this->respondObjectDeleted($giftId);
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        } catch (ValidationException $e) {
            return $this->respondValidatorFailed($e->validator);
        }
    }

    /**
     * Get the list of gifts for the given contact.
     *
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection|\Illuminate\Http\JsonResponse
     */
    public function gifts(Request $request, $contactId)
    {
        try {
            $contact = Contact::where('account_id', auth()->user()->account_id)
                ->findOrFail($contactId);
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        }

        try {
            $gifts = $contact->gifts()
                    ->orderBy($this->sort, $this->sortDirection)
                    ->paginate($this->getLimitPerPage());

            return GiftResource::collection($gifts);
        } catch (QueryException $e) {
            return $this->respondInvalidQuery();
        }
    }
}
