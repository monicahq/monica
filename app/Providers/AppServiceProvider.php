<?php

namespace App\Providers;

use App\Helpers\DBHelper;
use Laravel\Cashier\Cashier;
use Laravel\Passport\Passport;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\View;
use App\Notifications\EmailMessaging;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Auth\Notifications\VerifyEmail;
use Werk365\EtagConditionals\EtagConditionals;
use Illuminate\Auth\Notifications\ResetPassword;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        if (App::runningInConsole()) {
            Command::macro('exec', function (string $message, string $commandline) {
                // @codeCoverageIgnoreStart
                /** @var \Illuminate\Console\Command */
                $command = $this;
                \App\Console\Commands\Helpers\Command::exec($command, $message, $commandline);
                // @codeCoverageIgnoreEnd
            });
            Command::macro('artisan', function (string $message, string $commandline, array $arguments = []) {
                /** @var \Illuminate\Console\Command */
                $command = $this;
                \App\Console\Commands\Helpers\Command::artisan($command, $message, $commandline, $arguments);
            });
        }

        View::composer(
            'partials.components.currency-select', 'App\Http\ViewComposers\CurrencySelectViewComposer'
        );

        View::composer(
            'partials.components.date-select', 'App\Http\ViewComposers\DateSelectViewComposer'
        );

        View::composer(
            'partials.check', 'App\Http\ViewComposers\InstanceViewComposer'
        );

        Password::defaults(function () {
            if (! $this->app->environment('production')) {
                return Password::min(6);
            }
            $rules = Password::min(config('app.password_min'));
            $config = explode(',', config('app.password_rules'));
            if (in_array('mixedCase', $config)) {
                $rules = $rules->mixedCase();
            }
            if (in_array('letters', $config)) {
                $rules = $rules->letters();
            }
            if (in_array('numbers', $config)) {
                $rules = $rules->numbers();
            }
            if (in_array('symbols', $config)) {
                $rules = $rules->symbols();
            }
            if (in_array('uncompromised', $config)) {
                $rules = $rules->uncompromised();
            }

            return $rules;
        });

        if (config('database.use_utf8mb4')
            && DBHelper::connection()->getDriverName() == 'mysql'
            && ! DBHelper::testVersion('5.7.7')) {
            Schema::defaultStringLength(191);
        }

        Cashier::useCustomerModel(\App\Models\Account\Account::class);

        VerifyEmail::toMailUsing(function ($user, $verificationUrl) {
            return EmailMessaging::verifyEmailMail($user, $verificationUrl);
        });
        ResetPassword::toMailUsing(function ($user, $token) {
            return EmailMessaging::resetPasswordMail($user, $token);
        });

        Paginator::defaultView('vendor.pagination.default');

        RateLimiter::for('GPSCoordinate', function () {
            return [
                Limit::perMinute(60),
                Limit::perDay(5000),
            ];
        });

        EtagConditionals::etagGenerateUsing(function (\Illuminate\Http\Request $request, \Symfony\Component\HttpFoundation\Response $response) {
            $url = $request->getRequestUri();

            return Cache::rememberForever('etag.'.$url, function () use ($url) {
                return sha1($url);
            });
        });
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        Passport::ignoreMigrations();
        Cashier::ignoreMigrations();
        Cashier::formatCurrencyUsing(function ($amount, $currency) {
            $currency = \App\Models\Settings\Currency::where('iso', strtoupper($currency ?? config('cashier.currency')))->first();

            return \App\Helpers\MoneyHelper::format($amount, $currency);
        });
    }

    /**
     * All of the container singletons that should be registered.
     *
     * @var array
     */
    public $singletons = [
        \App\Services\Account\Activity\Activity\AttachContactToActivity::class => \App\Services\Account\Activity\Activity\AttachContactToActivity::class,
        \App\Services\Account\Activity\Activity\CreateActivity::class => \App\Services\Account\Activity\Activity\CreateActivity::class,
        \App\Services\Account\Activity\Activity\DestroyActivity::class => \App\Services\Account\Activity\Activity\DestroyActivity::class,
        \App\Services\Account\Activity\Activity\UpdateActivity::class => \App\Services\Account\Activity\Activity\UpdateActivity::class,
        \App\Services\Account\Activity\ActivityStatisticService::class => \App\Services\Account\Activity\ActivityStatisticService::class,
        \App\Services\Account\Activity\ActivityTypeCategory\CreateActivityTypeCategory::class => \App\Services\Account\Activity\ActivityTypeCategory\CreateActivityTypeCategory::class,
        \App\Services\Account\Activity\ActivityTypeCategory\DestroyActivityTypeCategory::class => \App\Services\Account\Activity\ActivityTypeCategory\DestroyActivityTypeCategory::class,
        \App\Services\Account\Activity\ActivityTypeCategory\UpdateActivityTypeCategory::class => \App\Services\Account\Activity\ActivityTypeCategory\UpdateActivityTypeCategory::class,
        \App\Services\Account\Activity\ActivityType\CreateActivityType::class => \App\Services\Account\Activity\ActivityType\CreateActivityType::class,
        \App\Services\Account\Activity\ActivityType\DestroyActivityType::class => \App\Services\Account\Activity\ActivityType\DestroyActivityType::class,
        \App\Services\Account\Activity\ActivityType\UpdateActivityType::class => \App\Services\Account\Activity\ActivityType\UpdateActivityType::class,
        \App\Services\Account\Company\CreateCompany::class => \App\Services\Account\Company\CreateCompany::class,
        \App\Services\Account\Company\DestroyCompany::class => \App\Services\Account\Company\DestroyCompany::class,
        \App\Services\Account\Company\UpdateCompany::class => \App\Services\Account\Company\UpdateCompany::class,
        \App\Services\Account\Settings\DestroyAllDocuments::class => \App\Services\Account\Settings\DestroyAllDocuments::class,
        \App\Services\Account\Gender\CreateGender::class => \App\Services\Account\Gender\CreateGender::class,
        \App\Services\Account\Gender\DestroyGender::class => \App\Services\Account\Gender\DestroyGender::class,
        \App\Services\Account\Gender\UpdateGender::class => \App\Services\Account\Gender\UpdateGender::class,
        \App\Services\Account\Photo\DestroyPhoto::class => \App\Services\Account\Photo\DestroyPhoto::class,
        \App\Services\Account\Photo\UploadPhoto::class => \App\Services\Account\Photo\UploadPhoto::class,
        \App\Services\Account\Place\CreatePlace::class => \App\Services\Account\Place\CreatePlace::class,
        \App\Services\Account\Place\DestroyPlace::class => \App\Services\Account\Place\DestroyPlace::class,
        \App\Services\Account\Place\UpdatePlace::class => \App\Services\Account\Place\UpdatePlace::class,
        \App\Services\User\CreateUser::class => \App\Services\User\CreateUser::class,
        \App\Services\Auth\Population\PopulateContactFieldTypesTable::class => \App\Services\Auth\Population\PopulateContactFieldTypesTable::class,
        \App\Services\Auth\Population\PopulateLifeEventsTable::class => \App\Services\Auth\Population\PopulateLifeEventsTable::class,
        \App\Services\Auth\Population\PopulateModulesTable::class => \App\Services\Auth\Population\PopulateModulesTable::class,
        \App\Services\Contact\Avatar\GenerateDefaultAvatar::class => \App\Services\Contact\Avatar\GenerateDefaultAvatar::class,
        \App\Services\Contact\Avatar\GetAdorableAvatarURL::class => \App\Services\Contact\Avatar\GetAdorableAvatarURL::class,
        \App\Services\Contact\Avatar\GetAvatarsFromInternet::class => \App\Services\Contact\Avatar\GetAvatarsFromInternet::class,
        \App\Services\Contact\Avatar\GetGravatar::class => \App\Services\Contact\Avatar\GetGravatar::class,
        \App\Services\Contact\Avatar\GetGravatarURL::class => \App\Services\Contact\Avatar\GetGravatarURL::class,
        \App\Services\Contact\Avatar\UpdateAvatar::class => \App\Services\Contact\Avatar\UpdateAvatar::class,
        \App\Services\Contact\Address\CreateAddress::class => \App\Services\Contact\Address\CreateAddress::class,
        \App\Services\Contact\Address\DestroyAddress::class => \App\Services\Contact\Address\DestroyAddress::class,
        \App\Services\Contact\Address\UpdateAddress::class => \App\Services\Contact\Address\UpdateAddress::class,
        \App\Services\Contact\Call\CreateCall::class => \App\Services\Contact\Call\CreateCall::class,
        \App\Services\Contact\Call\DestroyCall::class => \App\Services\Contact\Call\DestroyCall::class,
        \App\Services\Contact\Call\UpdateCall::class => \App\Services\Contact\Call\UpdateCall::class,
        \App\Services\Contact\Contact\CreateContact::class => \App\Services\Contact\Contact\CreateContact::class,
        \App\Services\Contact\Contact\DeleteMeContact::class => \App\Services\Contact\Contact\DeleteMeContact::class,
        \App\Services\Contact\Contact\DestroyContact::class => \App\Services\Contact\Contact\DestroyContact::class,
        \App\Services\Contact\Contact\SetMeContact::class => \App\Services\Contact\Contact\SetMeContact::class,
        \App\Services\Contact\Contact\UpdateBirthdayInformation::class => \App\Services\Contact\Contact\UpdateBirthdayInformation::class,
        \App\Services\Contact\Contact\UpdateContact::class => \App\Services\Contact\Contact\UpdateContact::class,
        \App\Services\Contact\Contact\UpdateContactFoodPreferences::class => \App\Services\Contact\Contact\UpdateContactFoodPreferences::class,
        \App\Services\Contact\Contact\UpdateContactIntroduction::class => \App\Services\Contact\Contact\UpdateContactIntroduction::class,
        \App\Services\Contact\Contact\UpdateWorkInformation::class => \App\Services\Contact\Contact\UpdateWorkInformation::class,
        \App\Services\Contact\Contact\UpdateDeceasedInformation::class => \App\Services\Contact\Contact\UpdateDeceasedInformation::class,
        \App\Services\Contact\Conversation\AddMessageToConversation::class => \App\Services\Contact\Conversation\AddMessageToConversation::class,
        \App\Services\Contact\Conversation\CreateConversation::class => \App\Services\Contact\Conversation\CreateConversation::class,
        \App\Services\Contact\Conversation\DestroyConversation::class => \App\Services\Contact\Conversation\DestroyConversation::class,
        \App\Services\Contact\Conversation\DestroyMessage::class => \App\Services\Contact\Conversation\DestroyMessage::class,
        \App\Services\Contact\Conversation\UpdateConversation::class => \App\Services\Contact\Conversation\UpdateConversation::class,
        \App\Services\Contact\Conversation\UpdateMessage::class => \App\Services\Contact\Conversation\UpdateMessage::class,
        \App\Services\Contact\Document\DestroyDocument::class => \App\Services\Contact\Document\DestroyDocument::class,
        \App\Services\Contact\Document\UploadDocument::class => \App\Services\Contact\Document\UploadDocument::class,
        \App\Services\Contact\Gift\AssociatePhotoToGift::class => \App\Services\Contact\Gift\AssociatePhotoToGift::class,
        \App\Services\Contact\Gift\CreateGift::class => \App\Services\Contact\Gift\CreateGift::class,
        \App\Services\Contact\Gift\DestroyGift::class => \App\Services\Contact\Gift\DestroyGift::class,
        \App\Services\Contact\Gift\UpdateGift::class => \App\Services\Contact\Gift\UpdateGift::class,
        \App\Services\Contact\Label\UpdateAddressLabels::class => \App\Services\Contact\Label\UpdateAddressLabels::class,
        \App\Services\Contact\Label\UpdateContactFieldLabels::class => \App\Services\Contact\Label\UpdateContactFieldLabels::class,
        \App\Services\Contact\LifeEvent\CreateLifeEvent::class => \App\Services\Contact\LifeEvent\CreateLifeEvent::class,
        \App\Services\Contact\LifeEvent\DestroyLifeEvent::class => \App\Services\Contact\LifeEvent\DestroyLifeEvent::class,
        \App\Services\Contact\LifeEvent\UpdateLifeEvent::class => \App\Services\Contact\LifeEvent\UpdateLifeEvent::class,
        \App\Services\Contact\Occupation\CreateOccupation::class => \App\Services\Contact\Occupation\CreateOccupation::class,
        \App\Services\Contact\Occupation\DestroyOccupation::class => \App\Services\Contact\Occupation\DestroyOccupation::class,
        \App\Services\Contact\Occupation\UpdateOccupation::class => \App\Services\Contact\Occupation\UpdateOccupation::class,
        \App\Services\Contact\Relationship\CreateRelationship::class => \App\Services\Contact\Relationship\CreateRelationship::class,
        \App\Services\Contact\Relationship\DestroyRelationship::class => \App\Services\Contact\Relationship\DestroyRelationship::class,
        \App\Services\Contact\Relationship\UpdateRelationship::class => \App\Services\Contact\Relationship\UpdateRelationship::class,
        \App\Services\Contact\Reminder\CreateReminder::class => \App\Services\Contact\Reminder\CreateReminder::class,
        \App\Services\Contact\Reminder\DestroyReminder::class => \App\Services\Contact\Reminder\DestroyReminder::class,
        \App\Services\Contact\Reminder\UpdateReminder::class => \App\Services\Contact\Reminder\UpdateReminder::class,
        \App\Services\Contact\Tag\AssociateTag::class => \App\Services\Contact\Tag\AssociateTag::class,
        \App\Services\Contact\Tag\CreateTag::class => \App\Services\Contact\Tag\CreateTag::class,
        \App\Services\Contact\Tag\DestroyTag::class => \App\Services\Contact\Tag\DestroyTag::class,
        \App\Services\Contact\Tag\DetachTag::class => \App\Services\Contact\Tag\DetachTag::class,
        \App\Services\Contact\Tag\UpdateTag::class => \App\Services\Contact\Tag\UpdateTag::class,
        \App\Services\Instance\IdHasher::class => \App\Services\Instance\IdHasher::class,
        \App\Services\Instance\Geolocalization\GetGPSCoordinate::class => \App\Services\Instance\Geolocalization\GetGPSCoordinate::class,
        \App\Services\Instance\Weather\GetWeatherInformation::class => \App\Services\Instance\Weather\GetWeatherInformation::class,
        \App\Services\Task\CreateTask::class => \App\Services\Task\CreateTask::class,
        \App\Services\Task\DestroyTask::class => \App\Services\Task\DestroyTask::class,
        \App\Services\Task\UpdateTask::class => \App\Services\Task\UpdateTask::class,
        \App\Services\User\EmailChange::class => \App\Services\User\EmailChange::class,
        \App\Services\VCalendar\ExportTask::class => \App\Services\VCalendar\ExportTask::class,
        \App\Services\VCalendar\ExportVCalendar::class => \App\Services\VCalendar\ExportVCalendar::class,
        \App\Services\VCalendar\ImportTask::class => \App\Services\VCalendar\ImportTask::class,
        \App\Services\VCard\ExportVCard::class => \App\Services\VCard\ExportVCard::class,
        \App\Services\VCard\ImportVCard::class => \App\Services\VCard\ImportVCard::class,
        \App\Services\Account\Settings\SqlExportAccount::class => \App\Services\Account\Settings\SqlExportAccount::class,
        \App\Services\Account\Settings\ResetAccount::class => \App\Services\Account\Settings\ResetAccount::class,
        \App\Services\Account\Settings\DestroyAccount::class => \App\Services\Account\Settings\DestroyAccount::class,
        \App\Services\Instance\AuditLog\LogAccountAction::class => \App\Services\Instance\AuditLog\LogAccountAction::class,
        \App\Services\User\UpdateViewPreference::class => \App\Services\User\UpdateViewPreference::class,
        \App\Services\User\AcceptPolicy::class => \App\Services\User\AcceptPolicy::class,
    ];
}
