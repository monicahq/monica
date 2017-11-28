<?php

namespace App\Http\Controllers\Contacts;

use App\Contact;
use App\Http\Controllers\Controller;
use App\Http\Requests\People\TagsRequest;

class TagsController extends Controller
{
    /**
     * Update the specified resource in storage.
     *
     * @param GiftsRequest $request
     * @param Contact $contact
     * @param Gift $gift
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
            $contact->tags()->detach();

            return response()->json(['status' => 'no', 'tags' => '']);
        }

        $tagsIDs = [];
        $tagsWithIdAndSlug = [];
        foreach ($tags as $tag) {
            $tag = auth()->user()->account->tags()->firstOrCreate([
                'name' => $tag,
            ]);

            $tag->name_slug = str_slug($tag->name);
            $tag->save();

            $tagsIDs[$tag->id] = ['account_id' => auth()->user()->account_id];

            // this is passed back in json to JS
            array_push($tagsWithIdAndSlug, [
              'id' => $tag->id,
              'slug' => $tag->name_slug,
            ]);
        }

        $contact->tags()->sync($tagsIDs);

        $response = [
          'status' => 'yes',
          'tags' => $tagsWithIdAndSlug,
        ];

        return response()->json($response);
    }
}
