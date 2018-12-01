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

namespace Tests\Unit\Services\Contact\Conversation;

use Tests\TestCase;
use App\Models\Contact\Tag;
use App\Models\Account\Account;
use App\Models\Contact\Contact;
use App\Services\Contact\Tag\DestroyTag;
use App\Exceptions\MissingParameterException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class DestroyTagTest extends TestCase
{
    use DatabaseTransactions;

    public function test_it_destroys_a_tag()
    {
        $contact = factory(Contact::class)->create([]);

        $tag = factory(Tag::class)->create([
            'account_id' => $contact->account->id,
        ]);

        $contact->tags()->syncWithoutDetaching([
            $tag->id => [
                'account_id' => $contact->account_id,
            ],
        ]);

        $this->assertDatabaseHas('contact_tag', [
            'account_id' => $contact->account->id,
            'contact_id' => $contact->id,
            'tag_id' => $tag->id,
        ]);

        $request = [
            'account_id' => $contact->account->id,
            'contact_id' => $contact->id,
            'tag_id' => $tag->id,
        ];

        $destroyTagService = new DestroyTag;
        $destroyTagService->execute($request);

        $this->assertDatabaseMissing('contact_tag', [
            'account_id' => $contact->account->id,
            'contact_id' => $contact->id,
        ]);

        $this->assertDatabaseMissing('tags', [
            'account_id' => $contact->account->id,
            'id' => $tag->id,
        ]);
    }

    public function test_it_fails_if_wrong_parameters_are_given()
    {
        $request = [
            'account_id' => 1,
        ];

        $this->expectException(MissingParameterException::class);

        $destroyTagService = new DestroyTag;
        $tag = $destroyTagService->execute($request);
    }

    public function test_it_throws_an_exception_if_tag_does_not_exist()
    {
        $account = factory(Account::class)->create();

        $request = [
            'account_id' => $account->id,
            'tag_id' => 123232,
        ];

        $this->expectException(ModelNotFoundException::class);
        $destroyTagService = (new DestroyTag)->execute($request);
    }
}
