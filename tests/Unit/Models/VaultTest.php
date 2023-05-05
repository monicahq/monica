<?php

namespace Tests\Unit\Models;

use App\Models\Address;
use App\Models\Company;
use App\Models\Contact;
use App\Models\ContactImportantDateType;
use App\Models\File;
use App\Models\Group;
use App\Models\Journal;
use App\Models\Label;
use App\Models\LifeEventCategory;
use App\Models\LifeMetric;
use App\Models\MoodTrackingParameter;
use App\Models\Tag;
use App\Models\Template;
use App\Models\TimelineEvent;
use App\Models\User;
use App\Models\Vault;
use App\Models\VaultQuickFactsTemplate;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class VaultTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_belongs_to_an_account()
    {
        $vault = Vault::factory()->create();
        $this->assertTrue($vault->account()->exists());
    }

    /** @test */
    public function it_belongs_to_a_template()
    {
        $template = Template::factory()->create();

        $vault = Vault::factory()->create([
            'default_template_id' => $template->id,
        ]);
        $this->assertTrue($vault->template()->exists());
    }

    /** @test */
    public function it_has_many_users(): void
    {
        $dwight = User::factory()->create();
        $vault = Vault::factory()->create([
            'account_id' => $dwight->account_id,
        ]);

        $vault->users()->sync([$dwight->id => [
            'permission' => Vault::PERMISSION_MANAGE,
            'contact_id' => Contact::factory()->create()->id,
        ]]);

        $this->assertTrue($vault->users()->exists());
    }

    /** @test */
    public function it_has_many_contacts(): void
    {
        $vault = Vault::factory()->create();
        $contact = Contact::factory()->create([
            'vault_id' => $vault->id,
        ]);

        $this->assertTrue($vault->contacts()->exists());
    }

    /** @test */
    public function it_has_many_labels(): void
    {
        $vault = Vault::factory()->create();
        $label = Label::factory()->create([
            'vault_id' => $vault->id,
        ]);

        $this->assertTrue($vault->labels()->exists());
    }

    /** @test */
    public function it_has_many_important_date_types(): void
    {
        $vault = Vault::factory()->create();
        $type = ContactImportantDateType::factory()->create([
            'vault_id' => $vault->id,
        ]);

        $this->assertTrue($vault->contactImportantDateTypes()->exists());
    }

    /** @test */
    public function it_has_many_companies(): void
    {
        $vault = Vault::factory()->create();
        Company::factory()->create([
            'vault_id' => $vault->id,
        ]);

        $this->assertTrue($vault->companies()->exists());
    }

    /** @test */
    public function it_has_many_groups(): void
    {
        $vault = Vault::factory()->create();
        Group::factory()->create([
            'vault_id' => $vault->id,
        ]);

        $this->assertTrue($vault->groups()->exists());
    }

    /** @test */
    public function it_has_many_journals(): void
    {
        $vault = Vault::factory()->create();
        Journal::factory()->create([
            'vault_id' => $vault->id,
        ]);

        $this->assertTrue($vault->journals()->exists());
    }

    /** @test */
    public function it_has_many_tags(): void
    {
        $vault = Vault::factory()->create();
        Tag::factory()->create([
            'vault_id' => $vault->id,
        ]);

        $this->assertTrue($vault->tags()->exists());
    }

    /** @test */
    public function it_has_many_files(): void
    {
        $vault = Vault::factory()->create();
        File::factory()->count(2)->create([
            'vault_id' => $vault->id,
        ]);

        $this->assertTrue($vault->files()->exists());
    }

    /** @test */
    public function it_has_many_mood_tracking_parameters(): void
    {
        $vault = Vault::factory()->create();
        MoodTrackingParameter::factory()->create([
            'vault_id' => $vault->id,
        ]);

        $this->assertTrue($vault->moodTrackingParameters()->exists());
    }

    /** @test */
    public function it_has_many_life_event_categories(): void
    {
        $vault = Vault::factory()->create();
        LifeEventCategory::factory()->count(2)->create([
            'vault_id' => $vault->id,
        ]);

        $this->assertTrue($vault->lifeEventCategories()->exists());
    }

    /** @test */
    public function it_has_many_timeline_events(): void
    {
        $vault = Vault::factory()->create();
        TimelineEvent::factory()->count(2)->create([
            'vault_id' => $vault->id,
        ]);

        $this->assertTrue($vault->timelineEvents()->exists());
    }

    /** @test */
    public function it_has_many_addresses(): void
    {
        $vault = Vault::factory()->create();
        Address::factory()->count(2)->create([
            'vault_id' => $vault->id,
        ]);

        $this->assertTrue($vault->addresses()->exists());
    }

    /** @test */
    public function it_has_many_quick_fact_template_entries(): void
    {
        $vault = Vault::factory()->create();
        VaultQuickFactsTemplate::factory()->count(2)->create([
            'vault_id' => $vault->id,
        ]);

        $this->assertTrue($vault->quickFactsTemplateEntries()->exists());
    }

    /** @test */
    public function it_has_many_life_metrics_entries(): void
    {
        $vault = Vault::factory()->create();
        LifeMetric::factory()->count(2)->create([
            'vault_id' => $vault->id,
        ]);

        $this->assertTrue($vault->lifeMetrics()->exists());
    }
}
