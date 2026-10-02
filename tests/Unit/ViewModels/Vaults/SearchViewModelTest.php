<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Vaults;

use App\Actions\SearchRecords;
use App\Enums\FieldTypeEnum;
use App\Models\Contact;
use App\Models\Field;
use App\Models\FieldValue;
use App\Models\LifeEvent;
use App\Models\LifeEventParticipant;
use App\Models\User;
use App\Models\Vault;
use App\ViewModels\Vaults\SearchViewModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SearchViewModelTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_waits_for_something_to_search(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        $viewModel = new SearchViewModel(user: $user, vault: $vault, query: '  ');

        $this->assertSame(SearchViewModel::IDLE, $viewModel->status());
        $this->assertSame([], $viewModel->groups());
    }

    #[Test]
    public function it_shows_each_result_with_the_words_marked_where_they_were_found(): void
    {
        $this->useSearchEngine();
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $dwight = Contact::factory()->create(['account_id' => $user->account_id, 'vault_id' => $vault->id, 'first_name' => 'Dwight', 'last_name' => 'Schrute', 'is_archived' => true]);
        $field = Field::factory()->create(['account_id' => $user->account_id, 'type' => FieldTypeEnum::Text, 'label' => 'Employer']);
        FieldValue::factory()->create(['valuable_type' => Contact::class, 'valuable_id' => $dwight->id, 'field_id' => $field->id, 'value_string' => 'Schrute Farms & Bed']);
        $this->waitForSearchEngine();

        $viewModel = new SearchViewModel(user: $user, vault: $vault, query: 'farms');

        $this->assertSame(SearchRecords::SEARCHED, $viewModel->status());
        $this->assertSame(1, $viewModel->resultCount());
        $this->assertSame('1 result for “farms” in this vault', $viewModel->countLine());

        $row = $viewModel->groups()[0]['rows'][0];

        $this->assertSame('Dwight Schrute', $row['name']);
        $this->assertSame('Dwight Schrute', (string) $row['markedName']);
        $this->assertFalse($row['isLifeEvent']);
        $this->assertSame(route('contacts.show', ['vault' => $vault, 'contact' => $dwight]), $row['url']);
        $this->assertTrue($row['isArchived']);
        $this->assertNull($row['vault']);
        $this->assertSame('Employer', $row['matchedLabel']);
        $this->assertSame('Schrute <mark>Farms</mark> &amp; Bed', (string) $row['matchedExcerpt']);
    }

    #[Test]
    public function it_says_nothing_was_found(): void
    {
        $this->useSearchEngine();
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        $viewModel = new SearchViewModel(user: $user, vault: $vault, query: 'kevin');

        $this->assertSame(0, $viewModel->resultCount());
        $this->assertSame('Nothing matches “kevin”', $viewModel->nothingFoundTitle());
        $this->assertSame('Try another spelling, or search every vault.', $viewModel->nothingFoundHint());
    }

    #[Test]
    public function it_marks_the_words_found_in_a_name_and_says_which_vaults_were_searched(): void
    {
        $this->useSearchEngine();
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        Contact::factory()->create(['account_id' => $user->account_id, 'vault_id' => $vault->id, 'first_name' => 'Kevin', 'last_name' => 'Malone']);
        $this->waitForSearchEngine();

        $viewModel = new SearchViewModel(user: $user, vault: $vault, query: 'kevin', acrossAccount: true);
        $row = $viewModel->groups()[0]['rows'][0];

        $this->assertSame('<mark>Kevin</mark> Malone', (string) $row['markedName']);
        $this->assertNull($row['matchedLabel']);
        $this->assertNull($row['avatarUrl']);
        $this->assertSame('1 result for “kevin” in every vault', $viewModel->countLine());
        $this->assertSame('Try another spelling or a shorter word.', $viewModel->nothingFoundHint());
    }

    #[Test]
    public function it_shows_a_life_event_with_its_date_and_who_was_there(): void
    {
        $this->useSearchEngine();
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $jan = Contact::factory()->create(['account_id' => $user->account_id, 'vault_id' => $vault->id, 'first_name' => 'Jan', 'last_name' => 'Levinson']);
        $dinner = LifeEvent::factory()->create(['vault_id' => $vault->id, 'title' => 'Dinner party', 'happened_on' => '2026-03-14']);
        LifeEventParticipant::factory()->create(['account_id' => $user->account_id, 'life_event_id' => $dinner->id, 'participant_type' => Contact::class, 'participant_id' => $jan->id]);
        $this->waitForSearchEngine();

        $group = collect(new SearchViewModel(user: $user, vault: $vault, query: 'levinson')->groups())->firstWhere('key', 'life-events');
        $row = $group['rows'][0];

        $this->assertSame('Dinner party', $row['name']);
        $this->assertTrue($row['isLifeEvent']);
        $this->assertSame('14/03/2026', $row['date']);
        $this->assertSame('With Jan Levinson', $row['matchedLabel']);
        $this->assertSame(route('contacts.show', ['vault' => $vault, 'contact' => $jan]), $row['url']);
    }
}
