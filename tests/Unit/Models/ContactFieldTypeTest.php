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

namespace Tests\Unit\Models;

use Tests\FeatureTestCase;
use App\Models\Account\Account;
use App\Models\Contact\Conversation;
use App\Models\Contact\ContactFieldType;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ContactFieldTypeTest extends FeatureTestCase
{
    use DatabaseTransactions;

    public function test_it_has_many_conversations()
    {
        $contactFieldType = factory(ContactFieldType::class)->create([]);
        $conversation = factory(Conversation::class, 3)->create([
            'account_id' => $contactFieldType->account->id,
            'contact_field_type_id' => $contactFieldType->id,
        ]);

        $this->assertTrue($contactFieldType->conversations()->exists());
    }

    public function test_it_belongs_to_an_account()
    {
        $account = factory(Account::class)->create([]);
        $contactFieldType = factory(ContactFieldType::class)->create([]);
        $conversation = factory(Conversation::class, 3)->create([
            'account_id' => $account->id,
            'contact_field_type_id' => $contactFieldType->id,
        ]);

        $this->assertTrue($contactFieldType->account()->exists());
    }
}
