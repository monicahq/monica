<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ApiUsage extends Model
{
    protected $table = 'api_usage';

    /**
     * Log a request made through the API.
     */
    public function log(\Illuminate\Http\Request $request)
    {
        $this->url = $request->fullUrl();
        $this->method = $request->getMethod();
        $this->client_ip = $request->getClientIp();
        $this->save();
    }
}
