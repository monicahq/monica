<?php

namespace Tests\Unit\Domains\Contact\DavClient\Services\Utils\Model;

use App\Domains\Contact\DavClient\Services\Utils\Model\ContactPushDto;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ContactUpdateDtoTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_create_dto_string()
    {
        $dto = new ContactPushDto('uri', 'etag', 'card', 'id');
        $this->assertEquals('uri', $dto->uri);
        $this->assertEquals('etag', $dto->etag);
        $this->assertEquals('id', $dto->contactId);
        $this->assertEquals('card', $dto->card);
    }

    /** @test */
    public function it_create_dto_resource()
    {
        $resource = fopen(__DIR__.'/stub.vcf', 'r');
        $dto = new ContactPushDto('uri', 'etag', $resource, 'id');
        $this->assertEquals('uri', $dto->uri);
        $this->assertEquals('etag', $dto->etag);
        $this->assertEquals('id', $dto->contactId);
        $this->assertEquals('card', $dto->card);
    }
}
