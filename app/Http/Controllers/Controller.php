<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    /**
     * Handle the ModelNotFoundException.
     *
     * @param ModelNotFoundException $e
     * @return \Illuminate\Http\Response
     */
    protected function handleModelNotFound(ModelNotFoundException $e)
    {
        return $this->respondUnauthorized($e->getMessage());
    }

    /**
     * Respond with an Unauthorized message.
     *
     * @param string $errorMessage
     * @return \Illuminate\Http\Response
     */
    protected function respondUnauthorized($errorMessage = null)
    {
        $data = [];
        if ($errorMessage) {
            $data = ['errors' => $errorMessage];
        }

        return response()->json([
            'message' => trans('app.error_unauthorized'),
        ] + $data, 400);
    }
}
