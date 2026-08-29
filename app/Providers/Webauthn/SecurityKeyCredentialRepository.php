<?php

declare(strict_types=1);

namespace App\Providers\Webauthn;

use Illuminate\Support\Collection;
use LaravelWebauthn\Facades\Webauthn;
use LaravelWebauthn\Services\Webauthn\CredentialRepository;
use Override;
use Webauthn\CredentialRecord;

final class SecurityKeyCredentialRepository extends CredentialRepository
{
    /**
     * List of PublicKeyCredentialSource associated to the user.
     *
     * @return Collection<array-key,CredentialRecord>
     */
    #[Override]
    protected function getAllRegisteredKeys(int|string $userId): Collection
    {
        return (Webauthn::model())::where([
            'user_id' => $userId,
            'kind' => 'security',
        ])
            ->get()
            ->map
            ->publicKeyCredentialSource;
    }
}
