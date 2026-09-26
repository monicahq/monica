<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Docs;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LlmsTextControllerTest extends TestCase
{
    #[Test]
    public function it_serves_the_text_file_for_assistants_to_anybody(): void
    {
        $response = $this->get('/llms.txt');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/plain; charset=UTF-8');
        $this->assertStringStartsWith('# Monica', $response->getContent());
    }
}
