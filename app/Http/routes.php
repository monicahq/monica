<?php

Route::get('/', 'MarketingController@index');

Auth::routes();

Route::get('/password/reset', 'Auth\ForgotPasswordController@showLinkRequestForm');

Route::get('/changelog', 'MarketingController@release');
Route::get('/privacy', 'MarketingController@privacy');
Route::get('/statistics', 'MarketingController@statistics');

Route::get('auth/facebook', 'Auth\RegisterController@redirectToProvider');
Route::get('auth/facebook/callback', 'Auth\RegisterController@handleProviderCallback');

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

        // Notes
        Route::get('/people/{people}/note/add', 'PeopleController@addNote');
        Route::post('/people/{people}/note/save', 'PeopleController@storeNote');

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

        Route::post('/people/{people}/notes/store', 'PeopleController@storeNote');
        Route::get('/people/{people}/notes/{note}/delete', 'PeopleController@deleteNote');

        // Activities
        Route::get('/people/{people}/activities/add', ['as' => '.activities.add', 'uses' => 'PeopleController@addActivity']);
        Route::post('/people/{people}/activities/store', 'PeopleController@storeActivity');
        Route::get('/people/{people}/activities/{activity}/edit', ['as' => '.activities.edit', 'uses' => 'PeopleController@editActivity']);
        Route::post('/people/{people}/activities/{activityId}/save', 'PeopleController@updateActivity');
        Route::get('/people/{people}/activities/{activityId}/delete', 'PeopleController@deleteActivity');

        // Reminders
        Route::get('/people/{people}/reminders/add', ['as' => '.reminders.add', 'uses' => 'PeopleController@addReminder']);
        Route::post('/people/{people}/reminders/store', 'PeopleController@storeReminder');
        Route::get('/people/{people}/reminders/{reminderId}/edit', ['as' => '.reminders.edit', 'uses' => 'PeopleController@editReminder']);
        Route::post('/people/{people}/reminders/{reminderId}/save', 'PeopleController@updateReminder');
        Route::get('/people/{people}/reminders/{reminderId}/delete', 'PeopleController@deleteReminder');

        // Tasks
        Route::get('/people/{people}/tasks/add', ['as' => '.tasks.add', 'uses' => 'PeopleController@addTask']);
        Route::post('/people/{people}/tasks/store', 'PeopleController@storeTask');
        Route::get('/people/{people}/tasks/{taskId}/toggle', 'PeopleController@toggleTask');
        Route::get('/people/{people}/tasks/{taskId}/delete', 'PeopleController@deleteTask');

        // Gifts
        Route::get('/people/{people}/gifts/add', ['as' => '.gifts.add', 'uses' => 'PeopleController@addGift']);
        Route::post('/people/{people}/gifts/store', 'PeopleController@storeGift');
        Route::get('/people/{people}/gifts/{giftId}/delete', 'PeopleController@deleteGift');
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
    });
});
