<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Vaults;

use App\Models\Contact;
use App\Models\ContactNameRepresentation;
use App\ViewModels\Vaults\ContactNameEditorBlock;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ContactNameEditorBlockTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_starts_from_every_way_the_name_is_written_and_the_one_shown(): void
    {
        $contact = Contact::factory()->create(['first_name' => 'Michael', 'last_name' => 'Scott']);
        $katakana = ContactNameRepresentation::factory()->transliteration()->create([
            'contact_id' => $contact->id,
            'script_code' => 'Kana',
            'formatted_name' => 'マイケル・スコット',
        ]);
        $contact->display_name_representation_id = $katakana->id;
        $contact->save();

        $block = new ContactNameEditorBlock(contact: $contact->refresh());
        $rows = $block->rows();

        $this->assertCount(2, $rows);
        $this->assertSame('Michael', $rows[0]['given_name']);
        $this->assertSame($katakana->uuid, $rows[1]['id']);
        $this->assertSame('transliteration', $rows[1]['kind']);
        $this->assertSame(1, $block->display());
        $this->assertTrue($block->hadAName());
    }

    #[Test]
    public function it_starts_from_the_refused_form_rather_than_the_stored_names(): void
    {
        $contact = Contact::factory()->create(['first_name' => 'Michael', 'last_name' => 'Scott']);
        request()->setLaravelSession(session()->driver());
        session()->put('_old_input', ['names' => [['kind' => 'written', 'given_name' => 'Prison', 'family_name' => 'Mike']], 'display' => '0']);

        $block = new ContactNameEditorBlock(contact: $contact);

        $this->assertSame('Prison', $block->rows()[0]['given_name']);
        $this->assertSame(0, $block->display());
    }

    #[Test]
    public function it_starts_a_new_contact_with_nothing_written(): void
    {
        $block = new ContactNameEditorBlock;

        $this->assertSame([], $block->rows());
        $this->assertNull($block->display());
        $this->assertFalse($block->hadAName());
    }

    #[Test]
    public function it_names_the_languages_and_writing_systems_in_the_language_of_the_reader(): void
    {
        $this->app->setLocale('fr');

        $block = new ContactNameEditorBlock;

        $this->assertSame('Coréen', $block->languages()['ko']);
        $this->assertArrayHasKey('Latn', $block->scripts());
    }

    #[Test]
    public function it_keeps_offering_a_code_stored_from_elsewhere(): void
    {
        $contact = Contact::factory()->create(['first_name' => null, 'last_name' => null]);
        $cantonese = ContactNameRepresentation::factory()->phonetic('jyut')->create([
            'contact_id' => $contact->id,
            'language_tag' => 'yue',
            'script_code' => 'Hant',
            'formatted_name' => 'Gam Man Zi',
        ]);
        $contact->display_name_representation_id = $cantonese->id;
        $contact->save();

        $block = new ContactNameEditorBlock(contact: $contact->refresh());

        $this->assertArrayHasKey('yue', $block->languages());
        $this->assertArrayHasKey('Hant', $block->scripts());
        $this->assertSame('jyut', $block->phoneticSystems()['jyut']);
    }

    #[Test]
    public function it_offers_no_language_or_writing_system_a_refused_form_got_wrong(): void
    {
        $contact = Contact::factory()->create(['first_name' => 'Chandler', 'last_name' => 'Bing']);
        request()->setLaravelSession(session()->driver());
        session()->put('_old_input', ['names' => [['kind' => 'written', 'given_name' => 'Chandler', 'language' => str_repeat('a', 200), 'script' => 'latin alphabet']], 'display' => '0']);

        $block = new ContactNameEditorBlock(contact: $contact);

        $this->assertArrayNotHasKey(str_repeat('a', 200), $block->languages());
        $this->assertArrayNotHasKey('latin alphabet', $block->scripts());
        $this->assertArrayHasKey('ko', $block->languages());
    }
}
