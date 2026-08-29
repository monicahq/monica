<?php

declare(strict_types=1);

use App\Models\WebauthnKey;
use Cose\Algorithms;

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

    'prefix' => 'passkeys',

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
    | Password fallback
    |--------------------------------------------------------------------------
    |
    | Whether the webauthn user provider still checks a password when what it
    | was handed is not a signed challenge. This key is absent from the config
    | the package publishes, and it defaults to true, so it is written here on
    | purpose: the users provider in config/auth.php is the only one this
    | application has, so every password sign in goes through that provider.
    | Set this to false and no password anywhere in the application is checked.
    |
    */

    'fallback' => true,

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
    | - key-confirmation: after a successful Webauthn key confirmation.
    |
    | Redirects are not used in case of application/json requests.
    |
    */

    'redirects' => [
        'login' => null,
        'register' => null,
        'key-confirmation' => null,
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

    'timeout' => 60000,

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
        Algorithms::COSE_ALGORITHM_ES256, // ECDSA with SHA-256
        Algorithms::COSE_ALGORITHM_ES512, // ECDSA with SHA-512
        Algorithms::COSE_ALGORITHM_RS256, // RSASSA-PKCS1-v1_5 with SHA-256
        Algorithms::COSE_ALGORITHM_EDDSA, // EDDSA
        Algorithms::COSE_ALGORITHM_ES384, // ECDSA with SHA-384
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
    | This should be set to "required" when userless is true.
    |
    */

    'user_verification' => 'required',

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
    | This should be set to "required" when userless is true.
    |
    */

    'resident_key' => 'required',

    /*
    |--------------------------------------------------------------------------
    | Userless (One touch, Typeless) login
    |--------------------------------------------------------------------------
    |
    | This activates userless login, also known as one-touch login or typeless
    | login for devices when they're being registered.
    |
    */

    'userless' => (bool) env('WEBAUTHN_USERLESS', true),

];
