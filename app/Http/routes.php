<?php

Route::get('test/{contact}/{activity}','People\ActivitiesController@edit');

if (App::environment('production')) {
    URL::forceScheme('https');
}

Route::get('/', 'Auth\LoginController@showLoginForm')->name('login');

Auth::routes();

Route::get('/password/reset', 'Auth\ForgotPasswordController@showLinkRequestForm');

Route::group(['middleware' => 'auth'], function () {

    //Route::resource('people', 'PeopleController');
    Route::get('/logout', 'Auth\LoginController@logout');

    Route::get('/dashboard/', ['as' => 'dashboard', 'uses' => 'DashboardController@index']);

    Route::group(['as' => 'people'], function () {
        Route::get('/people/', ['as' => '.index', 'uses' => 'PeopleController@index']);
        Route::get('/people/add', ['as' => '.create', 'uses' => 'PeopleController@create']);
        Route::post('/people/', 'PeopleController@store');

        // Dashboard
        Route::get('/people/{people}', ['as' => '.show', 'uses' => 'PeopleController@show']);
        Route::get('/people/{people}/edit', ['as' => '.edit', 'uses' => 'PeopleController@edit']);
        Route::post('/people/{people}/update', 'PeopleController@update');
        Route::get('/people/{people}/delete', ['as' => '.delete', 'uses' => 'PeopleController@delete']);

        // Work information
        Route::get('/people/{people}/work/edit', ['as' => '.edit', 'uses' => 'PeopleController@editWork']);
        Route::post('/people/{people}/work/update', 'PeopleController@updateWork');

        // Notes
        Route::get('/people/{people}/note/add', 'PeopleController@addNote');
        Route::get('/people/{people}/note/{noteId}/edit', ['as' => '.note.edit', 'uses' => 'PeopleController@editNote']);
        Route::post('/people/{people}/note/{noteId}/update', ['as' => '.note.update', 'uses' => 'PeopleController@updateNote']);
        Route::post('/people/{people}/note/save', 'PeopleController@storeNote');
        Route::post('/people/{people}/notes/store', 'PeopleController@storeNote');
        Route::get('/people/{people}/notes/{note}/delete', 'PeopleController@deleteNote');

        // Food preferencies
        Route::get('/people/{people}/food', ['as' => '.food', 'uses' => 'PeopleController@editFoodPreferencies']);
        Route::post('/people/{people}/food/save', 'PeopleController@updateFoodPreferencies');

        // Kid
        Route::get('/people/{people}/kid/add', ['as' => '.dashboard.kid.add', 'uses' => 'PeopleController@addKid']);
        Route::post('/people/{people}/kid/store', 'PeopleController@storeKid');
        Route::get('/people/{people}/kid/{kid}/edit', ['as' => '.dashboard.kid.edit', 'uses' => 'PeopleController@editKid']);
        Route::post('/people/{people}/kid/{kid}/save', 'PeopleController@updateKid');
        Route::get('/people/{people}/kid/{kid}/delete', 'PeopleController@deleteKid');

        // Significant other
        Route::get('/people/{people}/significantother/add', ['as' => '.dashboard.significantother.add', 'uses' => 'PeopleController@addSignificantOther']);
        Route::post('/people/{people}/significantother/store', 'PeopleController@storeSignificantOther');
        Route::get('/people/{people}/significantother/{significantother}/edit', ['as' => '.dashboard.significantother.edit', 'uses' => 'PeopleController@editSignificantOther']);
        Route::post('/people/{people}/significantother/{significantother}/save', 'PeopleController@updateSignificantOther');
        Route::get('/people/{people}/significantother/{significantother}/delete', 'PeopleController@deleteSignificantOther');


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
        Route::get('/people/{people}/debt/add', ['as' => '.debt.add', 'uses' => 'PeopleController@addDebt']);
        Route::get('/people/{people}/debt/{debtId}/edit', ['as' => '.debt.edit', 'uses' => 'PeopleController@editDebt']);
        Route::post('/people/{people}/debt/{debtId}/update', ['as' => '.debt.update', 'uses' => 'PeopleController@updateDebt']);
        Route::post('/people/{people}/debt/store', 'PeopleController@storeDebt');
        Route::get('/people/{people}/debt/{debtId}/delete', 'PeopleController@deleteDebt');
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
        Route::post('/settings/save', 'SettingsController@save');
        Route::get('/settings/export', 'SettingsController@export');
        Route::get('/settings/exportToSql', 'SettingsController@exportToSQL');
    });
});
