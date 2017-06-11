<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Auth;
use Response;
use Monica\Http\StatusCodes;
use Monica\Repositories\EntryRepository;
use Monica\Transformers\EntryTransformer;
use JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use \Exception as Exception;
use Dingo\Api\Routing\Helpers;

class JournalController extends BaseAPIController
{

  public function index()
  {
      try {
          $repo = new EntryRepository();
          $entries = $repo->getJournalEntriesByAccount( Auth::user()->account_id );
          return $this->response->collection($entries, new EntryTransformer)->setStatusCode(200);
      } catch (Exception $e) {
          return Response::json(['error' => $e->getMessage() ], StatusCodes::HTTP_BAD_REQUEST);
      }
  }

}



?>
