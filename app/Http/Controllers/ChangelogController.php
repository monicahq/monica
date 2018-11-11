<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;
use Auth;
use App\Helpers\InstanceHelper;
use Illuminate\Http\Request;
use App\Models\User\Changelog;

class ChangelogController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $changelogs = InstanceHelper::getChangelogEntries();

        return view('changelog.index')->withChangelogs($changelogs);
    }
}
