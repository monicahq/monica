<?php

namespace App\Http\Controllers\Contacts;

use Illuminate\Http\Request;
use App\Models\Contact\Contact;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Services\Account\Photo\UploadPhoto;

class AvatarController extends Controller
{
    /**
     * Display the Edit avatar screen.
     */
    public function edit(Contact $contact)
    {
        return view('people.avatar.edit')
            ->withContact($contact);
    }

    public function update(Request $request, Contact $contact)
    {
        switch ($request->get('avatar')) {
            case 'adorable':
                $contact->avatar_source = 'adorable';
                break;

            case 'gravatar':
                $contact->avatar_source = 'gravatar';
                break;

            case 'upload':
                $validator = Validator::make($request->all(), [
                    'file' => 'max:10240',
                ]);

                $contact->avatar_source = 'photo';
                if ($validator->fails()) {
                    return back()
                        ->withInput()
                        ->withErrors($validator);
                }

                $photo = (new UploadPhoto)->execute([
                    'account_id' => auth()->user()->account->id,
                    'photo' => $request->photo,
                ]);

                $contact->avatar_photo_id = $photo->id;
        }

        $contact->save();

        return redirect()->route('people.show', $contact)
            ->with('success', trans('people.information_edit_success'));
    }
}
