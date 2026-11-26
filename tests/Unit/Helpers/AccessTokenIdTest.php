<?php

declare(strict_types=1);

namespace Tests\Unit\Helpers;

use App\Helpers\AccessTokenId;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AccessTokenIdTest extends TestCase
{
    #[Test]
    public function it_reads_the_id_of_an_access_token(): void
    {
        $this->assertSame('pretzel-day', AccessTokenId::of($this->accessTokenWithId('pretzel-day')));
    }

    #[Test]
    public function it_reads_nothing_from_what_is_not_a_token(): void
    {
        $this->assertNull(AccessTokenId::of(''));
        $this->assertNull(AccessTokenId::of('jello-stapler'));
        $this->assertNull(AccessTokenId::of('a.b.c'));
    }

    #[Test]
    public function it_reads_nothing_from_a_token_without_an_id(): void
    {
        $payload = rtrim(strtr(base64_encode((string) json_encode(['sub' => '1'])), '+/', '-_'), '=');

        $this->assertNull(AccessTokenId::of("header.{$payload}.signature"));
    }
}
