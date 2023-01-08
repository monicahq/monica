<?php

namespace Tests\Unit\Domains\Settings\ManageGenders\Dav;

use App\Domains\Settings\ManageGenders\Dav\ExportGender;
use App\Models\Contact;
use App\Models\Gender;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Sabre\VObject\Component\VCard;
use Sabre\VObject\PHPUnitAssertions;
use Tests\TestCase;
use Tests\Unit\Domains\Contact\DAV\CardEtag;

class ExportGenderTest extends TestCase
{
    use DatabaseTransactions,
        PHPUnitAssertions,
        CardEtag;

    /** @var int */
    const defaultPropsCount = 3;

    /**
     * @group dav
     *
     * @test
     */
    public function it_adds_gender_to_vcard()
    {
        $user = $this->createUser();
        $vault = $this->createVaultUser($user);
        $contact = Contact::factory()->random()->create(['vault_id' => $vault->id]);

        $vCard = new VCard();
        (new ExportGender)->export($contact, $vCard);

        $this->assertCount(
            self::defaultPropsCount + 1,
            $vCard->children()
        );
        $this->assertStringContainsString("GENDER:{$contact->gender->type}", $vCard->serialize());
    }

    /**
     * @group dav
     *
     * @test
     */
    public function it_adds_gender_female()
    {
        $user = $this->createUser();
        $vault = $this->createVaultUser($user);
        $gender = Gender::factory()->create([
            'account_id' => $user->account_id,
            'type' => 'F',
            'name' => 'Female',
        ]);
        $contact = Contact::factory()->random()->create([
            'vault_id' => $vault->id,
            'gender_id' => $gender->id,
        ]);

        $vCard = new VCard();
        (new ExportGender)->export($contact, $vCard);

        $this->assertCount(
            self::defaultPropsCount + 1,
            $vCard->children()
        );
        $this->assertStringContainsString('GENDER:F', $vCard->serialize());
    }

    /**
     * @group dav
     *
     * @test
     */
    public function it_adds_gender_unknown()
    {
        $user = $this->createUser();
        $vault = $this->createVaultUser($user);
        $gender = Gender::factory()->create([
            'account_id' => $user->account_id,
            'type' => 'U',
        ]);
        $contact = Contact::factory()->random()->create([
            'vault_id' => $vault->id,
            'gender_id' => $gender->id,
        ]);

        $vCard = new VCard();
        (new ExportGender)->export($contact, $vCard);

        $this->assertCount(
            self::defaultPropsCount + 1,
            $vCard->children()
        );
        $this->assertStringContainsString('GENDER:U', $vCard->serialize());
    }

    /**
     * @group dav
     *
     * @test
     */
    public function it_adds_gender_type_null()
    {
        $user = $this->createUser();
        $vault = $this->createVaultUser($user);
        $gender = Gender::factory()->create([
            'account_id' => $user->account_id,
            'type' => null,
            'name' => 'Something',
        ]);
        $contact = Contact::factory()->random()->create([
            'vault_id' => $vault->id,
            'gender_id' => $gender->id,
        ]);

        $vCard = new VCard();
        (new ExportGender)->export($contact, $vCard);

        $this->assertCount(
            self::defaultPropsCount + 1,
            $vCard->children()
        );
        $this->assertStringContainsString('GENDER:O', $vCard->serialize());
    }

    /**
     * @group dav
     *
     * @test
     */
    public function it_adds_gender_type_null_male()
    {
        $user = $this->createUser();
        $vault = $this->createVaultUser($user);
        $gender = Gender::factory()->create([
            'account_id' => $user->account_id,
            'type' => null,
            'name' => 'Male',
        ]);
        $contact = Contact::factory()->random()->create([
            'vault_id' => $vault->id,
            'gender_id' => $gender->id,
        ]);

        $vCard = new VCard();
        (new ExportGender)->export($contact, $vCard);

        $this->assertCount(
            self::defaultPropsCount + 1,
            $vCard->children()
        );
        $this->assertStringContainsString('GENDER:M', $vCard->serialize());
    }

    /**
     * @group dav
     *
     * @test
     */
    public function it_adds_gender_type_null_female()
    {
        $user = $this->createUser();
        $vault = $this->createVaultUser($user);
        $gender = Gender::factory()->create([
            'account_id' => $user->account_id,
            'type' => null,
            'name' => 'Female',
        ]);
        $contact = Contact::factory()->random()->create([
            'vault_id' => $vault->id,
            'gender_id' => $gender->id,
        ]);

        $vCard = new VCard();
        (new ExportGender)->export($contact, $vCard);

        $this->assertCount(
            self::defaultPropsCount + 1,
            $vCard->children()
        );
        $this->assertStringContainsString('GENDER:F', $vCard->serialize());
    }
}
