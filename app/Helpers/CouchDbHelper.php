<?php

namespace App\Helpers;

use PHPOnCouch\CouchAdmin;
use PHPOnCouch\CouchClient;

class CouchDbHelper
{
    public static function client($dbName)
    {
        $config = config('database.connections.couchdb');
        // dd($config);
        return new CouchClient('http://'.$config['user'].':'.$config['password'].'@'.$config['host'].':'.$config['port'], $dbName);
    }

    public static function admin($dbName = 'users')
    {
        $client = self::client($dbName);

        return new CouchAdmin($client);
    }

    public static function getAccountDatabase($accountId)
    {
        return self::client(self::getAccountDatabaseName($accountId));
    }

    public static function getAccountDatabaseName($accountId)
    {
        return 'accountdb-'.$accountId;
    }

    public static function getAccountDatabaseRoleName($accountId)
    {
        return 'account-'.$accountId;
    }
}
