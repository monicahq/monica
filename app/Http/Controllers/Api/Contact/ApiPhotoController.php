<?php

namespace App\Http\Controllers\Api\Contact;

use Illuminate\Http\Request;
use App\Models\Account\Photo;
use App\Models\Contact\Contact;
use Illuminate\Database\QueryException;
use App\Http\Controllers\Api\ApiController;
use App\Services\Account\Photo\UploadPhoto;
use App\Services\Account\Photo\DestroyPhoto;
use Illuminate\Validation\ValidationException;
use App\Http\Resources\Photo\Photo as PhotoResource;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ApiPhotoController extends ApiController
{
    /**
     * Get the list of photos.
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection|\Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        try {
            $photos = auth()->user()->account->photos()
                ->orderBy($this->sort, $this->sortDirection)
                ->paginate($this->getLimitPerPage());
        } catch (QueryException $e) {
            return $this->respondInvalidQuery();
        }

        return PhotoResource::collection($photos);
    }

    /**
     * Get the list of photos for a specific contact.
     *
     * @param Request $request
     * @param int $contactId
     *
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection|\Illuminate\Http\JsonResponse
     */
    public function contact(Request $request, $contactId)
    {
        try {
            $contact = Contact::where('account_id', auth()->user()->account_id)
                ->findOrFail($contactId);
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        }

        try {
            $photos = $contact->photos()
                ->orderBy($this->sort, $this->sortDirection)
                ->paginate($this->getLimitPerPage());
        } catch (QueryException $e) {
            return $this->respondInvalidQuery();
        }

        return PhotoResource::collection($photos);
    }

    /**
     * Get the detail of a given photo.
     *
     * @param Request $request
     * @param int $photoId
     *
     * @return PhotoResource|\Illuminate\Http\JsonResponse
     */
    public function show(Request $request, $photoId)
    {
        try {
            $photo = Photo::where('account_id', auth()->user()->account_id)
                ->findOrFail($photoId);
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        }

        return new PhotoResource($photo);
    }

    /**
     * Store a photo.
     *
     * @param Request $request
     *
     * @return PhotoResource|\Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        try {
            $photo = app(UploadPhoto::class)->execute(
                $request->except(['account_id'])
                +
                [
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

        return new PhotoResource($photo);
    }

    /**
     * Destroy a photo.
     *
     * @param Request $request
     * @param int $photoId
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request, $photoId)
    {
        try {
            app(DestroyPhoto::class)->execute([
                'account_id' => auth()->user()->account_id,
                'photo_id' => $photoId,
            ]);
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        } catch (ValidationException $e) {
            return $this->respondValidatorFailed($e->validator);
        } catch (QueryException $e) {
            return $this->respondInvalidQuery();
        }

        return $this->respondObjectDeleted((int) $photoId);
    }
}
