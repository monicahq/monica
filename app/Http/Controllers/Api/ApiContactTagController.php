<?php

namespace App\Http\Controllers\Api;

use App\Models\Contact\Tag;
use Illuminate\Http\Request;
use App\Models\Contact\Contact;
use App\Services\Contact\Tag\DetachTag;
use Illuminate\Support\Facades\Validator;
use App\Services\Contact\Tag\AssociateTag;
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
        $contact = $this->validateTag($request, $contactId);
        if (! $contact instanceof Contact) {
            return $contact;
        }

        $tags = collect($request->input('tags'))
            ->filter(function ($tag) {
                return ! empty($tag);
            });

        foreach ($tags as $tag) {
            app(AssociateTag::class)->execute([
                'account_id' => auth()->user()->account_id,
                'contact_id' => $contact->id,
                'name' => $tag,
            ]);
        }

        return new ContactResource($contact);
    }

    /**
     * Remove all the tags associated with the contact.
     *
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

        $contactTags = $contact->tags()->get();

        foreach ($contactTags as $tag) {
            app(DetachTag::class)->execute([
                'account_id' => auth()->user()->account_id,
                'contact_id' => $contact->id,
                'tag_id' => $tag->id,
            ]);
        }

        return new ContactResource($contact);
    }

    /**
     * Remove one or more specific tags associated with the contact.
     *
     * @param Request $request
     * @param int  $contactId
     */
    public function unsetTag(Request $request, $contactId)
    {
        $contact = $this->validateTag($request, $contactId);
        if (! $contact instanceof Contact) {
            return $contact;
        }

        $tags = collect($request->input('tags'))
            ->filter(function ($tag) {
                return ! empty($tag);
            });

        foreach ($tags as $tag) {
            app(DetachTag::class)->execute([
                'account_id' => auth()->user()->account_id,
                'contact_id' => $contact->id,
                'tag_id' => $tag,
            ]);
        }

        return new ContactResource($contact);
    }

    /**
     * Validate the request for update tag.
     *
     * @param  Request $request
     * @param  int $contactId
     * @return mixed
     */
    private function validateTag(Request $request, $contactId)
    {
        try {
            $contact = Contact::where('account_id', auth()->user()->account_id)
                ->findOrFail($contactId);
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        }

        $validator = Validator::make($request->all(), [
            'tags' => 'required|array',
        ]);

        if ($validator->fails()) {
            return $this->respondValidatorFailed($validator);
        }

        return $contact;
    }
}
