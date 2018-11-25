<?php

namespace App\Http\Controllers\Contacts;

use Illuminate\Http\Request;
use App\Models\Contact\Contact;
use App\Models\Contact\Photo;
use App\Http\Controllers\Controller;
use App\Services\Contact\Photo\UploadPhoto;
use App\Services\Contact\Photo\DestroyPhoto;
use App\Http\Resources\Photo\Photo as PhotoResource;

class PhotosController extends Controller
{
    /**
     * Display the list of photos.
     *
     * @param  Contact $contact
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, Contact $contact)
    {
        $photos = $contact->photos()->get();

        return PhotoResource::collection($photos);
    }

    // /**
    //  * Store the Photo.
    //  *
    //  * @param Request $request
    //  * @param Contact $contact
    //  * @return \Illuminate\Http\Response
    //  */
    // public function store(Request $request, Contact $contact)
    // {
    //     return (new UploadPhoto)->execute([
    //         'account_id' => auth()->user()->account->id,
    //         'contact_id' => $contact->id,
    //         'Photo' => $request->Photo,
    //     ]);
    // }

    // /**
    //  * Delete the Photo.
    //  *
    //  * @param Request $request
    //  * @param Contact $contact
    //  * @param Photo $Photo
    //  * @return \Illuminate\Http\Response
    //  */
    // public function destroy(Request $request, Contact $contact, Photo $Photo)
    // {
    //     $data = [
    //         'account_id' => auth()->user()->account->id,
    //         'Photo_id' => $Photo->id,
    //     ];

    //     try {
    //         (new DestroyPhoto)->execute($data);
    //     } catch (\Exception $e) {
    //         return $this->respondNotFound();
    //     }
    // }
}
