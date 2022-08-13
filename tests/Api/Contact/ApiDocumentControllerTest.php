<?php

namespace Tests\Api\Contact;

use Tests\ApiTestCase;
use App\Models\User\User;
use App\Models\Contact\Contact;
use App\Models\Contact\Document;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ApiDocumentControllerTest extends ApiTestCase
{
    use DatabaseTransactions;

    protected $jsonDocuments = [
        'id',
        'object',
        'original_filename',
        'new_filename',
        'filesize',
        'type',
        'mime_type',
        'number_of_downloads',
        'link',
        'account' => [
            'id',
        ],
        'contact' => [
            'id',
        ],
        'created_at',
        'updated_at',
    ];

    private function createDocument(User $user): Document
    {
        $contact = factory(Contact::class)->create([
            'account_id' => $user->account_id,
        ]);

        $document = factory(Document::class)->create([
            'account_id' => $user->account_id,
            'contact_id' => $contact->id,
        ]);

        return $document;
    }

    /** @test */
    public function it_gets_a_list_of_documents()
    {
        $user = $this->signin();

        for ($i = 0; $i < 10; $i++) {
            $this->createDocument($user);
        }

        $response = $this->json('GET', '/api/documents');

        $response->assertStatus(200);

        $this->assertCount(
            10,
            $response->decodeResponseJson()['data']
        );

        $response->assertJsonFragment([
            'total' => 10,
            'current_page' => 1,
        ]);

        $response->assertJsonStructure([
            'data' => [
                '*' => $this->jsonDocuments,
            ],
        ]);
    }

    /** @test */
    public function it_applies_the_limit_parameter_in_search()
    {
        $user = $this->signin();

        for ($i = 0; $i < 10; $i++) {
            $this->createDocument($user);
        }

        $response = $this->json('GET', '/api/documents?limit=1');

        $response->assertJsonFragment([
            'total' => 10,
            'current_page' => 1,
            'per_page' => 1,
            'last_page' => 10,
        ]);

        $response = $this->json('GET', '/api/documents?limit=2');

        $response->assertJsonFragment([
            'total' => 10,
            'current_page' => 1,
            'per_page' => 2,
            'last_page' => 5,
        ]);
    }

    /** @test */
    public function it_gets_a_document()
    {
        $user = $this->signin();

        $document = $this->createDocument($user);

        $response = $this->json('GET', '/api/documents/'.$document->id);

        $response->assertStatus(200);

        $response->assertJsonStructure([
            '*' => $this->jsonDocuments,
        ]);
    }

    /** @test */
    public function document_show_gets_an_error_if_document_is_not_linked_to_account()
    {
        $user = $this->signin();

        $contact = factory(Contact::class)->create();
        $document = factory(Document::class)->create([
            'account_id' => $contact->account_id,
            'contact_id' => $contact->id,
        ]);

        $response = $this->json('GET', '/api/documents/'.$document->id);

        $this->expectNotFound($response);
    }

    /** @test */
    public function it_gets_a_document_for_a_specific_contact()
    {
        $user = $this->signin();

        $document = $this->createDocument($user);

        $response = $this->json('GET', '/api/contacts/'.$document['contact_id'].'/documents');

        $response->assertStatus(200);

        $response->assertJsonStructure([
            'data' => [
                '*' => $this->jsonDocuments,
            ],
        ]);

        $response->assertJsonFragment([
            'total' => 1,
            'current_page' => 1,
            'per_page' => 15,
            'last_page' => 1,
        ]);
    }

    /** @test */
    public function it_store_a_document_for_a_specific_contact()
    {
        Storage::fake();

        $user = $this->signin();
        $contact = factory(Contact::class)->create([
            'account_id' => $user->account_id,
        ]);

        $response = $this->json('POST', '/api/documents', [
            'contact_id' => $contact->id,
            'document' => UploadedFile::fake()->image('test.pdf'),
        ]);

        $response->assertStatus(201);

        $response->assertJsonStructure([
            'data' => $this->jsonDocuments,
        ]);

        $this->assertDatabaseHas('documents', [
            'account_id' => $user->account_id,
            'contact_id' => $contact->id,
            'original_filename' => 'test.pdf',
        ]);

        Storage::disk('public')->assertExists($response->json('data.new_filename'));
    }

    /** @test */
    public function document_store_gets_an_error_if_fields_are_missing()
    {
        $user = $this->signin();

        $response = $this->json('POST', '/api/documents', [
        ]);

        $this->expectDataError($response, [
            'The contact id field is required.',
        ]);
    }

    /** @test */
    public function document_store_gets_an_error_if_contact_is_not_linked_to_user()
    {
        $user = $this->signin();

        $contact = factory(Contact::class)->create();

        $response = $this->json('POST', '/api/documents', [
            'contact_id' => $contact->id,
            'document' => UploadedFile::fake()->image('test.pdf'),
        ]);

        $this->expectNotFound($response);
    }

    /** @test */
    public function it_destroy_a_document()
    {
        $user = $this->signin();

        $document = $this->createDocument($user);

        $response = $this->json('DELETE', '/api/documents/'.$document->id);

        $response->assertStatus(200);

        $response->assertJsonFragment([
            'deleted' => true,
            'id' => $document->id,
        ]);
    }

    /** @test */
    public function document_destroy_gets_an_error_if_document_is_not_linked_to_user()
    {
        $user = $this->signin();

        $contact = factory(Contact::class)->create();
        $document = factory(Document::class)->create([
            'account_id' => $contact->account_id,
            'contact_id' => $contact->id,
        ]);

        $response = $this->json('DELETE', '/api/documents/'.$document->id);

        $this->expectNotFound($response);
    }
}
