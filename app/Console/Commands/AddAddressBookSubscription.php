<?php

namespace App\Console\Commands;

use App\Models\User\User;
use Illuminate\Console\Command;
use App\Services\DavClient\AddAddressBook;

class AddAddressBookSubscription extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'monica:newaddressbooksubscription
                            {--email= : Monica account to add subscription to}
                            {--url= : CardDAV url of the address book}
                            {--login= : Login}
                            {--password= : Password of the account}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add a new  all dav subscriptions';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $user = User::where('email', $this->option('email'))->firstOrFail();

        $url = $this->option('url') ?? $this->ask('url', 'CardDAV url of the address book');
        $login = $this->option('login') ?? $this->ask('login', 'Login name');
        $password = $this->option('password') ?? $this->ask('password', 'User password');

        app(AddAddressBook::class)->execute([
            'account_id' => $user->account_id,
            'user_id' => $user->id,
            'base_uri' => $url,
            'username' => $login,
            'password' => $password,
        ]);
    }
}
