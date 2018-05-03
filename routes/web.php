<?php

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Sabre\DAV, Sabre\CalDAV, Sabre\DAVACL;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

if (App::environment('production')) {
    URL::forceScheme('https');
}

Route::get('/', 'Auth\LoginController@showLoginOrRegister')->name('login');

Auth::routes();

Route::get('/password/reset', 'Auth\ForgotPasswordController@showLinkRequestForm');

Route::get('/invitations/accept/{key}', 'SettingsController@acceptInvitation');
Route::post('/invitations/accept/{key}', 'SettingsController@storeAcceptedInvitation');

Route::middleware(['auth'])->group(function () {
    Route::get('/logout', 'Auth\LoginController@logout');
});

Route::middleware(['auth', '2fa'])->group(function () {
    Route::group(['as' => 'dashboard'], function () {
        Route::get('/dashboard', 'DashboardController@index')->name('.index');
        Route::get('/dashboard/calls', 'DashboardController@calls');
        Route::get('/dashboard/notes', 'DashboardController@notes');
        Route::post('/dashboard/setTab', 'DashboardController@setTab');
    });
    Route::post('/validate2fa', 'DashboardController@index');

    Route::get('/changelog', 'ChangelogController@index');

    Route::group(['as' => 'people'], function () {
        Route::get('/people', 'ContactsController@index')->name('.index');
        Route::get('/people/add', 'ContactsController@create')->name('.create');
        Route::get('/people/notfound', 'ContactsController@missing')->name('.missing');
        Route::post('/people', 'ContactsController@store')->name('.store');

        // Dashboard
        Route::get('/people/{contact}', 'ContactsController@show')->name('.show');
        Route::get('/people/{contact}/edit', 'ContactsController@edit')->name('.edit');
        Route::post('/people/{contact}/update', 'ContactsController@update')->name('.update');
        Route::delete('/people/{contact}', 'ContactsController@delete')->name('.delete');

        // Contact information
        Route::get('/people/{contact}/contactfield', 'Contacts\\ContactFieldsController@getContactFields');
        Route::post('/people/{contact}/contactfield', 'Contacts\\ContactFieldsController@storeContactField');
        Route::put('/people/{contact}/contactfield/{contact_field}', 'Contacts\\ContactFieldsController@editContactField');
        Route::delete('/people/{contact}/contactfield/{contact_field}', 'Contacts\\ContactFieldsController@destroyContactField');
        Route::get('/people/{contact}/contactfieldtypes', 'Contacts\\ContactFieldsController@getContactFieldTypes');

        // Export as vCard
        Route::get('/people/{contact}/vcard', 'ContactsController@vcard');

        // Addresses
        Route::get('/people/{contact}/countries', 'Contacts\\AddressesController@getCountries');
        Route::get('/people/{contact}/addresses', 'Contacts\\AddressesController@get');
        Route::post('/people/{contact}/addresses', 'Contacts\\AddressesController@store');
        Route::put('/people/{contact}/addresses/{address}', 'Contacts\\AddressesController@edit');
        Route::delete('/people/{contact}/addresses/{address}', 'Contacts\\AddressesController@destroy');

        // Work information
        Route::get('/people/{contact}/work/edit', ['as' => '.edit', 'uses' => 'ContactsController@editWork'])->name('.work.edit');
        Route::post('/people/{contact}/work/update', 'ContactsController@updateWork')->name('.work.update');

        // Introductions
        Route::get('/people/{contact}/introductions/edit', 'Contacts\\IntroductionsController@edit')->name('.introductions.edit');
        Route::post('/people/{contact}/introductions/update', 'Contacts\\IntroductionsController@update')->name('.introductions.update');

        // Tags
        Route::post('/people/{contact}/tags/update', 'Contacts\\TagsController@update')->name('.tags.update');

        // Notes
        Route::get('/people/{contact}/notes', 'Contacts\\NotesController@get');
        Route::post('/people/{contact}/notes', 'Contacts\\NotesController@store')->name('.notes.store');
        Route::put('/people/{contact}/notes/{note}', 'Contacts\\NotesController@update');
        Route::delete('/people/{contact}/notes/{note}', 'Contacts\\NotesController@destroy');
        Route::post('/people/{contact}/notes/{note}/toggle', 'Contacts\\NotesController@toggle');

        // Food preferencies
        Route::get('/people/{contact}/food', 'ContactsController@editFoodPreferencies')->name('.food');
        Route::post('/people/{contact}/food/save', 'ContactsController@updateFoodPreferencies')->name('.food.update');

        // Relationships
        Route::get('/people/{contact}/relationships/new', 'Contacts\\RelationshipsController@new');
        Route::post('/people/{contact}/relationships/store', 'Contacts\\RelationshipsController@store')->name('.relationships.store');
        Route::get('/people/{contact}/relationships/{otherContact}/edit', 'Contacts\\RelationshipsController@edit')->name('.relationships.edit');
        Route::post('/people/{contact}/relationships/{otherContact}', 'Contacts\\RelationshipsController@update')->name('.relationships.update');
        Route::delete('/people/{contact}/relationships/{otherContact}', 'Contacts\\RelationshipsController@destroy')->name('.relationships.delete');

        // Pets
        Route::get('/people/{contact}/pets', 'Contacts\\PetsController@get');
        Route::post('/people/{contact}/pet', 'Contacts\\PetsController@store');
        Route::put('/people/{contact}/pet/{pet}', 'Contacts\\PetsController@update');
        Route::delete('/people/{contact}/pet/{pet}', 'Contacts\\PetsController@trash');
        Route::get('/petcategories', 'Contacts\\PetsController@getPetCategories');

        // Reminders
        Route::get('/people/{contact}/reminders/add', 'Contacts\\RemindersController@create')->name('.reminders.add');
        Route::post('/people/{contact}/reminders/store', 'Contacts\\RemindersController@store')->name('.reminders.store');
        Route::get('/people/{contact}/reminders/{reminder}/edit', 'Contacts\\RemindersController@edit')->name('.reminders.edit');
        Route::put('/people/{contact}/reminders/{reminder}', 'Contacts\\RemindersController@update')->name('.reminders.update');
        // Special route to delete reminders. In one migration in summer '17, I
        // accidentely f**ked up the reminders table by messing up the contact ids
        // and now the only way to delete those reminders is to bypass the ReminderRequest
        // by creating a new route.
        Route::delete('/people/{contact}/reminders/{rmd}', 'Contacts\\RemindersController@destroy')->name('.reminders.delete');

        // Tasks
        Route::get('/people/{contact}/tasks', 'Contacts\\TasksController@get');
        Route::post('/people/{contact}/tasks', 'Contacts\\TasksController@store');
        Route::post('/people/{contact}/tasks/{task}/toggle', 'Contacts\\TasksController@toggle');
        Route::put('/people/{contact}/tasks/{task}', 'Contacts\\TasksController@update');
        Route::delete('/people/{contact}/tasks/{task}', 'Contacts\\TasksController@destroy')->name('.tasks.delete');

        // Gifts
        Route::get('/people/{contact}/gifts', 'Contacts\\GiftsController@get');
        Route::post('/people/{contact}/gifts/{gift}/toggle', 'Contacts\\GiftsController@toggle');
        Route::get('/people/{contact}/gifts/add', 'Contacts\\GiftsController@create')->name('.gifts.add');
        Route::get('/people/{contact}/gifts/{gift}/edit', 'Contacts\\GiftsController@edit');
        Route::post('/people/{contact}/gifts/store', 'Contacts\\GiftsController@store')->name('.gifts.store');
        Route::post('/people/{contact}/gifts/{gift}/update', 'Contacts\\GiftsController@update')->name('.gifts.update');
        Route::delete('/people/{contact}/gifts/{gift}', 'Contacts\\GiftsController@destroy')->name('.gifts.delete');

        // Debt
        Route::get('/people/{contact}/debt/add', 'Contacts\\DebtController@create')->name('.debt.add');
        Route::post('/people/{contact}/debt/store', 'Contacts\\DebtController@store')->name('.debt.store');
        Route::get('/people/{contact}/debt/{debt}/edit', 'Contacts\\DebtController@edit')->name('.debt.edit');
        Route::put('/people/{contact}/debt/{debt}', 'Contacts\\DebtController@update')->name('.debt.update');
        Route::delete('/people/{contact}/debt/{debt}', 'Contacts\\DebtController@destroy')->name('.debt.delete');

        // Phone calls
        Route::post('/people/{contact}/call/store', 'Contacts\\CallsController@store')->name('.call.store');
        Route::delete('/people/{contact}/call/{call}', 'Contacts\\CallsController@destroy')->name('.call.delete');

        // Search
        Route::post('/people/search', 'ContactsController@search')->name('people.search');

        // Stay in touch information
        Route::post('/people/{contact}/stayintouch', 'ContactsController@stayInTouch');
    });

    // Activities
    Route::group(['as' => 'activities'], function () {
        Route::get('/activities/add/{contact}', 'ActivitiesController@create')->name('.add');
        Route::post('/activities/store/{contact}', 'ActivitiesController@store')->name('.store');
        Route::get('/activities/{activity}/edit/{contact}', 'ActivitiesController@edit')->name('.edit');
        Route::put('/activities/{activity}/{contact}', 'ActivitiesController@update')->name('.update');
        Route::delete('/activities/{activity}', 'ActivitiesController@destroy')->name('.delete');
    });

    Route::group(['as' => 'journal'], function () {
        Route::get('/journal', ['as' => '.index', 'uses' => 'JournalController@index']);
        Route::get('/journal/entries', 'JournalController@list')->name('.list');
        Route::get('/journal/entries/{journalEntry}', 'JournalController@get');
        Route::get('/journal/hasRated', 'JournalController@hasRated');
        Route::post('/journal/day', 'JournalController@storeDay');
        Route::delete('/journal/day/{day}', 'JournalController@trashDay');

        Route::get('/journal/add', ['as' => '.create', 'uses' => 'JournalController@create']);
        Route::post('/journal/create', ['as' => '.create', 'uses' => 'JournalController@save']);
        Route::delete('/journal/{entryId}', 'JournalController@deleteEntry');
    });

    Route::group(['as' => 'settings'], function () {
        Route::get('/settings', ['as' => '.index', 'uses' => 'SettingsController@index']);
        Route::post('/settings/delete', ['as' => '.delete', 'uses' => 'SettingsController@delete']);
        Route::post('/settings/reset', ['as' => '.reset', 'uses' => 'SettingsController@reset']);
        Route::post('/settings/save', 'SettingsController@save');
        Route::get('/settings/export', 'SettingsController@export')->name('.export');
        Route::get('/settings/exportToSql', 'SettingsController@exportToSQL');

        Route::get('/settings/personalization', 'Settings\\PersonalizationController@index')->name('.personalization');
        Route::get('/settings/personalization/contactfieldtypes', 'Settings\\PersonalizationController@getContactFieldTypes');
        Route::post('/settings/personalization/contactfieldtypes', 'Settings\\PersonalizationController@storeContactFieldType');
        Route::put('/settings/personalization/contactfieldtypes/{contactfieldtype_id}', 'Settings\\PersonalizationController@editContactFieldType');
        Route::delete('/settings/personalization/contactfieldtypes/{contactfieldtype_id}', 'Settings\\PersonalizationController@destroyContactFieldType');

        Route::get('/settings/personalization/genders', 'Settings\\GendersController@getGenderTypes');
        Route::post('/settings/personalization/genders', 'Settings\\GendersController@storeGender');
        Route::put('/settings/personalization/genders/{gender}', 'Settings\\GendersController@updateGender');
        Route::delete('/settings/personalization/genders/{gender}/replaceby/{gender_id}', 'Settings\\GendersController@destroyAndReplaceGender');
        Route::delete('/settings/personalization/genders/{gender}', 'Settings\\GendersController@destroyGender');

        Route::get('/settings/personalization/reminderrules', 'Settings\\ReminderRulesController@get');
        Route::post('/settings/personalization/reminderrules/{reminderRule}', 'Settings\\ReminderRulesController@toggle');

        Route::get('/settings/personalization/modules', 'Settings\\ModulesController@get');
        Route::post('/settings/personalization/modules/{module}', 'Settings\\ModulesController@toggle');

        Route::get('/settings/import', 'SettingsController@import')->name('.import');
        Route::get('/settings/import/report/{importjobid}', 'SettingsController@report')->name('.report');
        Route::get('/settings/import/upload', 'SettingsController@upload')->name('.upload');
        Route::post('/settings/import/storeImport', 'SettingsController@storeImport')->name('.storeImport');

        Route::get('/settings/users', 'SettingsController@users')->name('.users');
        Route::get('/settings/users/add', 'SettingsController@addUser')->name('.users.add');
        Route::delete('/settings/users/{user}', ['as' => '.users.delete', 'uses' => 'SettingsController@deleteAdditionalUser']);
        Route::post('/settings/users/save', 'SettingsController@inviteUser')->name('.users.save');
        Route::delete('/settings/users/invitations/{invitation}', 'SettingsController@destroyInvitation');

        Route::get('/settings/subscriptions', 'Settings\\SubscriptionsController@index')->name('.subscriptions.index');
        Route::get('/settings/subscriptions/upgrade', 'Settings\\SubscriptionsController@upgrade')->name('.subscriptions.upgrade');
        Route::get('/settings/subscriptions/upgrade/success', 'Settings\\SubscriptionsController@upgradeSuccess')->name('.subscriptions.upgrade.success');
        Route::post('/settings/subscriptions/processPayment', 'Settings\\SubscriptionsController@processPayment');
        Route::get('/settings/subscriptions/invoice/{invoice}', 'Settings\\SubscriptionsController@downloadInvoice');
        Route::get('/settings/subscriptions/downgrade', 'Settings\\SubscriptionsController@downgrade')->name('.subscriptions.downgrade');
        Route::post('/settings/subscriptions/downgrade', 'Settings\\SubscriptionsController@processDowngrade');
        Route::get('/settings/subscriptions/downgrade/success', 'Settings\\SubscriptionsController@downgradeSuccess')->name('.subscriptions.upgrade.success');

        Route::get('/settings/tags', 'SettingsController@tags')->name('.tags');
        Route::get('/settings/tags/add', 'SettingsController@addUser')->name('.tags.add');
        Route::delete('/settings/tags/{user}', ['as' => '.tags.delete', 'uses' => 'SettingsController@deleteTag']);

        Route::get('/settings/api', 'SettingsController@api')->name('.api');

        // Security
        Route::get('/settings/security', 'SettingsController@security')->name('.security');
        Route::post('/settings/security/passwordChange', 'Auth\\PasswordChangeController@passwordChange');
        Route::get('/settings/security/2fa-enable', 'Settings\\MultiFAController@enableTwoFactor')->name('.security.2fa-enable');
        Route::post('/settings/security/2fa-enable', 'Settings\\MultiFAController@validateTwoFactor');
        Route::get('/settings/security/2fa-disable', 'Settings\\MultiFAController@disableTwoFactor')->name('.security.2fa-disable');
        Route::post('/settings/security/2fa-disable', 'Settings\\MultiFAController@deactivateTwoFactor');
    });
});

// https://github.com/sabre-io/dav/blob/master/tests/Sabre/DAVACL/PrincipalBackend/AbstractPDOTest.php
// https://github.com/sabre-io/dav/blob/master/lib/DAVACL/PrincipalBackend/PDO.php
// https://github.com/sabre-io/dav/tree/master/lib/DAV/Browser
// https://github.com/sabre-io/dav/blob/master/lib/CardDAV/Backend/PDO.php
// http://sabre.io/dav/caldav-carddav-integration-guide/


$verbs = [
    'GET',
    'HEAD',
    'POST',
    'PUT',
    'PATCH',
    'DELETE',
    'PROPFIND',
    'PROPPATCH',
    'MKCOL',
    'COPY',
    'MOVE',
    'LOCK',
    'UNLOCK'
];

Illuminate\Routing\Router::$verbs = $verbs;

class MonicaSabreBackend implements Sabre\DAV\Auth\Backend\BackendInterface {


    /**
     * When this method is called, the backend must check if authentication was
     * successful.
     *
     * The returned value must be one of the following
     *‹
     * [true, "principals/username"]
     * [false, "reason for failure"]
     *
     * If authentication was successful, it's expected that the authentication
     * backend returns a so-called principal url.
     *
     * Examples of a principal url:
     *
     * principals/admin
     * principals/user1
     * principals/users/joe
     * principals/uid/‹123457
     *
     * If you don't use WebDAV ACL (RFC3744) we recommend that you simply
     * return a string such as:
     *
     * principals/users/[username]
     *
     * @param RequestInterface $request
     * @param ResponseInterface $response
     * @return array
     */
    function check(Sabre\HTTP\RequestInterface $request, Sabre\HTTP\ResponseInterface $response) {
        if ($request->getRawServerValue('PHP_AUTH_USER') == 'samuel' && $request->getRawServerValue('PHP_AUTH_PW') == 'samuel') {
            return [true, "principals/users/samuel"];
        }

        return [false, "Unknown user or password"];
    }

    /**
     * This method is called when a user could not be authenticated, and
     * authentication was required for the current request.
     *
     * This gives you the opportunity to set authentication headers. The 401
     * status code will already be set.
     *
     * In this case of Basic Auth, this would for example mean that the
     * following header needs to be set:
     *
     * $response->addHeader('WWW-Authenticate', 'Basic realm=SabreDAV');
     *
     * Keep in mind that in the case of multiple authentication backends, other
     * WWW-Authenticate headers may already have been set, and you'll want to
     * append your own WWW-Authenticate header instead of overwriting the
     * existing one.
     *
     * @param RequestInterface $request
     * @param ResponseInterface $response
     * @return void
     */
    function challenge(Sabre\HTTP\RequestInterface $request, Sabre\HTTP\ResponseInterface $response) {
        $response->addHeader('WWW-Authenticate', 'Basic realm=SabreDAV');
    }

}

class MonicaPrincipleBackend implements Sabre\DAVACL\PrincipalBackend\BackendInterface {

    /**
     * Returns a list of principals based on a prefix.
     *
     * This prefix will often contain something like 'principals'. You are only
     * expected to return principals that are in this base path.
     *
     * You are expected to return at least a 'uri' for every user, you can
     * return any additional properties if you wish so. Common properties are:
     *   {DAV:}displayname
     *   {http://sabredav.org/ns}email-address - This is a custom SabreDAV
     *     field that's actually injected in a number of other properties. If
     *     you have an email address, use this property.
     *
     * @param string $prefixPath
     * @return array
     */
    function getPrincipalsByPrefix($prefixPath) {
        debug('getPrincipalsByPrefix', $prefixPath);
        return [
            [
                'uri'                                   => 'principals/users/samuel',
                '{http://sabredav.org/ns}email-address' => 'samuel@sava.be',
                '{DAV:}displayname'                     => 'Samuel Vandamme',
            ]
        ];
    }

    /**
     * Returns a specific principal, specified by it's path.
     * The returned structure should be the exact same as from
     * getPrincipalsByPrefix.
     *
     * @param string $path
     * @return array
     */
    function getPrincipalByPath($path) {
        debug('getPrincipalByPath', $path);

        return [
            'uri'                                   => 'principals/users/samuel',
            '{http://sabredav.org/ns}email-address' => 'samuel@sava.be',
            '{DAV:}displayname'                     => 'Samuel Vandamme',
        ];
    }

    /**
     * Updates one ore more webdav properties on a principal.
     *
     * The list of mutations is stored in a Sabre\DAV\PropPatch object.
     * To do the actual updates, you must tell this object which properties
     * you're going to process with the handle() method.
     *
     * Calling the handle method is like telling the PropPatch object "I
     * promise I can handle updating this property".
     *
     * Read the PropPatch documentation for more info and examples.
     *
     * @param string $path
     * @param \Sabre\DAV\PropPatch $propPatch
     * @return void
     */
    function updatePrincipal($path, \Sabre\DAV\PropPatch $propPatch) {
        dd('updatePrincipal');
    }

    /**
     * This method is used to search for principals matching a set of
     * properties.
     *
     * This search is specifically used by RFC3744's principal-property-search
     * REPORT.
     *
     * The actual search should be a unicode-non-case-sensitive search. The
     * keys in searchProperties are the WebDAV property names, while the values
     * are the property values to search on.
     *
     * By default, if multiple properties are submitted to this method, the
     * various properties should be combined with 'AND'. If $test is set to
     * 'anyof', it should be combined using 'OR'.
     *
     * This method should simply return an array with full principal uri's.
     *
     * If somebody attempted to search on a property the backend does not
     * support, you should simply return 0 results.
     *
     * You can also just return 0 results if you choose to not support
     * searching at all, but keep in mind that this may stop certain features
     * from working.
     *
     * @param string $prefixPath
     * @param array $searchProperties
     * @param string $test
     * @return array
     */
    function searchPrincipals($prefixPath, array $searchProperties, $test = 'allof') {
        dd('searchPrincipals');
    }

    /**
     * Finds a principal by its URI.
     *
     * This method may receive any type of uri, but mailto: addresses will be
     * the most common.
     *
     * Implementation of this API is optional. It is currently used by the
     * CalDAV system to find principals based on their email addresses. If this
     * API is not implemented, some features may not work correctly.
     *
     * This method must return a relative principal path, or null, if the
     * principal was not found or you refuse to find it.
     *
     * @param string $uri
     * @param string $principalPrefix
     * @return string
     */
    function findByUri($uri, $principalPrefix) {
        dd('findByUri');
    }

    /**
     * Returns the list of members for a group-principal
     *
     * @param string $principal
     * @return array
     */
    function getGroupMemberSet($principal) {
        debug('getGroupMemberSet');
        return [
            "principals/users/samuel"
        ];
    }

    /**
     * Returns the list of groups a principal is a member of
     *
     * @param string $principal
     * @return array
     */
    function getGroupMembership($principal) {
        debug('getGroupMembership');
        return [
            "principals/users/samuel"
        ];
    }

    /**
     * Updates the list of group members for a group principal.
     *
     * The principals should be passed as a list of uri's.
     *
     * @param string $principal
     * @param array $members
     * @return void
     */
    function setGroupMemberSet($principal, array $members) {
        dd('setGroupMemberSet');
    }
}

class MonicaCardDAVBackend implements Sabre\CardDAV\Backend\BackendInterface {
    /**
     * Returns the list of addressbooks for a specific user.
     *
     * Every addressbook should have the following properties:
     *   id - an arbitrary unique id
     *   uri - the 'basename' part of the url
     *   principaluri - Same as the passed parameter
     *
     * Any additional clark-notation property may be passed besides this. Some
     * common ones are :
     *   {DAV:}displayname
     *   {urn:ietf:params:xml:ns:carddav}addressbook-description
     *   {http://calendarserver.org/ns/}getctag
     *
     * @param string $principalUri
     * @return array
     */
    function getAddressBooksForUser($principalUri) {
        debug('getAddressBooksForUser');
        return [
            [
                "id" => '00',
                "uri" => 'no-idea',
                "principaluri" => 'principals/users/samuel'
            ]
        ];
    }

    /**
     * Updates properties for an address book.
     *
     * The list of mutations is stored in a Sabre\DAV\PropPatch object.
     * To do the actual updates, you must tell this object which properties
     * you're going to process with the handle() method.
     *
     * Calling the handle method is like telling the PropPatch object "I
     * promise I can handle updating this property".
     *
     * Read the PropPatch documentation for more info and examples.
     *
     * @param string $addressBookId
     * @param \Sabre\DAV\PropPatch $propPatch
     * @return void
     */
    function updateAddressBook($addressBookId, \Sabre\DAV\PropPatch $propPatch) {
        dd('updateAddressBook');
        return false;
    }

    /**
     * Creates a new address book.
     *
     * This method should return the id of the new address book. The id can be
     * in any format, including ints, strings, arrays or objects.
     *
     * @param string $principalUri
     * @param string $url Just the 'basename' of the url.
     * @param array $properties
     * @return mixed
     */
    function createAddressBook($principalUri, $url, array $properties) {
        dd('createAddressBook');
        return false;
    }

    /**
     * Deletes an entire addressbook and all its contents
     *
     * @param mixed $addressBookId
     * @return void
     */
    function deleteAddressBook($addressBookId) {
        dd('deleteAddressBook');
        return false;
    }

    /**
     * Returns all cards for a specific addressbook id.
     *
     * This method should return the following properties for each card:
     *   * carddata - raw vcard data
     *   * uri - Some unique url
     *   * lastmodified - A unix timestamp
     *
     * It's recommended to also return the following properties:
     *   * etag - A unique etag. This must change every time the card changes.
     *   * size - The size of the card in bytes.
     *
     * If these last two properties are provided, less time will be spent
     * calculating them. If they are specified, you can also ommit carddata.
     * This may speed up certain requests, especially with large cards.
     *
     * @param mixed $addressbookId
     * @return array
     */
    function getCards($addressbookId) {
        dd('getCards');
    }

    /**
     * Returns a specfic card.
     *
     * The same set of properties must be returned as with getCards. The only
     * exception is that 'carddata' is absolutely required.
     *
     * If the card does not exist, you must return false.
     *
     * @param mixed $addressBookId
     * @param string $cardUri
     * @return array
     */
    function getCard($addressBookId, $cardUri) {
        dd('getCard');
    }

    /**
     * Returns a list of cards.
     *
     * This method should work identical to getCard, but instead return all the
     * cards in the list as an array.
     *
     * If the backend supports this, it may allow for some speed-ups.
     *
     * @param mixed $addressBookId
     * @param array $uris
     * @return array
     */
    function getMultipleCards($addressBookId, array $uris) {
        dd('getMultipleCards');
    }

    /**
     * Creates a new card.
     *
     * The addressbook id will be passed as the first argument. This is the
     * same id as it is returned from the getAddressBooksForUser method.
     *
     * The cardUri is a base uri, and doesn't include the full path. The
     * cardData argument is the vcard body, and is passed as a string.
     *
     * It is possible to return an ETag from this method. This ETag is for the
     * newly created resource, and must be enclosed with double quotes (that
     * is, the string itself must contain the double quotes).
     *
     * You should only return the ETag if you store the carddata as-is. If a
     * subsequent GET request on the same card does not have the same body,
     * byte-by-byte and you did return an ETag here, clients tend to get
     * confused.
     *
     * If you don't return an ETag, you can just return null.
     *
     * @param mixed $addressBookId
     * @param string $cardUri
     * @param string $cardData
     * @return string|null
     */
    function createCard($addressBookId, $cardUri, $cardData) {
        dd('createCard');
    }

    /**
     * Updates a card.
     *
     * The addressbook id will be passed as the first argument. This is the
     * same id as it is returned from the getAddressBooksForUser method.
     *
     * The cardUri is a base uri, and doesn't include the full path. The
     * cardData argument is the vcard body, and is passed as a string.
     *
     * It is possible to return an ETag from this method. This ETag should
     * match that of the updated resource, and must be enclosed with double
     * quotes (that is: the string itself must contain the actual quotes).
     *
     * You should only return the ETag if you store the carddata as-is. If a
     * subsequent GET request on the same card does not have the same body,
     * byte-by-byte and you did return an ETag here, clients tend to get
     * confused.
     *
     * If you don't return an ETag, you can just return null.
     *
     * @param mixed $addressBookId
     * @param string $cardUri
     * @param string $cardData
     * @return string|null
     */
    function updateCard($addressBookId, $cardUri, $cardData) {
        dd('updateCard');
    }

    /**
     * Deletes a card
     *
     * @param mixed $addressBookId
     * @param string $cardUri
     * @return bool
     */
    function deleteCard($addressBookId, $cardUri) {
        dd('deleteCard');
    }

}

Route::match($verbs, 'carddav{path?}', function()
{
        date_default_timezone_set('Europe/Berlin');

        $baseUri = '/carddav';

        $authBackend = new MonicaSabreBackend();
        $principalBackend = new MonicaPrincipleBackend();
        $carddavBackend = new MonicaCardDAVBackend();

        $nodes = [
                new \Sabre\DAVACL\PrincipalCollection($principalBackend),
                new \Sabre\CardDAV\AddressBookRoot($principalBackend, $carddavBackend)
        ];

        $server = new \Sabre\DAV\Server($nodes);
        $server->setBaseUri($baseUri);

        $server->addPlugin(new \Sabre\DAV\Auth\Plugin($authBackend, 'SabreDAV'));
        $server->addPlugin(new \Sabre\DAV\Browser\Plugin());
        $server->addPlugin(new \Sabre\CardDAV\Plugin());
        $server->addPlugin(new \Sabre\DAVACL\Plugin());
        $server->addPlugin(new \Sabre\DAV\Sync\Plugin());

        ob_start();
        $server->exec();

        $status = $server->httpResponse->getStatus();
        $content = ob_get_contents();
        ob_end_clean();

        return response($content, $status);
})->where('path', '(.)*');
