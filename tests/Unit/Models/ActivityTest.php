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

use Carbon\Carbon;
use Tests\TestCase;
use App\Models\Contact\Activity;
use App\Models\Contact\ActivityType;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ActivityTest extends TestCase
{
    use DatabaseTransactions;

    public function testGetDescriptionReturnsNullIfUndefined()
    {
        $activity = new Activity;

        $this->assertNull($activity->getDescription());
    }

    public function testGetDescriptionReturnsDescription()
    {
        $activity = new Activity;
        $activity->description = 'This is a test';

        $this->assertEquals(
            'This is a test',
            $activity->getDescription()
        );
    }

    public function testGetDateItHappenedReturnsCarbonObject()
    {
        $activity = factory(Activity::class)->make();

        $this->assertInstanceOf(Carbon::class, $activity->date_it_happened);
    }

    public function testGetTitleReturnsAString()
    {
        $type = factory(ActivityType::class)->create();

        $activity = factory(Activity::class)->create([
            'activity_type_id' => $type->id,
        ]);

        $this->assertEquals($type->key, $activity->getTitle());
    }

    public function test_get_info_for_journal_entry()
    {
        $activity = factory(Activity::class)->create();

        $data = [
            'type' => 'activity',
            'id' => $activity->id,
            'activity_type' => (! is_null($activity->type) ? $activity->type->name : null),
            'summary' => $activity->summary,
            'description' => $activity->description,
            'day' => $activity->date_it_happened->day,
            'day_name' => $activity->date_it_happened->format('D'),
            'month' => $activity->date_it_happened->month,
            'month_name' => strtoupper($activity->date_it_happened->format('M')),
            'year' => $activity->date_it_happened->year,
            'attendees' => $activity->getContactsForAPI(),
        ];

        $this->assertEquals(
            $data,
            $activity->getInfoForJournalEntry()
        );
    }
}
