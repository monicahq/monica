<?php

namespace App\Http\Controllers\Contacts;

use Illuminate\Http\Request;
use App\Models\Contact\Contact;
use App\Http\Controllers\Controller;
use App\Services\Contact\Tag\AssociateTag;
use App\Services\Contact\Tag\DestroyTags;
use App\Http\Resources\Tag\Tag as TagResource;

class TagsController extends Controller
{
    /**
     * Get the list of all the tags in the account.
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $tags = auth()->user()->account->tags()->get();

        return TagResource::collection($tags);
    }

    /**
     * Get the list of all the tags for this contact.
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function get(Request $request, Contact $contact)
    {
        $tags = $contact->tags()->get();

        return TagResource::collection($tags);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param Contact $contact
     *
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Contact $contact)
    {
        $tags = $request->all();

        // destroy all the tags associated with this contact (so we can
        // recreate all tag associations).
        (new DestroyTags)->execute([
            'account_id' => auth()->user()->account->id,
            'contact_id' => $contact->id,
        ]);

        // associate all tags
        foreach ($tags as $tag) {
            (new AssociateTag)->execute([
                'account_id' => auth()->user()->account->id,
                'contact_id' => $contact->id,
                'name' => $tag['name'],
            ]);
        }
    }
}
