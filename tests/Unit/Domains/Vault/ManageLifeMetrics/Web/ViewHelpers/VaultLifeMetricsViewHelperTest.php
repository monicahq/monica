<?php

namespace Tests\Unit\Domains\Vault\ManageLifeMetrics\Web\ViewHelpers;

use App\Domains\Vault\ManageLifeMetrics\Web\ViewHelpers\VaultLifeMetricsViewHelper;
use App\Models\Contact;
use App\Models\LifeMetric;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

use function env;

class VaultLifeMetricsViewHelperTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_gets_information_necessary_to_load_the_view(): void
    {
        $user = $this->createAdministrator();
        $vault = $this->createVault($user->account);

        $array = VaultLifeMetricsViewHelper::data($vault, $user, 2022);

        $this->assertArrayHasKey('data', $array);
        $this->assertArrayHasKey('url', $array);
        $this->assertEquals(
            2,
            count($array)
        );

        $this->assertEquals(
            env('APP_URL').'/vaults/'.$vault->id.'/lifeMetrics',
            $array['url']['store']
        );
    }

    /** @test */
    public function it_gets_the_data_needed_for_the_data_transfer_object(): void
    {
        $user = $this->createAdministrator();
        $vault = $this->createVault($user->account);
        $lifeMetric = LifeMetric::factory()->create([
            'vault_id' => $vault->id,
            'label' => 'Engueulades',
        ]);
        $contact = Contact::factory()->create([
            'vault_id' => $vault->id,
        ]);
        DB::table('contact_life_metric')->insert([
            'contact_id' => $contact->id,
            'life_metric_id' => $lifeMetric->id,
            'created_at' => '2020-01-01 00:00:00',
        ]);
        DB::table('contact_life_metric')->insert([
            'contact_id' => $contact->id,
            'life_metric_id' => $lifeMetric->id,
            'created_at' => '2020-02-01 00:00:00',
        ]);
        DB::table('contact_life_metric')->insert([
            'contact_id' => $contact->id,
            'life_metric_id' => $lifeMetric->id,
            'created_at' => '2020-04-01 00:00:00',
        ]);
        DB::table('contact_life_metric')->insert([
            'contact_id' => $contact->id,
            'life_metric_id' => $lifeMetric->id,
            'created_at' => '2022-04-01 00:00:00',
        ]);

        $array = VaultLifeMetricsViewHelper::dto($lifeMetric, 2020, $contact);

        $this->assertEquals(
            $lifeMetric->id,
            $array['id']
        );
        $this->assertEquals(
            'Engueulades',
            $array['label']
        );
        $this->assertEquals(
            [
                0 => [
                    'year' => 2022,
                ],
                1 => [
                    'year' => 2020,
                ],
            ],
            $array['years']->toArray()
        );
        $this->assertEquals(
            [
                0 => [
                    'id' => 1,
                    'friendly_name' => 'Jan',
                    'events' => 1,
                ],
                1 => [
                    'id' => 2,
                    'friendly_name' => 'Feb',
                    'events' => 1,
                ],
                2 => [
                    'id' => 3,
                    'friendly_name' => 'Mar',
                    'events' => 0,
                ],
                3 => [
                    'id' => 4,
                    'friendly_name' => 'Apr',
                    'events' => 1,
                ],
                4 => [
                    'id' => 5,
                    'friendly_name' => 'May',
                    'events' => 0,
                ],
                5 => [
                    'id' => 6,
                    'friendly_name' => 'Jun',
                    'events' => 0,
                ],
                6 => [
                    'id' => 7,
                    'friendly_name' => 'Jul',
                    'events' => 0,
                ],
                7 => [
                    'id' => 8,
                    'friendly_name' => 'Aug',
                    'events' => 0,
                ],
                8 => [
                    'id' => 9,
                    'friendly_name' => 'Sep',
                    'events' => 0,
                ],
                9 => [
                    'id' => 10,
                    'friendly_name' => 'Oct',
                    'events' => 0,
                ],
                10 => [
                    'id' => 11,
                    'friendly_name' => 'Nov',
                    'events' => 0,
                ],
                11 => [
                    'id' => 12,
                    'friendly_name' => 'Dec',
                    'events' => 0,
                ],
            ],
            $array['months']->toArray()
        );
        $this->assertEquals(
            [
                'store' => env('APP_URL').'/vaults/'.$vault->id.'/lifeMetrics/'.$lifeMetric->id,
                'update' => env('APP_URL').'/vaults/'.$vault->id.'/lifeMetrics/'.$lifeMetric->id,
                'destroy' => env('APP_URL').'/vaults/'.$vault->id.'/lifeMetrics/'.$lifeMetric->id,
            ],
            $array['url']
        );
    }
}
