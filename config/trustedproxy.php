<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Application trusted proxies
    |--------------------------------------------------------------------------
    |
    | Set trusted proxy IP addresses.
    | Both IPv4 and IPv6 addresses are supported, along with CIDR notation.
    |
    | The "*" character is syntactic sugar within TrustedProxy to trust any
    | proxy that connects directly to your server, a requirement when you
    | cannot know the address of your proxy (e.g. if using ELB or similar).
    |
    */

    'proxies' => env('APP_TRUSTED_PROXIES', null),

];
