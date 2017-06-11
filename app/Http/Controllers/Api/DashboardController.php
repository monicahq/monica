<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Auth;
use Response;
use Monica\Http\StatusCodes as StatusCodes;

class DashboardController
{

  public function index()
  {
    return Response::json(['msg' =>'ok' ], StatusCodes::HTTP_OK);
  }

  

}



?>
