<?php

use App\Models\WebauthnKey;

return [

    /*
    |--------------------------------------------------------------------------
    | LaravelWebauthn Master Switch
    |--------------------------------------------------------------------------
    |
    | This option may be used to disable LaravelWebauthn.
    |
    */

    'enable' => true,

    /*
    |--------------------------------------------------------------------------
    | Webauthn Guard
    |--------------------------------------------------------------------------
    |
    | Here you may specify which authentication guard Webauthn will use while
    | authenticating users. This value should correspond with one of your
    | guards that is already present in your "auth" configuration file.
    |
    */

    'guard' => 'web',

    /*
    |--------------------------------------------------------------------------
    | Username / Email
    |--------------------------------------------------------------------------
    |
    | This value defines which model attribute should be considered as your
    | application's "username" field. Typically, this might be the email
    | address of the users but you are free to change this value here.
    |
    */

    'username' => 'email',

    /*
    |--------------------------------------------------------------------------
    | Webauthn Routes Prefix / Subdomain
    |--------------------------------------------------------------------------
    |
    | Here you may specify which prefix Webauthn will assign to all the routes
    | that it registers with the application. If necessary, you may change
    | subdomain under which all of the Webauthn routes will be available.
    |
    */

    'prefix' => 'webauthn',

    'domain' => null,

    /*
    |--------------------------------------------------------------------------
    | Webauthn Routes Middleware
    |--------------------------------------------------------------------------
    |
    | Here you may specify which middleware Webauthn will assign to the routes
    | that it registers with the application. If necessary, you may change
    | these middleware but typically this provided default is preferred.
    |
    */

    'middleware' => ['web'],

    /*
    |--------------------------------------------------------------------------
    | Webauthn key model
    |--------------------------------------------------------------------------
    |
    | Here you may specify the model used to create Webauthn keys.
    |
    */

    'model' => WebauthnKey::class,

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    |
    | By default, Laravel Webauthn will throttle logins to five requests per
    | minute for every email and IP address combination. However, if you would
    | like to specify a custom rate limiter to call then you may specify it here.
    |
    */

    'limiters' => [
        'login' => 'login',
    ],

    /*
    |--------------------------------------------------------------------------
    | Redirect routes
    |--------------------------------------------------------------------------
    |
    | When using navigation, redirects to these url on success:
    | - login: after a successful login.
    | - register: after a successful Webauthn key creation.
    |
    | Redirects are not used in case of application/json requests.
    |
    */

    'redirects' => [
        'login' => '/vaults',
        'register' => '/user/profile',
        'key-confirmation' => '/user/profile',
    ],

    /*
    |--------------------------------------------------------------------------
    | View to load after middleware login request.
    |--------------------------------------------------------------------------
    |
    | The name of blade template to load:
    | - authenticate: when a user login, and has to validate Webauthn 2nd factor.
    | - register: when a user request to create a Webauthn key.
    |
    | If the views are empty or null, then the route will not be registered.
    |
    */

    'views' => [
        'authenticate' => null,
        'register' => null,
    ],

    /*
    |--------------------------------------------------------------------------
    | Webauthn logging
    |--------------------------------------------------------------------------
    |
    | Here you may specify the channel to which Webauthn will log messages.
    | This value should correspond with one of your loggers that is already
    | present in your "logging" configuration file. If left as null, it will
    | use the default logger for the application.
    |
    */

    'log' => null,

    /*
    |--------------------------------------------------------------------------
    | Session name
    |--------------------------------------------------------------------------
    |
    | Name of the session parameter to store the successful login.
    |
    */

    'session_name' => 'webauthn_auth',

    /*
    |--------------------------------------------------------------------------
    | Webauthn challenge length
    |--------------------------------------------------------------------------
    |
    | Length of the random string used in the challenge request.
    |
    */

    'challenge_length' => 32,

    /*
    |--------------------------------------------------------------------------
    | Webauthn timeout (milliseconds)
    |--------------------------------------------------------------------------
    |
    | Time that the caller is willing to wait for the call to complete.
    | See https://webauthn-doc.spomky-labs.com/symfony-bundle/configuration-references#timeout
    |
    */

    'timeout' => null,

    /*
    |--------------------------------------------------------------------------
    | Webauthn extension client input
    |--------------------------------------------------------------------------
    |
    | Optional authentication extension.
    | See https://www.w3.org/TR/webauthn/#client-extension-input
    |
    */

    'extensions' => [],

    /*
    |--------------------------------------------------------------------------
    | Webauthn icon
    |--------------------------------------------------------------------------
    |
    | Url which resolves to an image associated with the entity.
    | See https://www.w3.org/TR/webauthn/#dom-publickeycredentialentity-icon
    |
    */

    'icon' => env('WEBAUTHN_ICON'),

    /*
    |--------------------------------------------------------------------------
    | Webauthn Attestation Conveyance
    |--------------------------------------------------------------------------
    |
    | This parameter specify the preference regarding the attestation conveyance
    | during credential generation.
    | See https://www.w3.org/TR/webauthn/#enum-attestation-convey
    |
    | Supported: "none", "indirect", "direct", "enterprise".
    */

    'attestation_conveyance' => 'none',

    /*
    |--------------------------------------------------------------------------
    | Webauthn Public Key Credential Parameters
    |--------------------------------------------------------------------------
    |
    | List of allowed Cryptographic Algorithm Identifier.
    | See https://www.w3.org/TR/webauthn/#sctn-alg-identifier
    |
    */

    'public_key_credential_parameters' => [
        \Cose\Algorithms::COSE_ALGORITHM_ES256, // ECDSA with SHA-256
        \Cose\Algorithms::COSE_ALGORITHM_ES512, // ECDSA with SHA-512
        \Cose\Algorithms::COSE_ALGORITHM_RS256, // RSASSA-PKCS1-v1_5 with SHA-256
        \Cose\Algorithms::COSE_ALGORITHM_EDDSA, // EDDSA
        \Cose\Algorithms::COSE_ALGORITHM_ES384, // ECDSA with SHA-384
    ],

    /*
    |--------------------------------------------------------------------------
    | Credentials Attachment.
    |--------------------------------------------------------------------------
    |
    | Authentication can be tied to the current device (like when using Windows
    | Hello or Touch ID) or a cross-platform device (like USB Key). When this
    | is "null" the user will decide where to store his authentication info.
    |
    | See https://www.w3.org/TR/webauthn/#enum-attachment
    |
    | Supported: "null", "cross-platform", "platform".
    |
    */

    'attachment_mode' => null,

    /*
    |--------------------------------------------------------------------------
    | User presence and verification
    |--------------------------------------------------------------------------
    |
    | Most authenticators and smartphones will ask the user to actively verify
    | themselves for log in. Use "required" to always ask verify, "preferred"
    | to ask when possible, and "discouraged" to just ask for user presence.
    |
    | See https://www.w3.org/TR/webauthn/#enum-userVerificationRequirement
    |
    | Supported: "required", "preferred", "discouraged".
    |
    */

    'user_verification' => 'preferred',

    /*
    |--------------------------------------------------------------------------
    | The resident key
    |--------------------------------------------------------------------------
    |
    | When userless is set to 'preferred' or 'required', the resident key will be
    | forced to be 'required' automatically.
    |
    | See https://www.w3.org/TR/webauthn/#enum-residentKeyRequirement
    |
    | Supported: "null", "required", "preferred", "discouraged".
    |
    */

    'resident_key' => 'preferred',

    /*
    |--------------------------------------------------------------------------
    | Userless (One touch, Typeless) login
    |--------------------------------------------------------------------------
    |
    | You may activate userless login, also known as one-touch login or typeless
    | login for devices when they're being registered. It's recommended to change
    | this to preferred in this case, since not all devices support this feature.
    | If this is activated (not null or discouraged), login verification will be
    | mandatory.
    |
    | This doesn't affect the login procedure; only the attestation (registration).
    |
    | Supported: "null", "required", "preferred", "discouraged".
    |
    */

    'userless' => (bool) env('WEBAUTHN_USERLESS', true),

];
