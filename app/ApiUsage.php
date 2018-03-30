<?php

namespace App;

class ApiUsage extends BaseMigrationModel
{
    protected $table = 'api_usage';

    /**
     * Log a request made through the API.
     * @param  Request $request
     */
    public function log(\Illuminate\Http\Request $request)
    {
        $this->url = $request->fullUrl();
        $this->method = $request->getMethod();
        $this->client_ip = $request->getClientIp();
        $this->save();
    }
}
