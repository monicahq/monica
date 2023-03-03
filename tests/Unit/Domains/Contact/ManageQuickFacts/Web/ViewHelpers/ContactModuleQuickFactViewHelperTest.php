<?php

namespace Tests\Unit\Domains\Contact\ManageQuickFacts\Web\ViewHelpers;

use App\Domains\Contact\ManageQuickFacts\Web\ViewHelpers\ContactModuleQuickFactViewHelper;
use App\Models\Contact;
use App\Models\QuickFact;
use App\Models\VaultQuickFactTemplate;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ContactModuleQuickFactViewHelperTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_gets_the_data_needed_for_the_view(): void
    {
        $contact = Contact::factory()->create();
        $template = VaultQuickFactTemplate::factory()->create();

        $array = ContactModuleQuickFactViewHelper::data($contact, $template);

        $this->assertEquals(
            3,
            count($array)
        );

        $this->assertArrayHasKey('template', $array);
        $this->assertArrayHasKey('quick_facts', $array);
        $this->assertArrayHasKey('url', $array);

        $this->assertEquals(
            [
                'store' => env('APP_URL').'/vaults/'.$contact->vault->id.'/contacts/'.$contact->id.'/quickFacts/'.$template->id,
            ],
            $array['url']
        );
    }

    /** @test */
    public function it_gets_the_data_transfer_object(): void
    {
        $contact = Contact::factory()->create();
        $template = VaultQuickFactTemplate::factory()->create();
        $quickFact = QuickFact::factory()->create([
            'vault_quick_facts_template_id' => $template->id,
            'contact_id' => $contact->id,
        ]);

        $array = ContactModuleQuickFactViewHelper::dto($quickFact);

        $this->assertEquals(
            [
                'id' => $quickFact->id,
                'content' => $quickFact->content,
                'url' => [
                    'update' => env('APP_URL').'/vaults/'.$contact->vault->id.'/contacts/'.$contact->id.'/quickFacts/'.$template->id.'/'.$quickFact->id,
                    'destroy' => env('APP_URL').'/vaults/'.$contact->vault->id.'/contacts/'.$contact->id.'/quickFacts/'.$template->id.'/'.$quickFact->id,
                ],
            ],
            $array
        );
    }
}
