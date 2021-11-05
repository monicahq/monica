<?php

namespace App\Http\Controllers\Contacts;

use Illuminate\Http\Request;
use App\Models\Contact\Contact;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Services\Account\Photo\UploadPhoto;
use App\Services\Contact\Avatar\UpdateAvatar;

class AvatarController extends Controller
{
    /**
     * Display the Edit avatar screen.
     */
    public function edit(Contact $contact)
    {
        $contact->throwInactive();

        return view('people.avatar.edit')
            ->withContact($contact);
    }

    /**
     * Update the avatar of the contact.
     *
     * @param  Request  $request
     * @param  Contact  $contact
     */
    public function update(Request $request, Contact $contact)
    {
        // update the avatar
        $data = [
            'account_id' => auth()->user()->account_id,
            'contact_id' => $contact->id,
            'source' => $request->input('avatar'),
        ];

        switch ($request->input('avatar')) {
            case 'upload':
                // if it's a new photo, we need to upload it
                $validator = Validator::make($request->all(), [
                    'file' => 'image|max:'.config('monica.max_upload_size'),
                ]);

                if ($validator->fails()) {
                    return back()
                        ->withInput()
                        ->withErrors($validator);
                }

                $photo = app(UploadPhoto::class)->execute([
                    'account_id' => auth()->user()->account_id,
                    'contact_id' => $contact->id,
                    'photo' => $request->photo,
                ]);

                $data['photo_id'] = $photo->id;
                $data['source'] = 'photo';
            break;
            case 'photo':
                $data['photo_id'] = $contact->avatar_photo_id;
            break;
        }

        app(UpdateAvatar::class)->execute($data);

        return redirect()->route('people.show', $contact)
            ->with('success', trans('people.information_edit_success'));
    }

    /**
     * Set the given photo as avatar.
     *
     * @param  Request  $request
     * @param  Contact  $contact
     * @param  int  $photoId
     */
    public function photo(Request $request, Contact $contact, $photoId)
    {
        // update the avatar
        $data = [
            'account_id' => auth()->user()->account_id,
            'contact_id' => $contact->id,
            'source' => 'photo',
            'photo_id' => $photoId,
        ];

        return app(UpdateAvatar::class)->execute($data);
    }
}
