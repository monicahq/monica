<?php

namespace App\Providers;

use App\Exceptions\MissingPrivateKeyException;
use Illuminate\Encryption\Encrypter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use function Safe\base64_decode;

class EncryptionServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('license.encrypter', function ($app) {
            $config = $app->make('config')->get('monica');

            return new Encrypter($this->parseKey($config), $config['licence_cipher']);
        });
    }

    /**
     * Parse the encryption key.
     *
     * @param  array  $config
     * @return string
     */
    protected function parseKey(array $config)
    {
        if (Str::startsWith($key = $this->key($config), $prefix = 'base64:')) {
            $key = base64_decode(Str::after($key, $prefix));
        }

        return $key;
    }

    /**
     * Extract the encryption key from the given configuration.
     *
     * @param  array  $config
     * @return string
     *
     * @throws \App\Exceptions\MissingPrivateKeyException
     */
    protected function key(array $config)
    {
        return tap($config['licence_private_key'], function ($key) {
            if (empty($key)) {
                throw new MissingPrivateKeyException();
            }
        });
    }
}
