<?php

declare(strict_types=1);

namespace Tests\Unit\Helpers;

use App\Enums\FieldTypeEnum;
use App\Models\Contact;
use App\Models\Field;
use App\Models\FieldValue;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MaterializedDatabaseEngineTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_writes_what_a_record_says_to_its_search_columns_when_it_is_saved(): void
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            $this->markTestSkipped('SQLite holds a folded copy of the document, which the next test proves.');
        }

        config(['scout.driver' => 'database']);
        $vault = $this->vault();
        $dwight = $this->contact($vault, 'Dwight', 'Schrute', ['Employer' => 'Schrute Farms'], ['Beet recipe' => 'brine']);

        $columns = DB::table('contacts')->where('id', $dwight->id)->first(['search_name', 'search_body', 'search_sensitive_body']);

        $this->assertSame('Dwight Schrute', $columns->search_name);
        $this->assertSame('Schrute Farms', $columns->search_body);
        $this->assertSame('brine', $columns->search_sensitive_body);
    }

    #[Test]
    public function it_writes_a_folded_copy_of_what_a_record_says_on_sqlite(): void
    {
        $this->searchWithLike();
        $oscar = $this->contact($this->vault(), 'Óscar', 'Martínez', ['Email' => 'oscar@dunder-mifflin.com']);

        $columns = DB::table('contacts')->where('id', $oscar->id)->first(['search_name', 'search_body']);

        $this->assertSame('oscar martinez', $columns->search_name);
        $this->assertSame('oscar dunder mifflin com', $columns->search_body);
    }

    #[Test]
    public function it_empties_the_search_columns_of_a_record_taken_out_of_search(): void
    {
        config(['scout.driver' => 'database']);
        $dwight = $this->contact($this->vault(), 'Dwight', 'Schrute');

        $dwight->unsearchable();

        $this->assertNull(DB::table('contacts')->where('id', $dwight->id)->value('search_name'));
    }

    #[Test]
    public function it_finds_a_record_holding_every_word_or_its_start_in_any_order(): void
    {
        $this->searchWithLike();
        $vault = $this->vault();
        $dwight = $this->contact($vault, 'Dwight', 'Schrute', ['Employer' => 'Schrute Farms']);
        $this->contact($vault, 'Jim', 'Halpert', ['Employer' => 'Athlead']);

        $this->assertSame([$dwight->id], Contact::search('farms dwight')->get()->modelKeys());
        $this->assertSame([$dwight->id], Contact::search('schru')->get()->modelKeys());
        $this->assertSame([], Contact::search('farms jim')->get()->modelKeys());
        $this->assertSame([], Contact::search('alpert')->get()->modelKeys());
    }

    #[Test]
    public function it_finds_a_word_after_an_apostrophe_an_at_sign_or_a_dot(): void
    {
        $this->searchWithLike();
        $vault = $this->vault();
        $conan = $this->contact($vault, 'Conan', "O'Brien");
        $jim = $this->contact($vault, 'Jim', 'Halpert', ['Email' => 'jim@dundermifflin.com']);

        $this->assertSame([$conan->id], Contact::search('brien')->get()->modelKeys());
        $this->assertSame([$jim->id], Contact::search('dundermifflin')->get()->modelKeys());
        $this->assertSame([$jim->id], Contact::search('com')->get()->modelKeys());
    }

    #[Test]
    public function it_finds_a_word_whatever_its_accents_and_capitals(): void
    {
        $this->searchWithLike();
        $vault = $this->vault();
        $oscar = $this->contact($vault, 'Óscar', 'Martínez');
        $angela = $this->contact($vault, 'Angela', 'Martin');

        $this->assertSame([$oscar->id], Contact::search('oscar martinez')->get()->modelKeys());
        $this->assertSame([$oscar->id], Contact::search('ÓSCAR')->get()->modelKeys());
        $this->assertSame([$angela->id], Contact::search('ANGÉLA')->get()->modelKeys());
    }

    #[Test]
    public function it_lists_a_record_whose_name_holds_every_word_first(): void
    {
        $this->searchWithLike();
        $vault = $this->vault();
        $strangler = $this->contact($vault, 'Scranton', 'Strangler');
        $pam = $this->contact($vault, 'Pam', 'Beesly', ['City' => 'Scranton']);

        $this->assertSame([$strangler->id, $pam->id], Contact::search('scranton')->get()->modelKeys());
    }

    #[Test]
    public function it_searches_only_the_columns_it_is_told_to(): void
    {
        $this->searchWithLike();
        $this->contact($this->vault(), 'Dwight', 'Schrute', sensitive: ['Beet recipe' => 'brine']);

        $found = Contact::search('brine')
            ->options(['columns' => ['search_name', 'search_body', 'search_linked_names']])
            ->get();

        $this->assertSame([], $found->modelKeys());
    }

    private function vault(): Vault
    {
        $user = User::factory()->create();

        return Vault::factory()->create(['account_id' => $user->account_id]);
    }

    private function searchWithLike(): void
    {
        if (DB::connection()->getDriverName() !== 'sqlite') {
            $this->markTestSkipped('A full-text index sees only committed rows, and RefreshDatabase never commits.');
        }

        config(['scout.driver' => 'database']);
    }

    /**
     * @param  array<string, string>  $values
     * @param  array<string, string>  $sensitive
     */
    private function contact(Vault $vault, string $firstName, string $lastName, array $values = [], array $sensitive = []): Contact
    {
        $contact = Contact::factory()->create([
            'account_id' => $vault->account_id,
            'vault_id' => $vault->id,
            'first_name' => $firstName,
            'last_name' => $lastName,
        ]);

        foreach ([$values, $sensitive] as $isSensitive => $written) {
            foreach ($written as $label => $text) {
                $field = Field::factory()->create([
                    'account_id' => $vault->account_id,
                    'type' => FieldTypeEnum::Text,
                    'label' => $label,
                    'is_sensitive' => $isSensitive === 1,
                ]);

                FieldValue::factory()->create([
                    'valuable_type' => Contact::class,
                    'valuable_id' => $contact->id,
                    'account_id' => $vault->account_id,
                    'vault_id' => $vault->id,
                    'field_id' => $field->id,
                    'value_string' => $text,
                ]);
            }
        }

        return $contact;
    }
}
