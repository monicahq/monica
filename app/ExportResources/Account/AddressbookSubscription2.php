<?php

namespace App\ExportResources\Account;

use App\Models\User\SyncToken;
use App\ExportResources\ExportResource;
use App\ExportResources\User\SyncToken as SyncTokenResource;

class AddressbookSubscription extends ExportResource
{
    protected $columns = [
        'uuid',
        'created_at',
        'updated_at',
    ];

    protected $properties = [
        'name',
        'uri',
        'username',
        'readonly',
        'active',
        'capabilities',
        'frequency',
        'last_syncronized_at',
    ];

    public function data(): ?array
    {
        return  [
            'properties' => [
                'addressbook' => $this->addressBook->uuid,
                'sync_token' => $this->syncToken,
                $this->merge(function () {
                    $localSyncToken = SyncToken::where('account_id', $this->account_id)->find($this->localSyncToken);

                    return [
                        'local_sync_token' => new SyncTokenResource($localSyncToken),
                    ];
                }),
            ],
        ];
    }
}
