<?php

namespace Tests\Unit\Domains\Vault\ManageReports\Web\ViewHelpers;

use App\Domains\Vault\ManageReports\Web\ViewHelpers\ReportImportantDateSummaryIndexViewHelper;
use App\Models\Contact;
use App\Models\ContactImportantDate;
use App\Models\User;
use App\Models\Vault;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ReportImportantDateSummaryIndexViewHelperTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_gets_the_data_needed_for_the_view(): void
    {
        Carbon::setTestNow(Carbon::create(2022, 10, 10));
        $user = User::factory()->create();
        $vault = Vault::factory()->create([
            'account_id' => $user->account_id,
        ]);
        $contact = Contact::factory()->create([
            'vault_id' => $vault->id,
        ]);
        $importantDate = ContactImportantDate::factory()->create([
            'contact_id' => $contact->id,
            'day' => 12,
            'month' => 3,
            'year' => 2024,
            'label' => 'stop it',
        ]);

        $collection = ReportImportantDateSummaryIndexViewHelper::data($vault, $user);

        $this->assertEquals(
            0,
            $collection->toArray()[0]['id']
        );
        $this->assertEquals(
            'Oct 2022',
            $collection->toArray()[0]['month']
        );
        $this->assertEquals(
            [],
            $collection->toArray()[0]['important_dates']->toArray()
        );
        $this->assertEquals(
            $importantDate->id,
            $collection->toArray()[5]['important_dates']->toArray()[0]['id']
        );
        $this->assertEquals(
            'stop it',
            $collection->toArray()[5]['important_dates']->toArray()[0]['label']
        );
        $this->assertEquals(
            'Mar 12, 2024',
            $collection->toArray()[5]['important_dates']->toArray()[0]['happened_at']
        );
    }
}
