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
        $this->assertEquals('Updated the profile of :name', UserActionEnum::EmployeeInformationUpdate->description());
        $this->assertEquals('Changed the photo of :name', UserActionEnum::EmployeePhotoUpdate->description());
        $this->assertEquals('Removed the photo of :name', UserActionEnum::EmployeePhotoDeletion->description());
    }
}
