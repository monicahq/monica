<?php

namespace App\Http\Controllers\People;

use App\Contact;
use App\Tag;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

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
    public function update(Request $request, $contact)
    {
        $tags = explode(',', $request->input('tags'));
        $arrayTags = array();

        foreach ($tags as $tag) {

            // check if the tag already exists for this user. if not, create the
            // tag. if yes, retrieve the tag.
            $tag = auth()->user()->account->tags()->firstOrCreate([
                'name' => $tag
            ]);

            array_push($arrayTags, $tag->id);
        }

        // associate the tag to the user to populate contact_tag many to many table.
        $contact->tags()->sync($arrayTags);

        $response = array(
          'status' => 'yes',
          'tags' => implode(',', $arrayTags),
        );

        return response()->json($response);
    }
}
