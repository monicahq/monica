<?php
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


namespace App\Services\Contact\Tag;

use App\Models\Contact\Tag;
use App\Services\BaseService;
use App\Models\Contact\Contact;

class DetachTag extends BaseService
{
    /**
     * Get the validation rules that apply to the service.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'account_id' => 'required|integer|exists:accounts,id',
            'contact_id' => 'required|integer',
            'tag_id' => 'required|integer',
        ];
    }

    /**
     * Detach the tag associated with a contact.
     *
     * @param array $data
     * @return void
     */
    public function execute(array $data)
    {
        $this->validate($data);

        $contact = Contact::where('account_id', $data['account_id'])
                            ->findOrFail($data['contact_id']);

        Tag::where('account_id', $data['account_id'])
            ->findOrFail($data['tag_id']);

        $contact->tags()->detach($data['tag_id']);
    }
}
