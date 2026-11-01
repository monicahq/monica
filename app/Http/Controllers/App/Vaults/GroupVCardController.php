<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Vaults;

use App\Actions\ExportGroupVCard;
use App\Http\Controllers\Controller;
use App\Models\Group;
use App\Models\Vault;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class GroupVCardController extends Controller
{
    public function show(Request $request, Vault $vault, Group $group): StreamedResponse
    {
        $cards = new ExportGroupVCard(
            user: $request->user(),
            vault: $vault,
            group: $group,
        )->execute();

        return response()->streamDownload(
            function () use ($cards): void {
                echo $cards;
            },
            (Str::slug($group->name) ?: $group->uuid).'.vcf',
            ['Content-Type' => 'text/vcard; charset=utf-8'],
        );
    }
}
