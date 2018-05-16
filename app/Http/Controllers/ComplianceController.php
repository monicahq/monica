<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ComplianceController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        return view('compliance.index');
    }
}
