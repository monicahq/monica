/**
  *  This file is part of Monica.
  *
  *  Monica is free software: you can redistribute it and/or modify
  *  it under the terms of the GNU Affero General Public License as published by
  *  the Free Software Foundation, either version 3 of the License, or
  *  (at your option) any later version.
  *
  *  Monica is distributed in the hope that it will be useful,
  *  but WITHOUT ANY WARRANTY; without even the implied warranty of
  *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  *  GNU Affero General Public License for more details.
  *
  *  You should have received a copy of the GNU Affero General Public License
  *  along with Monica.  If not, see <https://www.gnu.org/licenses/>.
  **/


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

        $tags = $request->get('tags');
        foreach ($tags as $tag) {
            (new AssociateTag)->execute([
                'account_id' => auth()->user()->account->id,
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
            (new DetachTag)->execute([
                'account_id' => auth()->user()->account->id,
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

        $tags = $request->get('tags');
        foreach ($tags as $tag) {
            (new DetachTag)->execute([
                'account_id' => auth()->user()->account->id,
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
                ->where('id', $contactId)
                ->firstOrFail();
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
