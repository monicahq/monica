<?php

namespace Tests\Unit\Models;

use App\Models\Contact;
use App\Models\Template;
use App\Models\TemplatePage;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class TemplateTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_has_one_account()
    {
        $template = Template::factory()->create();

        $this->assertTrue($template->account()->exists());
    }

    /** @test */
    public function it_has_many_template_pages()
    {
        $template = Template::factory()->create();

        $page = TemplatePage::factory()->create([
            'template_id' => $template->id,
        ]);

        $this->assertTrue($template->pages()->exists());
    }

    /** @test */
    public function it_has_many_contacts()
    {
        $template = Template::factory()->create();

        $contact = Contact::factory()->create([
            'template_id' => $template->id,
        ]);

        $this->assertTrue($template->contacts()->exists());
    }

    /** @test */
    public function it_gets_the_default_label()
    {
        $template = Template::factory()->create([
            'name' => null,
            'name_translation_key' => 'bla',
        ]);

        $this->assertEquals(
            'bla',
            $template->name
        );
    }

    /** @test */
    public function it_gets_the_custom_label_if_defined()
    {
        $template = Template::factory()->create([
            'name' => 'this is the real name',
            'name_translation_key' => 'bla',
        ]);

        $this->assertEquals(
            'this is the real name',
            $template->name
        );
    }
}
