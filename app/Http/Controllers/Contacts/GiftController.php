<?php

namespace App\Http\Controllers\Contacts;

use App\Models\Contact\Gift;
use Illuminate\Http\Request;
use App\Models\Account\Photo;
use App\Models\Contact\Contact;
use App\Http\Controllers\Controller;
use App\Traits\JsonRespondController;
use App\Services\Contact\Gift\CreateGift;
use App\Services\Contact\Gift\UpdateGift;
use App\Services\Contact\Gift\DestroyGift;
use App\Http\Resources\Gift\Gift as GiftResource;
use App\Services\Contact\Gift\AssociatePhotoToGift;

class GiftController extends Controller
{
    use JsonRespondController;

    /**
     * Get the list of gifts for the given contact.
     *
     * @param  Request  $request
     * @param  Contact  $contact
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection|\Illuminate\Http\JsonResponse
     */
    public function index(Request $request, Contact $contact)
    {
        $gifts = $contact->gifts()
                ->orderBy('created_at', 'asc')
                ->paginate();

        return GiftResource::collection($gifts);
    }

    /**
     * Get the detail of a given gift.
     *
     * @param  Request  $request
     * @param  Gift  $gift
     * @return GiftResource|\Illuminate\Http\JsonResponse
     */
    public function show(Request $request, Contact $contact, Gift $gift)
    {
        return new GiftResource($gift);
    }

    /**
     * Store the gift.
     *
     * @param  Request  $request
     * @return GiftResource|\Illuminate\Http\JsonResponse
     */
    public function store(Request $request, Contact $contact)
    {
        $gift = app(CreateGift::class)->execute(
            $request->except(['account_id', 'contact_id']) +
            [
                'account_id' => auth()->user()->account_id,
                'contact_id' => $contact->id,
            ]
        );

        return new GiftResource($gift);
    }

    /**
     * Update the gift.
     *
     * @param  Request  $request
     * @param  Gift  $gift
     * @return GiftResource|\Illuminate\Http\JsonResponse
     */
    public function update(Request $request, Contact $contact, Gift $gift)
    {
        $gift = app(UpdateGift::class)->execute(
            $request->except(['account_id', 'contact_id', 'gift_id']) +
            [
                'account_id' => auth()->user()->account_id,
                'contact_id' => $contact->id,
                'gift_id' => $gift->id,
            ]
        );

        return new GiftResource($gift);
    }

    /**
     * Associate a photo to the gift.
     *
     * @param  Request  $request
     * @param  Gift  $gift
     * @param  Photo  $photo
     * @return GiftResource|\Illuminate\Http\JsonResponse
     */
    public function associate(Request $request, Contact $contact, Gift $gift, Photo $photo)
    {
        $gift = app(AssociatePhotoToGift::class)->execute([
            'account_id' => auth()->user()->account_id,
            'gift_id' => $gift->id,
            'photo_id' => $photo->id,
        ]);

        return new GiftResource($gift);
    }

    /**
     * Delete a gift.
     *
     * @param  Request  $request
     * @param  Gift  $gift
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request, Contact $contact, Gift $gift)
    {
        app(DestroyGift::class)->execute([
            'account_id' => auth()->user()->account_id,
            'gift_id' => $gift->id,
        ]);

        return $this->respondObjectDeleted($gift->id);
    }
}
