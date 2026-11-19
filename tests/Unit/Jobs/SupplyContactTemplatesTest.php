<?php

declare(strict_types=1);

namespace Tests\Unit\Jobs;

use App\Actions\CreateDefaultValueLists;
use App\Enums\DefaultTemplateEnum;
use App\Enums\ValueListEnum;
use App\Jobs\CreateDefaultTemplate;
use App\Jobs\SupplyContactTemplates;
use App\Models\Section;
use App\Models\Template;
use App\Models\User;
use App\Models\ValueList;
use App\Models\Vault;
use App\Models\VaultDefaultTemplate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SupplyContactTemplatesTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_gives_an_existing_account_the_four_templates_and_leaves_basic_contact_the_default(): void
    {
        Queue::fake();

        $user = $this->accountFromBeforeTheFourTemplates();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        new CreateDefaultTemplate(user: $user)->handle();
        $default = VaultDefaultTemplate::query()->where('vault_id', $vault->id)->sole();

        new SupplyContactTemplates(user: $user)->handle();

        $this->assertEqualsCanonicalizing(
            ['basic-contact', 'essentials', 'friends-and-family', 'professional-network', 'full-portrait'],
            Template::query()->where('account_id', $user->account_id)->pluck('slug')->all(),
        );
        $this->assertSame(1, VaultDefaultTemplate::query()->where('vault_id', $vault->id)->count());
        $this->assertDatabaseHas('vault_default_templates', [
            'id' => $default->id,
            'structure_version_id' => $default->structure_version_id,
        ]);
    }

    #[Test]
    public function it_gives_an_existing_account_the_lists_the_templates_read_from(): void
    {
        Queue::fake();

        $user = $this->accountFromBeforeTheFourTemplates();

        new SupplyContactTemplates(user: $user)->handle();

        foreach ([ValueListEnum::Hobbies, ValueListEnum::CommunicationChannels, ValueListEnum::Industries, ValueListEnum::AreasOfExpertise] as $list) {
            $this->assertDatabaseHas('value_lists', ['account_id' => $user->account_id, 'key' => $list->value]);
        }
    }

    #[Test]
    public function it_adds_nothing_the_second_time(): void
    {
        Queue::fake();

        $user = $this->accountFromBeforeTheFourTemplates();

        new SupplyContactTemplates(user: $user)->handle();

        $templates = Template::query()->where('account_id', $user->account_id)->count();
        $sections = Section::query()->where('account_id', $user->account_id)->count();
        $lists = ValueList::query()->where('account_id', $user->account_id)->count();

        new SupplyContactTemplates(user: $user)->handle();

        $this->assertSame($templates, Template::query()->where('account_id', $user->account_id)->count());
        $this->assertSame($sections, Section::query()->where('account_id', $user->account_id)->count());
        $this->assertSame($lists, ValueList::query()->where('account_id', $user->account_id)->count());
    }

    #[Test]
    public function it_does_not_bring_back_a_supplied_template_somebody_deleted(): void
    {
        Queue::fake();

        $user = $this->accountFromBeforeTheFourTemplates();

        new SupplyContactTemplates(user: $user)->handle();

        Template::query()->where('slug', DefaultTemplateEnum::Essentials->value)->sole()->delete();

        new SupplyContactTemplates(user: $user)->handle();

        $this->assertSame(0, Template::query()->where('slug', DefaultTemplateEnum::Essentials->value)->count());
    }

    #[Test]
    public function it_skips_only_the_template_whose_slug_the_account_uses_for_its_own(): void
    {
        Queue::fake();

        $user = $this->accountFromBeforeTheFourTemplates();
        Section::factory()->create(['account_id' => $user->account_id, 'slug' => 'essentials-notes', 'is_system' => false]);

        new SupplyContactTemplates(user: $user)->handle();

        $slugs = Template::query()->where('account_id', $user->account_id)->pluck('slug')->all();

        $this->assertNotContains(DefaultTemplateEnum::Essentials->value, $slugs);
        $this->assertContains(DefaultTemplateEnum::FullPortrait->value, $slugs);
    }

    private function accountFromBeforeTheFourTemplates(): User
    {
        $user = User::factory()->create();

        new CreateDefaultValueLists(user: $user, lists: [
            ValueListEnum::Genders,
            ValueListEnum::ContactCategories,
            ValueListEnum::Languages,
            ValueListEnum::EmailLabels,
            ValueListEnum::PhoneLabels,
            ValueListEnum::AddressLabels,
        ])->execute();

        return $user;
    }
}
