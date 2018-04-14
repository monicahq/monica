<?php

namespace App\Http\Controllers;

use Auth;
use App\Changelog;
use Illuminate\Http\Request;

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
        $changelogs = Changelog::orderBy('created_at', 'desc')->get();

        return view('changelog.index')->withChangelogs($changelogs);
    }
}
