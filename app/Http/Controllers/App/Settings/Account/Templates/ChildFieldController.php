<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Settings\Account\Templates;

use App\Http\Controllers\Controller;
use App\Models\EntityType;
use App\Models\Field;
use App\Models\StructureVersionSection;
use App\Models\Template;
use App\Models\ValueList;
use App\ViewModels\Settings\Account\Templates\FieldCatalogueViewModel;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ChildFieldController extends Controller
{
    public function new(Request $request, Template $template, StructureVersionSection $structureVersionSection, Field $field): View
    {
        abort_unless($structureVersionSection->isReachableThrough($request->user(), $template), 404);
        abort_unless($field->section_version_id === $structureVersionSection->section_version_id && $field->type->isRepeater(), 404);

        return view('app.settings.account.field-type', [
            'row' => $structureVersionSection,
            'sectionName' => $structureVersionSection->title_override ?? $structureVersionSection->sectionVersion->section->name,
            'parent' => $field,
            'backHref' => route('settings.templateBuilder.field', ['template' => $template, 'page' => $structureVersionSection->structureVersionPage->key, 'field' => $field->id]),
            'backLabel' => __('Back to the template'),
            'current' => 'templates',
            'action' => route('settings.fields.create', [$template, $structureVersionSection]),
            'catalogue' => new FieldCatalogueViewModel(insideARepeater: true),
            'valueLists' => ValueList::query()
                ->where('account_id', $request->user()->account_id)
                ->where('is_shared', true)
                ->orderBy('position')
                ->get(),
            'entityTypes' => EntityType::query()
                ->where('account_id', $request->user()->account_id)
                ->where('is_active', true)
                ->orderBy('position')
                ->get(),
            'user' => $request->user(),
        ]);
    }
}
