<?php

namespace App\Http\Controllers\People;

use App\Contact;
use App\Tag;
use Illuminate\Http\Request;
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
            return response()->json(array('status' => 'no'));
        }

        $tags = explode(',', $request->input('tags'));

        // if we receive an empty string, that means all tags have been removed.
        if ($request->input('tags') == '') {
            $contact->tags()->detach();
            return response()->json(array('status' => 'no'));
        }

        $arrayTags = array();
        foreach ($tags as $tag) {
            $tag = auth()->user()->account->tags()->firstOrCreate([
                'name' => $tag
            ]);

            $tag->name_slug = str_slug($tag->name);
            $tag->save();

            array_push($arrayTags, $tag->id);
        }

        $contact->tags()->sync($arrayTags);

        $response = array(
          'status' => 'yes',
          'tags' => implode(',', $arrayTags),
        );

        return response()->json($response);
    }
}
