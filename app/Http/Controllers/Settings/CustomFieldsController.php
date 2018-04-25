<?php

namespace App\Http\Controllers\Settings;

use App\CustomField;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class CustomFieldsController extends Controller
{
    public function index()
    {
        return view('settings.custom_fields.index')
                ->withCustomFields(auth()->user()->account->customFields);
    }
}
