<?php

if (App::environment('production')) {
    URL::forceScheme('https');
}

Route::get('/', 'Auth\LoginController@showLoginForm')->name('login');

Auth::routes();

Route::get('/password/reset', 'Auth\ForgotPasswordController@showLinkRequestForm');

Route::get('/invitations/accept/{key}', 'SettingsController@acceptInvitation');
Route::post('/invitations/accept/{key}', 'SettingsController@storeAcceptedInvitation');

Route::group(['middleware' => 'auth'], function () {

    //Route::resource('people', 'PeopleController');
    Route::get('/logout', 'Auth\LoginController@logout');

    Route::get('/dashboard/', ['as' => 'dashboard', 'uses' => 'DashboardController@index']);

    Route::group(['as' => 'people'], function () {
        Route::get('/people/', 'PeopleController@index')->name('.index');
        Route::get('/people/add', 'PeopleController@create')->name('.create');
        Route::post('/people/', 'PeopleController@store')->name('.store');

        // Dashboard
        Route::get('/people/{contact}', 'PeopleController@show')->name('.show');
        Route::get('/people/{contact}/edit', 'PeopleController@edit')->name('.edit');
        Route::post('/people/{contact}/update', 'PeopleController@update')->name('.update');
        Route::get('/people/{contact}/delete', 'PeopleController@delete')->name('.delete');

        // Work information
        Route::get('/people/{contact}/work/edit', ['as' => '.edit', 'uses' => 'PeopleController@editWork'])->name('.work.edit');
        Route::post('/people/{contact}/work/update', 'PeopleController@updateWork')->name('.work.update');

        // Notes
        Route::get('/people/{contact}/notes/add', 'People\\NotesController@create')->name('.notes.add');
        Route::post('/people/{contact}/notes/store', 'People\\NotesController@store')->name('.notes.store');
        Route::get('/people/{contact}/notes/{note}/edit', 'People\\NotesController@edit')->name('.notes.edit');
        Route::put('/people/{contact}/notes/{note}', 'People\\NotesController@update')->name('.notes.update');
        Route::get('/people/{contact}/notes/{note}/delete', 'People\\NotesController@destroy')->name('.notes.delete');

        // Food preferencies
        Route::get('/people/{contact}/food', 'PeopleController@editFoodPreferencies')->name('.food');
        Route::post('/people/{contact}/food/save', 'PeopleController@updateFoodPreferencies')->name('.food.update');

        // Kid
        Route::get('/people/{contact}/kids/add', 'People\\KidsController@create')->name('.kids.add');
        Route::post('/people/{contact}/kids/store', 'People\\KidsController@store')->name('.kids.store');
        Route::get('/people/{contact}/kids/{kid}/edit', 'People\\KidsController@edit')->name('.kids.edit');
        Route::put('/people/{contact}/kids/{kid}', 'People\\KidsController@update')->name('.kids.update');
        Route::get('/people/{contact}/kids/{kid}/delete', 'People\\KidsController@destroy')->name('.kids.delete');

        // Significant other
        Route::get('/people/{contact}/significant-others/add', 'People\\SignificantOthersController@create')->name('.significant_others.add');
        Route::post('/people/{contact}/significant-others/store', 'People\\SignificantOthersController@store')->name('.significant_others.store');
        Route::get('/people/{contact}/significant-others/{significant_other}/edit', 'People\\SignificantOthersController@edit')->name('.significant_others.edit');
        Route::put('/people/{contact}/significant-others/{significant_other}', 'People\\SignificantOthersController@update')->name('.significant_others.update');
        Route::get('/people/{contact}/significant-others/{significant_other}/delete', 'People\\SignificantOthersController@destroy')->name('.significant_others.delete');

        // Activities
        Route::get('/people/{contact}/activities/add', 'People\\ActivitiesController@create')->name('.activities.add');
        Route::post('/people/{contact}/activities/store', 'People\\ActivitiesController@store')->name('.activities.store');
        Route::get('/people/{contact}/activities/{activity}/edit', 'People\\ActivitiesController@edit')->name('.activities.edit');
        Route::put('/people/{contact}/activities/{activity}', 'People\\ActivitiesController@update')->name('.activities.update');
        Route::get('/people/{contact}/activities/{activity}/delete', 'People\\ActivitiesController@destroy')->name('.activities.delete');

        // Reminders
        Route::get('/people/{contact}/reminders/add', 'People\\RemindersController@create')->name('.reminders.add');
        Route::post('/people/{contact}/reminders/store', 'People\\RemindersController@store')->name('.reminders.store');
        Route::get('/people/{contact}/reminders/{reminder}/edit', 'People\\RemindersController@edit')->name('.reminders.edit');
        Route::put('/people/{contact}/reminders/{reminder}', 'People\\RemindersController@update')->name('.reminders.update');
        Route::get('/people/{contact}/reminders/{reminder}/delete', 'People\\RemindersController@destroy')->name('.reminders.delete');

        // Tasks
        Route::get('/people/{contact}/tasks/add', 'People\\TasksController@create')->name('.tasks.add');
        Route::post('/people/{contact}/tasks/store', 'People\\TasksController@store')->name('.tasks.store');
        Route::patch('/people/{contact}/tasks/{task}/toggle', 'People\\TasksController@toggle')->name('.tasks.toggle');
        Route::get('/people/{contact}/tasks/{task}/delete', 'People\\TasksController@destroy')->name('.tasks.delete');

        // Gifts
        Route::get('/people/{contact}/gifts/add', 'People\\GiftsController@create')->name('.gifts.add');
        Route::post('/people/{contact}/gifts/store', 'People\\GiftsController@store')->name('.gifts.store');
        Route::get('/people/{contact}/gifts/{gift}/delete', 'People\\GiftsController@destroy')->name('.gifts.delete');

        // Debt
        Route::get('/people/{contact}/debt/add', 'People\\DebtController@create')->name('.debt.add');
        Route::post('/people/{contact}/debt/store', 'People\\DebtController@store')->name('.debt.store');
        Route::get('/people/{contact}/debt/{debt}/edit', 'People\\DebtController@edit')->name('.debt.edit');
        Route::put('/people/{contact}/debt/{debt}', 'People\\DebtController@update')->name('.debt.update');
        Route::get('/people/{contact}/debt/{debt}/delete', 'People\\DebtController@destroy')->name('.debt.delete');
    });

    Route::group(['as' => 'journal'], function () {
        Route::get('/journal', ['as' => '.index', 'uses' => 'JournalController@index']);
        Route::get('/journal/add', ['as' => '.create', 'uses' => 'JournalController@add']);
        Route::post('/journal/create', ['as' => '.create', 'uses' => 'JournalController@save']);
        Route::get('/journal/{entryId}/delete', ['as' => '.delete', 'uses' => 'JournalController@deleteEntry']);
    });

    Route::group(['as' => 'settings'], function () {
        Route::get('/settings', ['as' => '.index', 'uses' => 'SettingsController@index']);
        Route::get('/settings/delete', ['as' => '.delete', 'uses' => 'SettingsController@delete']);
        Route::get('/settings/reset', ['as' => '.reset', 'uses' => 'SettingsController@reset']);
        Route::post('/settings/save', 'SettingsController@save');
        Route::get('/settings/export', 'SettingsController@export')->name('.export');
        Route::get('/settings/exportToSql', 'SettingsController@exportToSQL');

        Route::get('/settings/import', 'SettingsController@import')->name('.import');
        Route::get('/settings/import/report/{importjobid}', 'SettingsController@report')->name('.report');
        Route::get('/settings/import/upload', 'SettingsController@upload')->name('.upload');
        Route::post('/settings/import/storeImport', 'SettingsController@storeImport')->name('.storeImport');

        Route::get('/settings/users', 'SettingsController@users')->name('.users');
        Route::get('/settings/users/add', 'SettingsController@addUser')->name('.users.add');
        Route::get('/settings/users/{user}/delete', ['as' => '.users.delete', 'uses' => 'SettingsController@deleteAdditionalUser']);
        Route::post('/settings/users/save', 'SettingsController@inviteUser')->name('.users.save');
        Route::get('/settings/users/invitations/{invitation}/delete', 'SettingsController@destroyInvitation');

        Route::get('/settings/subscriptions', 'Settings\\SubscriptionsController@index')->name('.subscriptions.index');
        Route::get('/settings/subscriptions/upgrade', 'Settings\\SubscriptionsController@upgrade')->name('.subscriptions.upgrade');
        Route::post('/settings/subscriptions/processPayment', 'Settings\\SubscriptionsController@processPayment');
        Route::get('/settings/subscriptions/invoice/{invoice}', 'Settings\\SubscriptionsController@downloadInvoice');
        Route::get('/settings/subscriptions/downgrade', 'Settings\\SubscriptionsController@downgrade')->name('.subscriptions.downgrade');
        Route::post('/settings/subscriptions/downgrade', 'Settings\\SubscriptionsController@processDowngrade');

    });
});
