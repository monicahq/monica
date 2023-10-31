<?php

namespace App\Console\Commands;

use App\Models\Account\Account;
use Illuminate\Console\Command;

class ImportAccounts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'account:import_ldap
                            {--ldap_uri= : LDAP URI.}
                            {--ldap_user= : LDAP Bind DN.}
                            {--ldap_pass= : LDAP Bind Password.}
                            {--ldap_base= : LDAP base DN for searching.}
                            {--ldap_filter= : Filter to search for user accounts.}
                            {--ldap_attr_mail= : LDAP attribute to map to email (default: mail).}
                            {--ldap_attr_firstname= : LDAP attribute to map to firstname (default: gn).}
                            {--ldap_attr_lastname= : LDAP attribute to map to lastname (default: sn).}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import user accounts from LDAP';

    /**
     * Missing argument errors. Exposed for testing.
     */
    const ERROR_MISSING_LDAP_FILTER = '! You must specify an LDAP Filter';
    const ERROR_MISSING_LDAP_BASE = '! You must specify an LDAP Base';
    const ERROR_MISSING_LDAP_USER = '! You must specify an LDAP User';
    const ERROR_MISSING_LDAP_PASS = '! You must specify an LDAP Password';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $ldap_uri = $this->option('ldap_uri') ?? '127.0.0.1';
        $ldap_attr_mail = $this->option('ldap_attr_mail') ?? 'mail';
        $ldap_attr_firstname = $this->option('ldap_attr_firstname') ?? 'givenName';
        $ldap_attr_lastname = $this->option('ldap_attr_lastname') ?? 'sn';

        $ldap_user = $this->option('ldap_user');
        if (empty($ldap_user)) {
            $this->error($this::ERROR_MISSING_LDAP_USER);
        }

        $ldap_pass = $this->option('ldap_pass');
        if (empty($ldap_pass)) {
            $this->error($this::ERROR_MISSING_LDAP_PASS);
        }

        $ldap_base = $this->option('ldap_base');
        if (empty($ldap_base)) {
            $this->error($this::ERROR_MISSING_LDAP_BASE);
        }

        $ldap_filter = $this->option('ldap_filter');
        if (empty($ldap_filter)) {
            $this->error($this::ERROR_MISSING_LDAP_FILTER);
        }

        if (empty($ldap_user) || empty($ldap_pass) || empty($ldap_base) || empty($ldap_filter)) {
            return;
        }

        $ldap_conn = ldap_connect($ldap_uri);
        if (! $ldap_conn) {
            $this->error('Could not connect to LDAP URI');

            return;
        }
        if (! ldap_set_option($ldap_conn, LDAP_OPT_PROTOCOL_VERSION, 3)) {
            $this->error('Could not set LDAP protocol v3');

            return false;
        }

        try {
            $bind = ldap_bind($ldap_conn, $ldap_user, $ldap_pass);
            if (! $bind) {
                $this->error('Could not bind with given LDAP credentials');

                return;
            }
        } catch (\Exception $e) {
            $this->error($e->getMessage());

            return;
        }

        $ldap_res = [];
        try {
            $ldap_res = ldap_search($ldap_conn, $ldap_base, $ldap_filter, [$ldap_attr_mail, $ldap_attr_firstname, $ldap_attr_lastname]);
        } catch (\Exception $e) {
            $this->error($e->getMessage());

            return;
        }

        $ldap_data = ldap_get_entries($ldap_conn, $ldap_res);

        for ($i = 0; $i < $ldap_data['count']; $i++) {
            if (! (isset($ldap_data[$i][$ldap_attr_mail]) && $ldap_data[$i][$ldap_attr_mail]['count'] > 0)) {
                continue;
            }
            $user_mail = $ldap_data[$i][$ldap_attr_mail][0];
            $user_firstname = 'John';
            $user_lastname = 'Doe';
            $user_password = bin2hex(random_bytes(64));
            if (isset($ldap_data[$i][$ldap_attr_firstname]) && $ldap_data[$i][$ldap_attr_firstname]['count'] > 0) {
                $user_firstname = $ldap_data[$i][$ldap_attr_firstname][0];
            }
            if (isset($ldap_data[$i][$ldap_attr_lastname]) && $ldap_data[$i][$ldap_attr_lastname]['count'] > 0) {
                $user_lastname = $ldap_data[$i][$ldap_attr_lastname][0];
            }
            $this->info('Importing user "'.$user_mail.'"');
            try {
                Account::createDefault($user_firstname, $user_lastname, $user_mail, $user_password);
            } catch (\Exception $import_error) {
                $this->warn('Could not import user "'.$user_mail.'": '.$import_error->getMessage());
            }
        }
    }
}
