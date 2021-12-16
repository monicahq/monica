<?php

namespace Tests\Unit\Controllers\Settings\Personalize\Labels\ViewHelpers;

use function env;
use Tests\TestCase;
use App\Models\Label;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Http\Controllers\Settings\Personalize\Labels\ViewHelpers\PersonalizeLabelIndexViewHelper;

class PersonalizeLabelIndexViewHelperTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_gets_the_data_needed_for_the_view(): void
    {
        $label = Label::factory()->create();
        $array = PersonalizeLabelIndexViewHelper::data($label->account);
        $this->assertEquals(
            2,
            count($array)
        );
        $this->assertArrayHasKey('labels', $array);
        $this->assertEquals(
            [
                'settings' => env('APP_URL').'/settings',
                'personalize' => env('APP_URL').'/settings/personalize',
                'label_store' => env('APP_URL').'/settings/personalize/labels',
            ],
            $array['url']
        );
    }

    /** @test */
    public function it_gets_the_data_needed_for_the_data_transfer_object(): void
    {
        $label = Label::factory()->create();
        $array = PersonalizeLabelIndexViewHelper::dtoLabel($label);
        $this->assertEquals(
            [
                'id' => $label->id,
                'name' => $label->name,
                'count' => 0,
                'url' => [
                    'update' => route('settings.personalize.label.update', [
                        'label' => $label->id,
                    ]),
                    'destroy' => route('settings.personalize.label.destroy', [
                        'label' => $label->id,
                    ]),
                ],
            ],
            $array
        );
    }
}
