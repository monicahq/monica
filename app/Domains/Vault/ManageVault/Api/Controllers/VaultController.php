<?php

namespace App\Domains\Vault\ManageVault\Api\Controllers;

use App\Domains\Vault\ManageVault\Services\CreateVault;
use App\Domains\Vault\ManageVault\Services\DestroyVault;
use App\Domains\Vault\ManageVault\Services\UpdateVault;
use App\Http\Controllers\ApiController;
use App\Http\Resources\VaultResource;
use App\Models\Vault;
use Illuminate\Http\Request;
use Knuckles\Scribe\Attributes\{BodyParam,QueryParam,Response,ResponseFromApiResource};

/**
 * @group Vault management
 *
 * @subgroup Vaults
 */
class VaultController extends ApiController
{
    public function __construct()
    {
        $this->middleware('abilities:read')->only(['index', 'show']);
        $this->middleware('abilities:write')->only(['store', 'update', 'delete']);

        parent::__construct();
    }

    /**
     * List all vaults.
     *
     * Get all the vaults in the account.
     */
    #[QueryParam('limit', 'int', description: 'A limit on the number of objects to be returned. Limit can range between 1 and 100, and the default is 10.', required: false, example: 10)]
    #[ResponseFromApiResource(VaultResource::class, Vault::class, collection: true)]
    public function index(Request $request)
    {
        $vaults = $request->user()->account->vaults()
            ->paginate($this->getLimitPerPage());

        return VaultResource::collection($vaults);
    }

    /**
     * Create a vault.
     *
     * Creates a vault object.
     */
    #[BodyParam('name', description: 'The name of the vault. Max 255 characters.')]
    #[BodyParam('description', description: 'The description of the vault. Max 65535 characters.', required: false)]
    #[ResponseFromApiResource(VaultResource::class, Vault::class, status: 201)]
    public function store(Request $request)
    {
        $data = [
            'account_id' => $request->user()->account_id,
            'author_id' => $request->user()->id,
            'type' => Vault::TYPE_PERSONAL,
            'name' => $request->input('name'),
            'description' => $request->input('description'),
        ];

        $vault = (new CreateVault)->execute($data);

        return new VaultResource($vault);
    }

    /**
     * Retrieve a vault.
     *
     * Get a specific vault object.
     */
    #[ResponseFromApiResource(VaultResource::class, Vault::class)]
    public function show(Request $request, string $vaultId)
    {
        $vault = $request->user()->account->vaults()
            ->findOrFail($vaultId);

        return new VaultResource($vault);
    }

    /**
     * Update a vault.
     *
     * Updates a vault object.
     *
     * If the call succeeds, the response is the same as the one for the
     * Retrieve a vault endpoint.
     */
    #[BodyParam('name', description: 'The name of the vault. Max 255 characters.')]
    #[BodyParam('description', description: 'The description of the vault. Max 65535 characters.', required: false)]
    #[ResponseFromApiResource(VaultResource::class, Vault::class)]
    public function update(Request $request, string $vaultId)
    {
        $data = [
            'account_id' => $request->user()->account_id,
            'author_id' => $request->user()->id,
            'vault_id' => $vaultId,
            'name' => $request->input('name'),
            'description' => $request->input('description'),
        ];

        $vault = (new UpdateVault)->execute($data);

        return new VaultResource($vault);
    }

    /**
     * Delete a vault.
     *
     * Destroys a vault object.
     * Warning: everything in the vault will be immediately deleted.
     */
    #[Response(['deleted' => true, 'id' => 1])]
    public function destroy(Request $request, string $vaultId)
    {
        $data = [
            'account_id' => $request->user()->account_id,
            'author_id' => $request->user()->id,
            'vault_id' => $vaultId,
        ];

        (new DestroyVault)->execute($data);

        return $this->respondObjectDeleted($vaultId);
    }
}
