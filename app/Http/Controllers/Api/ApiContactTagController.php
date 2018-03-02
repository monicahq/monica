<?php

namespace App\Http\Controllers\Api;

use App\Tag;
use Validator;
use App\Contact;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Http\Resources\Contact\Contact as ContactResource;

class ApiContactTagController extends ApiController
{
    /**
     * Associate one or more tags to the contact.
     * @param Request $request
     * @param int  $contactId
     */
    public function setTags(Request $request, $contactId)
    {
        try {
            $contact = Contact::where('account_id', auth()->user()->account_id)
                ->where('id', $contactId)
                ->firstOrFail();
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        }

        $validator = Validator::make($request->all(), [
            'tags' => 'required|array',
        ]);

        if ($validator->fails()) {
            return $this->setErrorCode(32)
                        ->respondWithError($validator->errors()->all());
        }

        $tags = $request->get('tags');
        foreach ($tags as $tag) {
            $contact->setTag($tag);
        }

        return new ContactResource($contact);
    }

    /**
     * Remove all the tags associated with the contact.
     * @param Request $request
     * @param int  $contactId
     */
    public function unsetTags(Request $request, $contactId)
    {
        try {
            $contact = Contact::where('account_id', auth()->user()->account_id)
                ->where('id', $contactId)
                ->firstOrFail();
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        }

        $contact->unsetTags();

        return new ContactResource($contact);
    }

    /**
     * Remove one or more specific tags associated with the contact.
     * @param Request $request
     * @param int  $contactId
     */
    public function unsetTag(Request $request, $contactId)
    {
        try {
            $contact = Contact::where('account_id', auth()->user()->account_id)
                ->where('id', $contactId)
                ->firstOrFail();
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        }

        $validator = Validator::make($request->all(), [
            'tags' => 'required|array',
        ]);

        if ($validator->fails()) {
            return $this->setErrorCode(32)
                        ->respondWithError($validator->errors()->all());
        }

        $tags = $request->get('tags');
        foreach ($tags as $tag) {
            // does the tag exist?
            try {
                $tag = Tag::where('account_id', auth()->user()->account_id)
                    ->where('id', $tag)
                    ->firstOrFail();
            } catch (ModelNotFoundException $e) {
                return $this->respondNotFound();
            }

            $contact->unsetTag($tag);
        }

        return new ContactResource($contact);
    }
}
