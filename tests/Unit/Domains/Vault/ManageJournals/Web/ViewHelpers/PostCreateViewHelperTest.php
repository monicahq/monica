<?php

namespace Tests\Unit\Domains\Vault\ManageJournals\Web\ViewHelpers;

use App\Domains\Vault\ManageJournals\Web\ViewHelpers\PostCreateViewHelper;
use App\Models\Journal;
use App\Models\PostTemplate;
use App\Models\PostTemplateSection;
use App\Models\Vault;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

use function env;

class PostCreateViewHelperTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_gets_the_data_needed_for_the_view(): void
    {
        $vault = Vault::factory()->create();
        $journal = Journal::factory()->create([
            'vault_id' => $vault->id,
        ]);
        $postTemplate = PostTemplate::factory()->create([
            'account_id' => $vault->account_id,
        ]);
        $section = PostTemplateSection::factory()->create([
            'post_template_id' => $postTemplate->id,
        ]);

        $array = PostCreateViewHelper::data($journal);

        $this->assertCount(3, $array);
        $this->assertEquals(
            [
                'name' => $journal->name,
            ],
            $array['journal']
        );
        $this->assertEquals(
            [
                'back' => env('APP_URL').'/vaults/'.$vault->id.'/journals/'.$journal->id,
            ],
            $array['url']
        );
        $this->assertEquals(
            $postTemplate->id,
            $array['templates']->toArray()[0]['id']
        );
        $this->assertEquals(
            $postTemplate->label,
            $array['templates']->toArray()[0]['label']
        );
        $this->assertEquals(
            [
                'create' => env('APP_URL').'/vaults/'.$vault->id.'/journals/'.$journal->id.'/posts/template/'.$postTemplate->id,
            ],
            $array['templates']->toArray()[0]['url']
        );
        $this->assertEquals(
            [
                0 => [
                    'id' => $section->id,
                    'label' => $section->label,
                ],
            ],
            $array['templates']->toArray()[0]['sections']->toArray()
        );
    }
}
