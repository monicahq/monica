<?php

namespace App\Http\Controllers;

use Auth;
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
        $changelogs = auth()->user()->changelogs()->orderBy('created_at', 'desc')->get();

        auth()->user()->markChangelogAsRead();

        return view('changelog.index')->withChangelogs($changelogs);
    }
}
