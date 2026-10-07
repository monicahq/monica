<?php

declare(strict_types=1);

namespace Tests\Unit\Helpers;

use App\Helpers\AlpineAjaxTargets;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AlpineAjaxTargetsTest extends TestCase
{
    #[Test]
    public function it_reads_every_target_an_alpine_request_names(): void
    {
        $request = Request::create('/');
        $request->headers->set('X-Alpine-Request', 'true');
        $request->headers->set('X-Alpine-Target', 'contact-timeline  notifications');

        $this->assertSame(['contact-timeline', 'notifications'], AlpineAjaxTargets::from($request));
    }

    #[Test]
    public function it_reads_nothing_from_an_ordinary_request(): void
    {
        $request = Request::create('/');
        $request->headers->set('X-Alpine-Target', 'contact-timeline');

        $this->assertSame([], AlpineAjaxTargets::from($request));
    }

    #[Test]
    public function it_reads_nothing_when_an_alpine_request_names_no_target(): void
    {
        $request = Request::create('/');
        $request->headers->set('X-Alpine-Request', 'true');

        $this->assertSame([], AlpineAjaxTargets::from($request));
    }
}
