<?php

namespace App\Http\Controllers\Contacts;

use Illuminate\Http\Request;
use App\Models\Contact\Contact;
use App\Http\Controllers\Controller;
use App\Http\Requests\People\TagsRequest;
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
        $tagsCollection = collect();
        foreach ($tags as $tag) {
            $data = [
                $tag->id => $tag->name,
            ];
            $tagsCollection->push($data);
        }

        return $tagsCollection->toJson();
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
        $tagString = '';
        foreach ($tags as $tag) {
            $tagString .= $tag->name;
        }
        return $tagString;
    }

    /**
     * Update the specified resource in storage.
     *
     * @param TagsRequest $request
     * @param Contact $contact
     *
     * @return \Illuminate\Http\Response
     */
    public function update(TagsRequest $request, Contact $contact)
    {
        if (auth()->user()->account_id != $contact->account_id) {
            return response()->json(['status' => 'no']);
        }

        $tags = explode(',', $request->input('tags'));

        // if we receive an empty string, that means all tags have been removed.
        if ($request->input('tags') == '') {
            $contact->unsetTags();

            return response()->json(['status' => 'no', 'tags' => '']);
        }

        // remove old tags if there are not to keep
        foreach ($contact->tags()->get() as $tag) {
            if (! in_array($tag->name, $tags)) {
                $contact->unsetTag($tag);
            }
        }

        $tagsWithIdAndSlug = [];
        foreach ($tags as $tag) {
            $tag = $contact->setTag($tag);

            // this is passed back in json to JS
            array_push($tagsWithIdAndSlug, [
              'id' => $tag->id,
              'slug' => $tag->name_slug,
              'name' => $tag->name,
            ]);
        }

        $response = [
          'status' => 'yes',
          'tags' => $tagsWithIdAndSlug,
        ];

        return response()->json($response);
    }
}
