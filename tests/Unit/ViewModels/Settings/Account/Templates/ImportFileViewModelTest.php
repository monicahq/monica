<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Settings\Account\Templates;

use App\Helpers\StructureFile;
use App\Helpers\StructureFileReader;
use App\Models\EntityType;
use App\Models\StructureVersion;
use App\Models\Template;
use App\Models\User;
use App\Models\ValueList;
use App\ViewModels\Settings\Account\Templates\ImportFileViewModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ImportFileViewModelTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_reads_as_the_door_a_template_comes_through(): void
    {
        $viewModel = new ImportFileViewModel(user: User::factory()->create());

        $this->assertFalse($viewModel->isForASection());
        $this->assertEquals('Import a template', $viewModel->title());
        $this->assertEquals('Nothing pasted yet', $viewModel->counterLabel());
        $this->assertFalse($viewModel->isChecked());
        $this->assertFalse($viewModel->isRefused());
        $this->assertCount(3, $viewModel->steps());
    }

    #[Test]
    public function it_reads_as_the_door_a_section_comes_through(): void
    {
        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        $version = StructureVersion::factory()->create(['account_id' => $user->account_id, 'structure_id' => $template->structure->id]);

        $viewModel = new ImportFileViewModel(
            user: $user,
            template: $template,
            page: $version->pages()->firstOrFail(),
        );

        $this->assertTrue($viewModel->isForASection());
        $this->assertEquals('Import a section', $viewModel->title());
        $this->assertStringContainsString('sections/import', $viewModel->checkUrl());
    }

    #[Test]
    public function it_counts_what_was_pasted(): void
    {
        $viewModel = new ImportFileViewModel(user: User::factory()->create(), raw: "one\ntwo");

        $this->assertEquals('2 lines · 7 characters', $viewModel->counterLabel());
    }

    #[Test]
    public function it_says_what_was_found_in_a_template(): void
    {
        $user = User::factory()->create();
        $viewModel = new ImportFileViewModel(user: $user, checked: StructureFileReader::read($this->file()));

        $found = collect($viewModel->found())->pluck('value', 'label');

        $this->assertEquals('A whole template', $found['Kind of file']);
        $this->assertEquals('Basic contact', $found['Name it will take']);
        $this->assertEquals('1 page, 1 section, 2 fields', $found['Structure']);
        $this->assertEquals('None', $found['Value lists']);
        $this->assertEquals('None', $found['Kinds of record']);
        $this->assertEquals('Monica structure format '.StructureFile::FORMAT_VERSION, $found['Format']);
        $this->assertEquals('None', $found['Records inside']);
    }

    #[Test]
    public function it_says_the_lists_of_choices_the_account_already_has(): void
    {
        $user = User::factory()->create();
        ValueList::factory()->create(['account_id' => $user->account_id, 'key' => 'branches']);

        $file = json_decode($this->file(), true);
        $file['value_lists'] = [
            'branches' => ['key' => 'branches', 'name' => 'Branches', 'items' => [['key' => null, 'label' => 'Scranton', 'position' => 1, 'is_active' => true]]],
            'moods' => ['key' => null, 'name' => 'Moods', 'items' => [['key' => null, 'label' => 'Bleak', 'position' => 1, 'is_active' => true]]],
        ];

        $viewModel = new ImportFileViewModel(user: $user, checked: StructureFileReader::read(json_encode($file)));

        $found = collect($viewModel->found())->pluck('value', 'label');

        $this->assertEquals('1 new list, 1 already here', $found['Value lists']);
    }

    #[Test]
    public function it_says_how_many_kinds_of_record_the_import_will_add(): void
    {
        $user = User::factory()->create();

        $file = json_decode($this->file(), true);
        $file['template']['applies_to'] = 'entity';
        $file['template']['entity_type'] = ['slug' => 'branch', 'name_singular' => 'Branch', 'name_plural' => 'Branches'];

        $viewModel = new ImportFileViewModel(user: $user, checked: StructureFileReader::read(json_encode($file)));

        $this->assertEquals('1 will be added', collect($viewModel->found())->pluck('value', 'label')['Kinds of record']);

        EntityType::factory()->create(['account_id' => $user->account_id, 'slug' => 'branch']);

        $viewModel = new ImportFileViewModel(user: $user, checked: StructureFileReader::read(json_encode($file)));

        $this->assertEquals('All of them already here', collect($viewModel->found())->pluck('value', 'label')['Kinds of record']);
    }

    #[Test]
    public function it_carries_the_refusal_and_finds_nothing(): void
    {
        $viewModel = new ImportFileViewModel(user: User::factory()->create(), raw: 'nope', refusal: 'This is not readable as JSON.');

        $this->assertTrue($viewModel->isRefused());
        $this->assertEquals('This is not readable as JSON.', $viewModel->refusal());
        $this->assertEquals([], $viewModel->found());
    }

    #[Test]
    public function it_offers_an_example_that_reads(): void
    {
        $viewModel = new ImportFileViewModel(user: User::factory()->create());

        $read = StructureFileReader::read($viewModel->example(), expecting: 'template');

        $this->assertEquals('Basic contact', $read['template']['name']);
    }

    private function file(): string
    {
        return json_encode([
            'monica' => ['format' => 'template', 'format_version' => StructureFile::FORMAT_VERSION],
            'template' => [
                'name' => 'Basic contact',
                'applies_to' => 'contact',
                'pages' => [[
                    'key' => 'general',
                    'label' => 'General',
                    'position' => 1,
                    'sections' => [[
                        'position' => 1,
                        'section' => [
                            'name' => 'Ways to connect',
                            'fields' => [
                                ['key' => 'personal_email', 'type' => 'email', 'label' => 'Personal email', 'position' => 1],
                                ['key' => 'mobile', 'type' => 'phone', 'label' => 'Mobile', 'position' => 2],
                            ],
                        ],
                    ]],
                ]],
            ],
            'value_lists' => [],
        ]);
    }
}
