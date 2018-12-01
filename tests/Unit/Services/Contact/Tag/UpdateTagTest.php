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
use App\Services\Contact\Tag\UpdateTag;
use App\Exceptions\MissingParameterException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class UpdateTagTest extends TestCase
{
    use DatabaseTransactions;

    public function test_it_updates_a_tag()
    {
        $tag = factory(Tag::class)->create([]);

        $request = [
            'account_id' => $tag->account->id,
            'tag_id' => $tag->id,
            'name' => 'Central Perk',
        ];

        $updateTagService = new UpdateTag;
        $tag = $updateTagService->execute($request);

        $this->assertDatabaseHas('tags', [
            'id' => $tag->id,
            'name' => 'Central Perk',
            'name_slug' => 'central-perk',
        ]);

        $this->assertInstanceOf(
            Tag::class,
            $tag
        );
    }

    public function test_it_fails_if_wrong_parameters_are_given()
    {
        $request = [
            'account_id' => 1,
            'tag_id' => 2,
        ];

        $this->expectException(MissingParameterException::class);

        $updateTagService = new UpdateTag;
        $tag = $updateTagService->execute($request);
    }

    public function test_it_throws_an_exception_if_tag_does_not_exist()
    {
        $account = factory(Account::class)->create();

        $request = [
            'account_id' => $account->id,
            'tag_id' => 1232322,
            'name' => 'Central Perk',
        ];

        $this->expectException(ModelNotFoundException::class);
        $updateTagService = (new UpdateTag)->execute($request);
    }
}
