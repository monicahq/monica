<?php

declare(strict_types=1);

namespace Tests\Unit\Enums;

use App\Enums\UserActionEnum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UserActionEnumTest extends TestCase
{
    #[Test]
    public function it_describes_every_action(): void
    {
        foreach (UserActionEnum::cases() as $action) {
            $this->assertNotEmpty($action->description());
        }
    }

    #[Test]
    public function it_describes_an_action_with_the_parameters_it_was_logged_with(): void
    {
        $this->assertEquals('Updated the profile of :name', UserActionEnum::UserProfileUpdate->description());
        $this->assertEquals('Changed the photo of :name', UserActionEnum::UserPhotoUpdate->description());
        $this->assertEquals('Removed the photo of :name', UserActionEnum::UserPhotoDeletion->description());
    }

    #[Test]
    public function it_names_the_security_key_a_security_key_action_was_about(): void
    {
        $this->assertEquals('Added the security key called :name', UserActionEnum::SecurityKeyAdded->description());
        $this->assertEquals('Renamed a security key to :name', UserActionEnum::SecurityKeyRenamed->description());
        $this->assertEquals('Removed the security key called :name', UserActionEnum::SecurityKeyRemoved->description());
        $this->assertEquals('Confirmed a sign in with the security key called :name', UserActionEnum::SecurityKeyUsed->description());
    }

    #[Test]
    public function it_names_the_passkey_a_passkey_action_was_about(): void
    {
        $this->assertEquals('Added the passkey called :name', UserActionEnum::PasskeyAdded->description());
        $this->assertEquals('Renamed a passkey to :name', UserActionEnum::PasskeyRenamed->description());
        $this->assertEquals('Removed the passkey called :name', UserActionEnum::PasskeyRemoved->description());
        $this->assertEquals('Signed in with the passkey called :name', UserActionEnum::PasskeyLogin->description());
    }

    #[Test]
    public function it_tells_removing_a_password_from_setting_one(): void
    {
        $this->assertEquals('Removed their password', UserActionEnum::UserPasswordRemoved->description());
        $this->assertEquals('Set a password', UserActionEnum::UserPasswordSet->description());
        $this->assertEquals('Changed their password', UserActionEnum::UserPasswordUpdate->description());
    }
}
