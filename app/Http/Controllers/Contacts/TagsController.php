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


namespace App\Http\Controllers\Contacts;

use Illuminate\Http\Request;
use App\Models\Contact\Contact;
use App\Http\Controllers\Controller;
use App\Services\Contact\Tag\DetachTag;
use App\Services\Contact\Tag\AssociateTag;
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

        // detaching all the tags
        $contactTags = $contact->tags()->get();
        foreach ($contactTags as $tag) {
            (new DetachTag)->execute([
                'account_id' => auth()->user()->account->id,
                'contact_id' => $contact->id,
                'tag_id' => $tag->id,
            ]);
        }

        // attach all the new/updated tags
        foreach ($tags as $tag) {
            (new AssociateTag)->execute([
                'account_id' => auth()->user()->account->id,
                'contact_id' => $contact->id,
                'name' => $tag['name'],
            ]);
        }
    }
}
