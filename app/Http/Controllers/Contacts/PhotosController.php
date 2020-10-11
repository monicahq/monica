<?php

namespace App\Http\Controllers\Contacts;

use Illuminate\Http\Request;
use App\Models\Account\Photo;
use App\Models\Contact\Contact;
use App\Http\Controllers\Controller;
use App\Traits\JsonRespondController;
use App\Services\Account\Photo\UploadPhoto;
use App\Services\Account\Photo\DestroyPhoto;
use App\Services\Contact\Avatar\UpdateAvatar;
use App\Http\Resources\Photo\Photo as PhotoResource;

class PhotosController extends Controller
{
    use JsonRespondController;

    /**
     * Display the list of photos.
     *
     * @param Contact $contact
     *
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index(Request $request, Contact $contact)
    {
        $photos = $contact->photos()->orderBy('created_at', 'desc')->get();

        return PhotoResource::collection($photos);
    }

    /**
     * Store the Photo.
     *
     * @param Request $request
     * @param Contact $contact
     *
     * @return PhotoResource
     */
    public function store(Request $request, Contact $contact): PhotoResource
    {
        $photo = app(UploadPhoto::class)->execute([
            'account_id' => auth()->user()->account_id,
            'contact_id' => $contact->id,
            'photo' => $request->photo,
        ]);

        return new PhotoResource($photo);
    }

    /**
     * Delete the Photo.
     * Also, if this photo was the current avatar of the contact, change the
     * avatar to the default one.
     *
     * @param Request $request
     * @param Contact $contact
     * @param Photo $photo
     *
     * @return null|\Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request, Contact $contact, Photo $photo)
    {
        $data = [
            'account_id' => auth()->user()->account_id,
            'photo_id' => $photo->id,
        ];

        try {
            app(DestroyPhoto::class)->execute($data);
        } catch (\Exception $e) {
            return $this->respondNotFound();
        }

        if ($contact->avatar_source == 'photo'
            && $contact->avatar_photo_id == $photo->id) {
            app(UpdateAvatar::class)->execute([
                'account_id' => auth()->user()->account_id,
                'contact_id' => $contact->id,
                'source' => 'adorable',
            ]);
        }

        return $this->respondObjectDeleted($photo->id);
    }
}
