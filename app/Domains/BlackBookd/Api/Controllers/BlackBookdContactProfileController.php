<?php

namespace App\Domains\BlackBookd\Api\Controllers;

use App\Domains\BlackBookd\ManageContactProfiles\Services\UpsertBlackBookdContactProfile;
use App\Http\Controllers\ApiController;
use App\Http\Resources\BlackBookd\ContactProfileResource;
use App\Models\BlackBookdContactProfile;
use Illuminate\Http\Request;

class BlackBookdContactProfileController extends ApiController
{
    public function __construct()
    {
        $this->middleware('abilities:read')->only(['index', 'show']);
        $this->middleware('abilities:write')->only(['update']);
        parent::__construct();
    }

    private function vault(Request $request, string $vaultId)
    {
        return $request->user()->account->vaults()->findOrFail($vaultId);
    }

    public function index(Request $request, string $vaultId)
    {
        $vault = $this->vault($request, $vaultId);
        $contactIds = $vault->contacts()->pluck('id');
        $profiles = BlackBookdContactProfile::whereIn('contact_id', $contactIds)->paginate($this->getLimitPerPage());

        return ContactProfileResource::collection($profiles);
    }

    public function show(Request $request, string $vaultId, string $contactId)
    {
        $vault = $this->vault($request, $vaultId);
        $vault->contacts()->findOrFail($contactId);
        $profile = BlackBookdContactProfile::where('contact_id', $contactId)->firstOrFail();

        return new ContactProfileResource($profile);
    }

    public function update(Request $request, string $vaultId, string $contactId)
    {
        $data = array_merge([
            'account_id' => $request->user()->account_id,
            'author_id' => $request->user()->id,
            'vault_id' => $vaultId,
            'contact_id' => $contactId,
        ], $request->only([
            'hiv_status', 'on_prep', 'last_test_date', 'kinks', 'boundaries', 'relationship_type', 'notes',
        ]));

        $profile = (new UpsertBlackBookdContactProfile)->execute($data);

        return new ContactProfileResource($profile);
    }
}