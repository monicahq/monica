<?php

namespace Tests\Unit\Services\DavClient\Utils\Model;

use Tests\TestCase;
use App\Models\User\User;
use Mockery\MockInterface;
use Tests\Api\DAV\CardEtag;
use App\Jobs\Dav\UpdateVCard;
use App\Models\Contact\Contact;
use Illuminate\Bus\PendingBatch;
use App\Jobs\Dav\GetMultipleVCard;
use Illuminate\Support\Facades\Bus;
use Sabre\CardDAV\Plugin as CardDAVPlugin;
use Illuminate\Bus\DatabaseBatchRepository;
use App\Models\Account\AddressBookSubscription;
use App\Services\DavClient\Utils\Dav\DavClient;
use App\Services\DavClient\Utils\Model\ContactUpdateDto;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ContactUpdateDtoTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_create_dto_string()
    {
        $dto = new ContactUpdateDto('uri', 'etag', 'card');
        $this->assertEquals('uri', $dto->uri);
        $this->assertEquals('etag', $dto->etag);
        $this->assertEquals('card', $dto->card);
    }

    /** @test */
    public function it_create_dto_resource()
    {
        $resource = fopen(__DIR__.'/stub.vcf', 'r');
        $dto = new ContactUpdateDto('uri', 'etag', $resource);
        $this->assertEquals('uri', $dto->uri);
        $this->assertEquals('etag', $dto->etag);
        $this->assertEquals('card', $dto->card);
    }
}
