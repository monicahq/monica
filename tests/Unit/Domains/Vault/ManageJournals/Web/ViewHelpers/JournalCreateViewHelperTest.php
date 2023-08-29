<?php

namespace Tests\Unit\Domains\Vault\ManageJournals\Web\ViewHelpers;

use App\Domains\Vault\ManageJournals\Web\ViewHelpers\JournalCreateViewHelper;
use App\Models\Vault;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

use function env;

class JournalCreateViewHelperTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_gets_the_data_needed_for_the_view(): void
    {
        $vault = Vault::factory()->create();

        $array = JournalCreateViewHelper::data($vault);
        $this->assertEquals(
            [
                'url' => [
                    'store' => env('APP_URL').'/vaults/'.$vault->id.'/journals',
                    'back' => env('APP_URL').'/vaults/'.$vault->id.'/journals',
                ],
            ],
            $array
        );
    }
}
