<?php

namespace Tests\Unit\Models;

use Carbon\Carbon;
use App\Models\User\User;
use Tests\FeatureTestCase;
use App\Models\Contact\Debt;
use App\Models\Account\Photo;
use App\Models\Contact\Gender;
use App\Models\Account\Account;
use App\Models\Contact\Contact;
use App\Models\Contact\Message;
use App\Models\Account\Activity;
use App\Models\Contact\Document;
use App\Models\Contact\Reminder;
use App\Models\Contact\LifeEvent;
use App\Models\Contact\Occupation;
use App\Models\Contact\Conversation;
use App\Models\Instance\SpecialDate;
use App\Notifications\StayInTouchEmail;
use App\Models\Relationship\Relationship;
use App\Jobs\StayInTouch\ScheduleStayInTouch;
use App\Models\Family\Family;
use App\Models\Relationship\RelationshipType;
use App\Models\Relationship\RelationshipTypeGroup;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Notification as NotificationFacade;
use Tests\TestCase;

class FamilyTest extends TestCase
{
    use DatabaseTransactions;

    public function test_it_belongs_to_an_account()
    {
        $family = factory(Family::class)->create([]);

        $this->assertTrue($family->account()->exists());
    }
}
