<?php

namespace Tests\Unit\Services\Contact\Avatar;

use Tests\TestCase;
use Illuminate\Validation\ValidationException;
use App\Services\Contact\Avatar\GetAdorableAvatarURL;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class GetAdorableAvatarTest extends TestCase
{
    use DatabaseTransactions;

    public function test_it_returns_an_url()
    {
        $request = [
            'uuid' => 'matt@wordpress.com',
            'size' => 400,
        ];

        $url = app(GetAdorableAvatarURL::class)->execute($request);

        $this->assertEquals(
            'https://api.adorable.io/avatars/400/matt@wordpress.com.png',
            $url
        );
    }

    public function test_it_returns_an_url_with_a_default_avatar_size()
    {
        $request = [
            'uuid' => 'matt@wordpress.com',
        ];

        $url = app(GetAdorableAvatarURL::class)->execute($request);

        // should return an avatar of 200 px wide
        $this->assertEquals(
            'https://api.adorable.io/avatars/200/matt@wordpress.com.png',
            $url
        );
    }

    public function test_it_fails_if_wrong_parameters_are_given()
    {
        $request = [
            'size' => 200,
        ];

        $this->expectException(ValidationException::class);
        app(GetAdorableAvatarURL::class)->execute($request);
    }
}
