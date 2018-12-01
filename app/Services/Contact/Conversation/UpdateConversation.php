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

/**
 * This is a single action class, totally inspired by
 * https://medium.com/@remi_collin/keeping-your-laravel-applications-dry-with-single-action-classes-6a950ec54d1d.
 */

namespace App\Services\Contact\Conversation;

use App\Services\BaseService;
use App\Models\Contact\Conversation;
use App\Models\Contact\ContactFieldType;

class UpdateConversation extends BaseService
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
            'happened_at' => 'required|date',
            'contact_field_type_id' => 'required|integer',
            'conversation_id' => 'required|integer',
        ];
    }

    /**
     * Update a conversation.
     *
     * @param array $data
     * @return Conversation
     */
    public function execute(array $data): Conversation
    {
        $this->validate($data);

        $conversation = Conversation::where('account_id', $data['account_id'])
                                    ->findOrFail($data['conversation_id']);

        ContactFieldType::where('account_id', $data['account_id'])
                            ->findOrFail($data['contact_field_type_id']);

        $conversation->update([
            'happened_at' => $data['happened_at'],
            'contact_field_type_id' => $data['contact_field_type_id'],
        ]);

        return $conversation;
    }
}
