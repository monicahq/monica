<?php

namespace App\Http\Controllers;

use App\User;
use App\Statistic;
use App\Http\Requests;
use Illuminate\Http\Request;

class MarketingController extends Controller
{
    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('marketing/homepage');
    }

    public function release()
    {
        return view('marketing/release');
    }

    public function privacy()
    {
        return view('marketing/privacy');
    }

    public function statistics()
    {
        $statistics = Statistic::orderBy('id', 'desc')->get();
        $data = [
          'statistics' => $statistics,
        ];

        return view('marketing/statistics', $data);
    }

    public function getMails()
    {
        $users = User::all();
        foreach ($users as $user) {
            echo $user->email.'<br>';
        }
    }
}
