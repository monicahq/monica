<?php

declare(strict_types=1);

namespace App\Http\Dav;

use App\Models\User;
use Override;
use Sabre\DAV\Exception\Forbidden;
use Sabre\DAV\PropPatch;
use Sabre\DAVACL\PrincipalBackend\BackendInterface;

class PrincipalBackend implements BackendInterface
{
    public const string PREFIX = 'principals';

    public function __construct(
        private readonly User $user,
    ) {}

    public function uri(): string
    {
        return self::PREFIX.'/'.$this->user->email;
    }

    /** @return array<int, array<string, string>> */
    #[Override]
    public function getPrincipalsByPrefix($prefixPath): array
    {
        if (trim((string) $prefixPath, '/') !== self::PREFIX) {
            return [];
        }

        return [$this->principal()];
    }

    /** @return array<string, string>|null */
    #[Override]
    public function getPrincipalByPath($path): ?array
    {
        if (trim((string) $path, '/') !== $this->uri()) {
            return null;
        }

        return $this->principal();
    }

    #[Override]
    public function updatePrincipal($path, PropPatch $propPatch): void
    {
        $propPatch->handleRemaining(fn (): bool => false);
    }

    /** @return array<int, string> */
    #[Override]
    public function searchPrincipals($prefixPath, array $searchProperties, $test = 'allof'): array
    {
        return [];
    }

    #[Override]
    public function findByUri($uri, $principalPrefix): ?string
    {
        if ($uri === 'mailto:'.$this->user->email) {
            return $this->uri();
        }

        return null;
    }

    /** @return array<int, string> */
    #[Override]
    public function getGroupMemberSet($principal): array
    {
        return [];
    }

    /** @return array<int, string> */
    #[Override]
    public function getGroupMembership($principal): array
    {
        return [];
    }

    #[Override]
    public function setGroupMemberSet($principal, array $members): void
    {
        throw new Forbidden('A principal holds no members.');
    }

    /** @return array<string, string> */
    private function principal(): array
    {
        return [
            'uri' => $this->uri(),
            '{DAV:}displayname' => $this->user->name,
            '{http://sabredav.org/ns}email-address' => $this->user->email,
        ];
    }
}
