<?php

namespace Tests\Unit\Domains\Contact\ManageAvatar\Services;

use App\Domains\Contact\ManageAvatar\Services\SuggestAvatar;
use App\Models\Contact;
use App\Models\Vault;
use Exception;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SuggestAvatarTest extends TestCase
{
    use DatabaseTransactions;

    private array $suggestions = [
        'https://encrypted-tbn0.gstatic.com/images?q=tbn:A1nnWw7Tai5mXqXIS_NN3jA0&s',
        'https://encrypted-tbn0.gstatic.com/images?q=tbn:A2nnWw7Tai5mXqXIS_NN3jA0&s',
        'https://www.google.com/logos/doodles/2023/seasonal-holidays-2023-6753651837110165.2-s.png',
    ];

    /**
     * @test
     *
     * @group suggest-avatar
     * @group it_suggests_list_based_on_contact_name
     *
     * @throws Exception
     */
    public function it_suggests_list_based_on_contact_name(): void
    {
        $this->fakeHttpResponse();
        $request = $this->requestParams();

        $suggestAvatar = new SuggestAvatar();
        $suggestions = $suggestAvatar->execute($request);

        $this->assertCount(2, $suggestions);
        $this->assertEquals(
            $suggestions,
            array_slice($this->suggestions, 0, 2)
        );

        $contact = Contact::find($request['contact_id']);

        $this->assertSame($suggestAvatar->getSearchTerm(), $contact->name);
    }

    /**
     * @test
     *
     * @group suggest-avatar
     * @group it_suggests_list_based_on_search_term
     *
     * @throws Exception
     */
    public function it_suggests_list_based_on_search_term(): void
    {
        $this->fakeHttpResponse();
        $request = $this->requestParams();
        $request['search_term'] = 'John Doe';

        $suggestAvatar = new SuggestAvatar();
        $suggestions = $suggestAvatar->execute($request);

        $this->assertCount(2, $suggestions);
        $this->assertEquals(
            $suggestions,
            array_slice($this->suggestions, 0, 2)
        );

        $this->assertSame($suggestAvatar->getSearchTerm(), $request['search_term']);
    }

    private function requestParams(): array
    {
        $author = $this->createUser();
        $vault = $this->createVault($author->account);
        $vault = $this->setPermissionInVault($author, Vault::PERMISSION_EDIT, $vault);
        $contact = Contact::factory()->create(['vault_id' => $vault->id]);

        return [
            'account_id' => $author->account->id,
            'vault_id' => $vault->id,
            'author_id' => $author->id,
            'contact_id' => $contact->id,
        ];
    }

    /**
     * @throws Exception
     */
    private function fakeHttpResponse(): void
    {
        Http::fake(function () {
            return Http::response('
            <html lang="en" dir="ltr" itemscope itemtype="http://schema.org/SearchResultsPage">
                <body>
                    <img src="'.$this->suggestions[0].'" alt="">
                    <img src="'.$this->suggestions[1].'" alt="">
                    <img src="'.$this->suggestions[2].'" alt="">
                </body>
            </html>');
        });
    }
}
