<?php

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

        // People
        Route::get('/people/', ['as' => '.index', 'uses' => 'PeopleController@index']);
        Route::post('/people/', 'PeopleController@store');
        Route::get('/people/add', ['as' => '.create', 'uses' => 'PeopleController@create']);
        Route::get('/people/{people}', 'PeopleController@show')->name('.show');
        Route::get('/people/{people}/edit', 'PeopleController@edit')->name('.edit');
        Route::post('/people/{people}/update', 'PeopleController@update')->name('.update');
        Route::get('/people/{people}/delete', 'PeopleController@delete')->name('.delete');

        // Work information
        Route::get('/people/{people}/work/edit', ['as' => '.work.edit', 'uses' => 'PeopleController@editWork']);
        Route::post('/people/{people}/work/update', 'PeopleController@updateWork');

        // Notes
        Route::get('/people/{people}/note/add', 'PeopleController@addNote')->name('.notes.add');
        Route::post('/people/{people}/note/save', 'PeopleController@storeNote')->name('.notes.save');
        Route::post('/people/{people}/notes/store', 'PeopleController@storeNote')->name('.notes.store');
        Route::get('/people/{people}/notes/{note}/delete', 'PeopleController@deleteNote')->name('.notes.delete');

        // Food preferencies
        Route::get('/people/{people}/food', ['as' => '.food', 'uses' => 'PeopleController@editFoodPreferencies']);
        Route::post('/people/{people}/food/save', ['as' => '.food.save', 'uses' => 'PeopleController@updateFoodPreferencies']);

        // Kid
        Route::get('/people/{people}/kid/add', ['as' => '.dashboard.kid.add', 'uses' => 'PeopleController@addKid']);
        Route::post('/people/{people}/kid/store', ['as' => '.dashboard.kid.store', 'uses' => 'PeopleController@storeKid']);
        Route::get('/people/{people}/kid/{kid}/edit', ['as' => '.dashboard.kid.edit', 'uses' => 'PeopleController@editKid']);
        Route::post('/people/{people}/kid/{kid}/save', ['as' => '.dashboard.kid.save', 'uses' => 'PeopleController@updateKid']);
        Route::get('/people/{people}/kid/{kid}/delete', ['as' => '.dashboard.kid.delete', 'uses' => 'PeopleController@deleteKid']);

        // Significant other
        Route::get('/people/{people}/significantother/add', 'PeopleController@addSignificantOther')->name('.dashboard.significantother.add');
        Route::post('/people/{people}/significantother/store', 'PeopleController@storeSignificantOther')->name('.dashboard.significantother.store');
        Route::get('/people/{people}/significantother/{significantother}/edit', 'PeopleController@editSignificantOther')->name('.dashboard.significantother.edit');
        Route::post('/people/{people}/significantother/{significantother}/save', 'PeopleController@updateSignificantOther')->name('.dashboard.significantother.save');
        Route::get('/people/{people}/significantother/{significantother}/delete', 'PeopleController@deleteSignificantOther')->name('.dashboard.significantother.delete');

        // Activities
        Route::get('/people/{people}/activities/add', ['as' => '.activities.add', 'uses' => 'PeopleController@addActivity']);
        Route::post('/people/{people}/activities/store', ['as' => '.activities.store', 'uses' => 'PeopleController@storeActivity']);
        Route::get('/people/{people}/activities/{activity}/edit', ['as' => '.activities.edit', 'uses' => 'PeopleController@editActivity']);
        Route::post('/people/{people}/activities/{activityId}/save', ['as' => '.activities.save', 'uses' => 'PeopleController@updateActivity'] );
        Route::get('/people/{people}/activities/{activityId}/delete', ['as' => '.activities.delete', 'uses' => 'PeopleController@deleteActivity'] );

        // Reminders
        Route::get('/people/{people}/reminders/add', 'PeopleController@addReminder')->name('.reminders.add');
        Route::post('/people/{people}/reminders/store', 'PeopleController@storeReminder')->name('.reminders.store');
        Route::get('/people/{people}/reminders/{reminderId}/edit', ['as' => '.reminders.edit', 'uses' => 'PeopleController@editReminder']);
        Route::post('/people/{people}/reminders/{reminderId}/save', 'PeopleController@updateReminder')->name('.reminders.save');
        Route::get('/people/{people}/reminders/{reminderId}/delete', 'PeopleController@deleteReminder')->name('.reminders.delete');

        // Tasks
        Route::get('/people/{people}/tasks/add', 'PeopleController@addTask')->name('.tasks.add');
        Route::post('/people/{people}/tasks/store', 'PeopleController@storeTask')->name('.tasks.store');
        Route::get('/people/{people}/tasks/{taskId}/toggle', 'PeopleController@toggleTask')->name('.tasks.toggle');
        Route::get('/people/{people}/tasks/{taskId}/delete', 'PeopleController@deleteTask')->name('.tasks.delete');

        // Gifts
        Route::get('/people/{people}/gifts/add', 'PeopleController@addGift')->name('.gifts.add');
        Route::post('/people/{people}/gifts/store', 'PeopleController@storeGift')->name('.gifts.store');
        Route::get('/people/{people}/gifts/{giftId}/delete', 'PeopleController@deleteGift')->name('.gifts.delete');

        // Debt
        Route::get('/people/{people}/debt/add', 'PeopleController@addDebt')->name('.debt.add');
        Route::post('/people/{people}/debt/store', 'PeopleController@storeDebt')->name('.debt.store');
        Route::get('/people/{people}/debt/{debtId}/delete', 'PeopleController@deleteDebt')->name('.debt.delete');
    });

    Route::group(['as' => 'journal'], function () {
        Route::get('/journal', ['as' => '.index', 'uses' => 'JournalController@index']);
        Route::get('/journal/add', ['as' => '.create', 'uses' => 'JournalController@add']);
        Route::post('/journal/create', ['as' => '.store', 'uses' => 'JournalController@save']);
        Route::get('/journal/{entryId}/delete', ['as' => '.delete', 'uses' => 'JournalController@deleteEntry']);
    });

    Route::group(['as' => 'settings'], function () {
        Route::get('/settings', 'SettingsController@index')->name('.index');
        Route::get('/settings/delete', 'SettingsController@delete')->name('.delete');
        Route::post('/settings/save', 'SettingsController@save')->name('.save');
        Route::get('/settings/export', 'SettingsController@export')->name('.export');
        Route::get('/settings/exportToSql', 'SettingsController@exportToSQL')->name('.exportToSql');
    });
});
