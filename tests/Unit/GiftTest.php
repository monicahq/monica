<?php

namespace Tests\Unit;

use App\Gift;
use Carbon\Carbon;
use Tests\TestCase;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class GiftTest extends TestCase
{
    use DatabaseTransactions;

    public function testGetNameReturnsNullIfUndefined()
    {
        $gift = new Gift;

        $this->assertNull($gift->getName());
    }

    public function testGetNameReturnsName()
    {
        $gift = new Gift;
        $gift->name = encrypt('This is a test');

        $this->assertEquals(
            'This is a test',
            $gift->getName()
        );
    }

    public function testGetUrlReturnsNullIfUndefined()
    {
        $gift = new Gift;

        $this->assertNull($gift->getUrl());
    }

    public function testGetURLReturnsURL()
    {
        $gift = new Gift;
        $gift->url = encrypt('https://test.com');

        $this->assertEquals(
            'https://test.com',
            $gift->getUrl()
        );
    }

    public function testGetCommentReturnsNullIfUndefined()
    {
        $gift = new Gift;

        $this->assertNull($gift->getComment());
    }

    public function testGetCommentReturnsComment()
    {
        $gift = new Gift;
        $gift->comment = encrypt('this is a test');

        $this->assertEquals(
            'this is a test',
            $gift->getComment()
        );
    }

    public function testGetValueReturnsNullIfUndefined()
    {
        $gift = new Gift;

        $this->assertNull($gift->getValue());
    }

    public function testGetValueReturnsValue()
    {
        $gift = new Gift;
        $gift->value_in_dollars = '220.00';

        $this->assertEquals(
            '220.00',
            $gift->getValue()
        );
    }

    public function testGetCreatedAtReturnsCarbonObject()
    {
        $gift = factory(\App\Gift::class)->make();

        $this->assertInstanceOf(Carbon::class, $gift->getCreatedAt());
    }
}
