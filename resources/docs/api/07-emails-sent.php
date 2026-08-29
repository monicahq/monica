<?php

declare(strict_types=1);

use App\ViewModels\Docs\ApiDocsViewModel;

$base = ApiDocsViewModel::baseUrl();

$delivered = [
    'type' => 'email_sent',
    'id' => '42',
    'attributes' => [
        'email_type' => 'new_login',
        'subject' => 'A sign-in from a new place',
        'email_address' => 'michael.scott@dundermifflin.com',
        'delivery_status' => 'delivered',
        'sent_at' => 1756339200,
        'delivered_at' => 1756339260,
        'bounced_at' => null,
        'created_at' => 1756339200,
    ],
    'links' => [
        'self' => $base.'/emails-sent/42',
    ],
];

$bounced = [
    'type' => 'email_sent',
    'id' => '41',
    'attributes' => [
        'email_type' => 'magic_link_created',
        'subject' => 'Your sign-in link',
        'email_address' => 'dwight.schrute@dundermifflin.com',
        'delivery_status' => 'bounced',
        'sent_at' => 1756252800,
        'delivered_at' => null,
        'bounced_at' => 1756252860,
        'created_at' => 1756252800,
    ],
    'links' => [
        'self' => $base.'/emails-sent/41',
    ],
];

$withBody = $delivered;
$withBody['attributes'] = array_merge(
    array_slice($delivered['attributes'], 0, 3, true),
    ['body' => 'Somebody just signed in to your account from a device we have not seen before.'],
    array_slice($delivered['attributes'], 3, null, true),
);

return [
    'name' => 'Emails sent',
    'sections' => [
        [
            'id' => 'emails-sent-index',
            'title' => 'List emails sent',
            'label' => 'List emails sent',
            'method' => 'GET',
            'path' => '/emails-sent',
            'description' => 'List the emails we sent on behalf of your account, most recently sent first.',
            'body' => [
                'This is the same record the emails screen in your settings shows, which is where to look when somebody says a sign-in link never arrived. Each email carries the three moments that can happen to it, when it left, when it arrived and when it came back, and one word that reads them for you.',
                'The copy of each email is deliberately left out of this list. A page holds twenty five emails by default, and carrying the full text of each would turn a list somebody is scanning into a download. Ask for one email to read what it said.',
                'The record belongs to the account rather than to you personally, so an email we sent to an address that has no account of its own is still listed here.',
                'Nothing here can be written, changed or removed. The record is written as the mail leaves and updated only when the provider tells us what became of it.',
            ],
            'rateLimit' => 'Sixty requests per minute, per token.',
            'queryParams' => [
                [
                    'name' => 'page',
                    'type' => 'integer',
                    'required' => false,
                    'description' => 'Which page to answer with.',
                    'default' => '1',
                ],
                [
                    'name' => 'per_page',
                    'type' => 'integer',
                    'required' => false,
                    'description' => 'How many emails one page holds, between 1 and 100. A number outside the range is brought back into it rather than refused.',
                    'default' => '25',
                ],
            ],
            'returns' => 'A page of emails we sent, and how many there are in all. An account that has never had an email leave gets an empty list rather than an error.',
            'response' => [
                'data' => [$delivered, $bounced],
                'meta' => [
                    'page' => 1,
                    'per_page' => 25,
                    'total' => 2,
                ],
            ],
        ],
        [
            'id' => 'emails-sent-show',
            'title' => 'Get an email sent',
            'label' => 'Get an email sent',
            'method' => 'GET',
            'path' => '/emails-sent/{emailSent}',
            'examplePath' => '/emails-sent/42',
            'description' => 'Retrieve one email we sent, including the copy that left.',
            'body' => [
                'Everything a row in the list carries, and one field more: `body`, the text of the email as it was sent. That is what tells you what we actually said, rather than only that we said something.',
                'The `delivery_status` is one of three words. `delivered` means it arrived, `bounced` means it came back, and `sent` means it left and nothing has come back yet. When a provider reports a delivery and a bounce for the same email, which it can do out of order, the email reads as `bounced`.',
            ],
            'rateLimit' => 'Sixty requests per minute, per token.',
            'pathParams' => [
                [
                    'name' => 'emailSent',
                    'type' => 'integer',
                    'required' => true,
                    'description' => 'The identifier of the email.',
                ],
            ],
            'returns' => 'One email we sent. An email belonging to another account answers 404, and so does an identifier that matches nothing, deliberately: the two cannot be told apart.',
            'response' => ['data' => $withBody],
        ],
    ],
];
