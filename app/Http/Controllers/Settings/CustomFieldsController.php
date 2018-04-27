<?php

namespace App\Http\Controllers\Settings;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class CustomFieldsController extends Controller
{
    public function index()
    {
        return view('settings.custom_fields.index')
                ->withCustomFields(auth()->user()->account->customFields);
    }

    public function new()
    {
        $customFields = \App\CustomFieldType::all();
        $customFieldsCollection = collect([]);
        foreach ($customFields as $customField) {
            $customFieldsCollection->push([
                'id' => $customField->id,
                'name' => $customField->type,
            ]);
        }

        return view('settings.custom_fields.new')
                ->withCustomFieldTypes($customFieldsCollection);
    }
}
