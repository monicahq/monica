<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Vaults;

use App\Actions\ChangeContactTemplate;
use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\StructureVersion;
use App\Models\Template;
use App\Models\Vault;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

class ContactTemplateController extends Controller
{
    public function update(Request $request, Vault $vault, Contact $contact): RedirectResponse
    {
        abort_unless($contact->isReachableThrough($request->user(), $vault), 404);

        $validated = $request->validate([
            'template' => ['required', 'string', 'max:255'],
            'structure_version_id' => ['nullable', 'integer'],
        ]);

        $template = Template::query()
            ->where('account_id', $request->user()->account_id)
            ->where('uuid', $validated['template'])
            ->first();

        abort_if($template === null, 404);

        $version = isset($validated['structure_version_id'])
            ? StructureVersion::query()->where('account_id', $request->user()->account_id)->whereKey($validated['structure_version_id'])->first()
            : null;

        abort_if(isset($validated['structure_version_id']) && $version === null, 404);

        try {
            new ChangeContactTemplate(
                user: $request->user(),
                contact: $contact,
                template: $template,
                structureVersion: $version,
            )->execute();
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['template' => $e->getMessage()]);
        }

        return redirect()
            ->route('contacts.show', ['vault' => $vault, 'contact' => $contact])
            ->with('status', __(':name uses the :template template now.', [
                'name' => $contact->name,
                'template' => $template->name,
            ]));
    }
}
