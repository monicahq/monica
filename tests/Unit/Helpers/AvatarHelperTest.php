<?php

namespace Tests\Unit\Helpers;

use Tests\FeatureTestCase;
use App\Helpers\AvatarHelper;
use App\Models\Contact\Contact;
use Illuminate\Support\Facades\App;

class AvatarHelperTest extends FeatureTestCase
{
    /** @test */
    public function it_gets_the_url_of_the_avatar_of_the_contact(): void
    {
        $jim = factory(Contact::class)->create([
            'avatar_default_url' => 'avatars/img.png',
        ]);
    }
}
