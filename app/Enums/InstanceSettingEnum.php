<?php

declare(strict_types=1);

namespace App\Enums;

use App\Contracts\Translatable;
use Override;

enum InstanceSettingEnum: string implements Translatable
{
    case SelfHosted = 'self_hosted';

    case FreeStorageLimitInBytes = 'free_storage_limit_in_bytes';
    case FreeVaultLimit = 'free_vault_limit';
    case FreeContactLimit = 'free_contact_limit';
    case PaidStorageLimitInBytes = 'paid_storage_limit_in_bytes';
    case MaxFileSizeInKilobytes = 'max_file_size_in_kilobytes';

    case RememberDurationDays = 'remember_duration_days';
    case MagicLinkEnabled = 'magic_link_enabled';
    case MagicLinkDurationMinutes = 'magic_link_duration_minutes';
    case RegistrationEnabled = 'registration_enabled';
    case PreviewListEnabled = 'preview_list_enabled';

    case ReminderGenerationWindowInDays = 'reminder_generation_window_in_days';
    case NotificationMaximumAttempts = 'notification_maximum_attempts';
    case NotificationRetentionMonths = 'notification_retention_months';
    case NotificationChannelFailureThreshold = 'notification_channel_failure_threshold';

    case OrphanedFileGracePeriodInDays = 'orphaned_file_grace_period_in_days';

    case ApiDefaultItemsPerPage = 'api_default_items_per_page';
    case ApiMaximumItemsPerPage = 'api_maximum_items_per_page';

    case ChangelogEnabled = 'changelog_enabled';

    case GithubClientId = 'github_client_id';
    case GithubClientSecret = 'github_client_secret';
    case GoogleClientId = 'google_client_id';
    case GoogleClientSecret = 'google_client_secret';
    case AppleClientId = 'apple_client_id';
    case AppleTeamId = 'apple_team_id';
    case AppleKeyId = 'apple_key_id';
    case ApplePrivateKey = 'apple_private_key';
    case AppleClientSecret = 'apple_client_secret';
    case EntraClientId = 'entra_client_id';
    case EntraClientSecret = 'entra_client_secret';
    case FacebookClientId = 'facebook_client_id';
    case FacebookClientSecret = 'facebook_client_secret';
    case EntraTenant = 'entra_tenant';
    case KeycloakServerUrl = 'keycloak_server_url';
    case KeycloakRealm = 'keycloak_realm';
    case KeycloakClientId = 'keycloak_client_id';
    case KeycloakClientSecret = 'keycloak_client_secret';
    case Auth0Domain = 'auth0_domain';
    case Auth0ClientId = 'auth0_client_id';
    case Auth0ClientSecret = 'auth0_client_secret';
    case OktaDomain = 'okta_domain';
    case OktaAuthServer = 'okta_auth_server';
    case OktaClientId = 'okta_client_id';
    case OktaClientSecret = 'okta_client_secret';
    case OpenIdConnectBaseUrl = 'openid_connect_base_url';
    case OpenIdConnectName = 'openid_connect_name';
    case OpenIdConnectClientId = 'openid_connect_client_id';
    case OpenIdConnectClientSecret = 'openid_connect_client_secret';

    case MailMailer = 'mail_mailer';
    case MailScheme = 'mail_scheme';
    case MailHost = 'mail_host';
    case MailPort = 'mail_port';
    case MailUsername = 'mail_username';
    case MailPassword = 'mail_password';
    case MailFromAddress = 'mail_from_address';
    case MailFromName = 'mail_from_name';
    case ResendApiKey = 'resend_api_key';
    case ResendWebhookSecret = 'resend_webhook_secret';
    case VonageKey = 'vonage_key';
    case VonageSecret = 'vonage_secret';
    case VonageSmsFrom = 'vonage_sms_from';
    case TelegramBotToken = 'telegram_bot_token';
    case TelegramBotUrl = 'telegram_bot_url';
    case TelegramBotWebhookSecret = 'telegram_bot_webhook_secret';
    case CashierCurrency = 'cashier_currency';

    /**
     * The configuration key this setting overrides. Every reader in the
     * application keeps asking configuration, so this is the only place that
     * knows a setting and a key are the same thing.
     */
    public function configKey(): string
    {
        return match ($this) {
            self::GithubClientId => 'services.github.client_id',
            self::GithubClientSecret => 'services.github.client_secret',
            self::GoogleClientId => 'services.google.client_id',
            self::GoogleClientSecret => 'services.google.client_secret',
            self::AppleClientId => 'services.apple.client_id',
            self::AppleTeamId => 'services.apple.team_id',
            self::AppleKeyId => 'services.apple.key_id',
            self::ApplePrivateKey => 'services.apple.private_key',
            self::AppleClientSecret => 'services.apple.client_secret',
            self::EntraClientId => 'services.entra.client_id',
            self::EntraClientSecret => 'services.entra.client_secret',
            self::FacebookClientId => 'services.facebook.client_id',
            self::FacebookClientSecret => 'services.facebook.client_secret',
            self::EntraTenant => 'services.entra.tenant',
            self::KeycloakServerUrl => 'services.keycloak.server_url',
            self::KeycloakRealm => 'services.keycloak.realm',
            self::KeycloakClientId => 'services.keycloak.client_id',
            self::KeycloakClientSecret => 'services.keycloak.client_secret',
            self::Auth0Domain => 'services.auth0.domain',
            self::Auth0ClientId => 'services.auth0.client_id',
            self::Auth0ClientSecret => 'services.auth0.client_secret',
            self::OktaDomain => 'services.okta.domain',
            self::OktaAuthServer => 'services.okta.auth_server',
            self::OktaClientId => 'services.okta.client_id',
            self::OktaClientSecret => 'services.okta.client_secret',
            self::OpenIdConnectBaseUrl => 'services.openidconnect.base_url',
            self::OpenIdConnectName => 'services.openidconnect.name',
            self::OpenIdConnectClientId => 'services.openidconnect.client_id',
            self::OpenIdConnectClientSecret => 'services.openidconnect.client_secret',
            self::MailMailer => 'mail.default',
            self::MailScheme => 'mail.mailers.smtp.scheme',
            self::MailHost => 'mail.mailers.smtp.host',
            self::MailPort => 'mail.mailers.smtp.port',
            self::MailUsername => 'mail.mailers.smtp.username',
            self::MailPassword => 'mail.mailers.smtp.password',
            self::MailFromAddress => 'mail.from.address',
            self::MailFromName => 'mail.from.name',
            self::ResendApiKey => 'services.resend.key',
            self::ResendWebhookSecret => 'resend.webhook.secret',
            self::VonageKey => 'vonage.api_key',
            self::VonageSecret => 'vonage.api_secret',
            self::VonageSmsFrom => 'vonage.sms_from',
            self::TelegramBotToken => 'services.telegram.token',
            self::TelegramBotUrl => 'services.telegram.bot_url',
            self::TelegramBotWebhookSecret => 'services.telegram.webhook_secret',
            self::CashierCurrency => 'cashier.currency',
            default => 'monica.'.$this->value,
        };
    }

    /**
     * The environment variable this setting was configured with before this
     * screen existed, and which still supplies its value until somebody changes
     * it here.
     *
     * It is shown beside every setting so an operator can move between the two
     * without reading the configuration files, and so a deployment written in a
     * compose file can be matched to what the screen says.
     */
    public function environmentVariable(): string
    {
        return match ($this) {
            self::GithubClientId => 'GITHUB_CLIENT_ID',
            self::GithubClientSecret => 'GITHUB_CLIENT_SECRET',
            self::GoogleClientId => 'GOOGLE_CLIENT_ID',
            self::GoogleClientSecret => 'GOOGLE_CLIENT_SECRET',
            self::AppleClientId => 'APPLE_CLIENT_ID',
            self::AppleTeamId => 'APPLE_TEAM_ID',
            self::AppleKeyId => 'APPLE_KEY_ID',
            self::ApplePrivateKey => 'APPLE_PRIVATE_KEY',
            self::AppleClientSecret => 'APPLE_CLIENT_SECRET',
            self::EntraClientId => 'ENTRA_CLIENT_ID',
            self::EntraClientSecret => 'ENTRA_CLIENT_SECRET',
            self::FacebookClientId => 'FACEBOOK_CLIENT_ID',
            self::FacebookClientSecret => 'FACEBOOK_CLIENT_SECRET',
            self::EntraTenant => 'ENTRA_TENANT',
            self::KeycloakServerUrl => 'KEYCLOAK_SERVER_URL',
            self::KeycloakRealm => 'KEYCLOAK_REALM',
            self::KeycloakClientId => 'KEYCLOAK_CLIENT_ID',
            self::KeycloakClientSecret => 'KEYCLOAK_CLIENT_SECRET',
            self::Auth0Domain => 'AUTH0_DOMAIN',
            self::Auth0ClientId => 'AUTH0_CLIENT_ID',
            self::Auth0ClientSecret => 'AUTH0_CLIENT_SECRET',
            self::OktaDomain => 'OKTA_DOMAIN',
            self::OktaAuthServer => 'OKTA_AUTH_SERVER',
            self::OktaClientId => 'OKTA_CLIENT_ID',
            self::OktaClientSecret => 'OKTA_CLIENT_SECRET',
            self::OpenIdConnectBaseUrl => 'OIDC_BASE_URL',
            self::OpenIdConnectName => 'OIDC_NAME',
            self::OpenIdConnectClientId => 'OIDC_CLIENT_ID',
            self::OpenIdConnectClientSecret => 'OIDC_CLIENT_SECRET',
            self::MailMailer => 'MAIL_MAILER',
            self::MailScheme => 'MAIL_SCHEME',
            self::MailHost => 'MAIL_HOST',
            self::MailPort => 'MAIL_PORT',
            self::MailUsername => 'MAIL_USERNAME',
            self::MailPassword => 'MAIL_PASSWORD',
            self::MailFromAddress => 'MAIL_FROM_ADDRESS',
            self::MailFromName => 'MAIL_FROM_NAME',
            self::ResendApiKey => 'RESEND_API_KEY',
            self::ResendWebhookSecret => 'RESEND_WEBHOOK_SECRET',
            self::VonageKey => 'VONAGE_KEY',
            self::VonageSecret => 'VONAGE_SECRET',
            self::VonageSmsFrom => 'VONAGE_SMS_FROM',
            self::TelegramBotToken => 'TELEGRAM_BOT_TOKEN',
            self::TelegramBotUrl => 'TELEGRAM_BOT_URL',
            self::TelegramBotWebhookSecret => 'TELEGRAM_BOT_WEBHOOK_SECRET',
            self::CashierCurrency => 'CASHIER_CURRENCY',
            default => 'MONICA_'.strtoupper($this->value),
        };
    }

    public function group(): InstanceSettingGroupEnum
    {
        return match ($this) {
            self::SelfHosted => InstanceSettingGroupEnum::Instance,

            self::FreeStorageLimitInBytes, self::FreeVaultLimit, self::FreeContactLimit,
            self::PaidStorageLimitInBytes, self::MaxFileSizeInKilobytes => InstanceSettingGroupEnum::Accounts,

            self::RememberDurationDays, self::MagicLinkEnabled,
            self::MagicLinkDurationMinutes, self::RegistrationEnabled,
            self::PreviewListEnabled => InstanceSettingGroupEnum::SigningIn,

            self::GithubClientId, self::GithubClientSecret, self::GoogleClientId,
            self::GoogleClientSecret, self::AppleClientId, self::AppleTeamId, self::AppleKeyId,
            self::ApplePrivateKey, self::AppleClientSecret, self::EntraClientId, self::EntraClientSecret,
            self::FacebookClientId, self::FacebookClientSecret, self::EntraTenant,
            self::KeycloakServerUrl, self::KeycloakRealm, self::KeycloakClientId, self::KeycloakClientSecret,
            self::Auth0Domain, self::Auth0ClientId, self::Auth0ClientSecret,
            self::OktaDomain, self::OktaAuthServer, self::OktaClientId, self::OktaClientSecret,
            self::OpenIdConnectBaseUrl, self::OpenIdConnectName, self::OpenIdConnectClientId,
            self::OpenIdConnectClientSecret => InstanceSettingGroupEnum::SingleSignOn,

            self::ReminderGenerationWindowInDays, self::NotificationMaximumAttempts,
            self::NotificationRetentionMonths,
            self::NotificationChannelFailureThreshold => InstanceSettingGroupEnum::RemindersAndNotifications,

            self::OrphanedFileGracePeriodInDays => InstanceSettingGroupEnum::Files,

            self::ApiDefaultItemsPerPage, self::ApiMaximumItemsPerPage => InstanceSettingGroupEnum::Api,

            self::ChangelogEnabled => InstanceSettingGroupEnum::WhatsNew,

            default => InstanceSettingGroupEnum::OutboundServices,
        };
    }

    public function kind(): InstanceSettingKindEnum
    {
        return match ($this) {
            self::MagicLinkEnabled, self::RegistrationEnabled, self::PreviewListEnabled, self::SelfHosted,
            self::ChangelogEnabled => InstanceSettingKindEnum::Boolean,

            self::TelegramBotUrl, self::KeycloakServerUrl,
            self::OpenIdConnectBaseUrl => InstanceSettingKindEnum::Url,

            self::MailPassword, self::ResendApiKey, self::ResendWebhookSecret, self::VonageSecret,
            self::TelegramBotToken, self::TelegramBotWebhookSecret, self::GithubClientSecret,
            self::GoogleClientSecret, self::ApplePrivateKey, self::AppleClientSecret, self::EntraClientSecret,
            self::FacebookClientSecret, self::KeycloakClientSecret, self::Auth0ClientSecret,
            self::OktaClientSecret, self::OpenIdConnectClientSecret => InstanceSettingKindEnum::Secret,

            self::MailMailer, self::MailScheme, self::MailHost, self::MailUsername,
            self::MailFromAddress, self::MailFromName, self::VonageKey, self::VonageSmsFrom,
            self::CashierCurrency, self::GithubClientId, self::GoogleClientId,
            self::AppleClientId, self::AppleTeamId, self::AppleKeyId,
            self::EntraClientId, self::FacebookClientId, self::EntraTenant,
            self::KeycloakRealm, self::KeycloakClientId, self::Auth0Domain, self::Auth0ClientId,
            self::OktaDomain, self::OktaAuthServer, self::OktaClientId, self::OpenIdConnectName,
            self::OpenIdConnectClientId => InstanceSettingKindEnum::Text,

            default => InstanceSettingKindEnum::Integer,
        };
    }

    /**
     * Whether the deployment settles this one, which makes it read only. These
     * three describe the deployment rather than govern it: changing them from a
     * screen would make the instance lie about what it is.
     */
    public function fixedAtDeployment(): bool
    {
        return $this->group() === InstanceSettingGroupEnum::Instance;
    }

    /**
     * Whether this setting's provider is installed on this server.
     *
     * Read through configuration rather than asked of the autoloader here, so a
     * test can reach either state. The two notification channels answer with a
     * flag of their own; a sign in provider answers by being in the list of
     * drivers this installation carries, which is how the sign in screen already
     * decides what to offer.
     */
    public function providerIsInstalled(): bool
    {
        return match ($this) {
            self::VonageKey, self::VonageSecret,
            self::VonageSmsFrom => config('services.vonage.installed') === true,

            self::TelegramBotToken, self::TelegramBotUrl,
            self::TelegramBotWebhookSecret => config('services.telegram.installed') === true,

            self::GithubClientId, self::GithubClientSecret => self::signInProviderIsInstalled('github'),
            self::GoogleClientId, self::GoogleClientSecret => self::signInProviderIsInstalled('google'),
            self::AppleClientId, self::AppleTeamId, self::AppleKeyId,
            self::ApplePrivateKey, self::AppleClientSecret => self::signInProviderIsInstalled('apple'),
            self::EntraClientId, self::EntraClientSecret,
            self::EntraTenant => self::signInProviderIsInstalled('entra'),
            self::KeycloakServerUrl, self::KeycloakRealm, self::KeycloakClientId,
            self::KeycloakClientSecret => self::signInProviderIsInstalled('keycloak'),
            self::Auth0Domain, self::Auth0ClientId,
            self::Auth0ClientSecret => self::signInProviderIsInstalled('auth0'),
            self::OktaDomain, self::OktaAuthServer, self::OktaClientId,
            self::OktaClientSecret => self::signInProviderIsInstalled('okta'),
            self::FacebookClientId, self::FacebookClientSecret => self::signInProviderIsInstalled('facebook'),
            self::OpenIdConnectBaseUrl, self::OpenIdConnectName, self::OpenIdConnectClientId,
            self::OpenIdConnectClientSecret => self::signInProviderIsInstalled('openidconnect'),

            default => true,
        };
    }

    private static function signInProviderIsInstalled(string $provider): bool
    {
        return in_array($provider, config('monica.sso_installed', []), true);
    }

    /**
     * What a change to this setting can take away, or null when it can take
     * nothing away. Principle V asks for a warning before a destructive action,
     * and these are destructive in that sense: switching off a capability
     * strands what it already issued, shortening a retention makes data
     * eligible for removal that was not before, and replacing a Telegram
     * credential leaves the registered bot unable to reach the instance.
     */
    public function warning(): ?string
    {
        return match ($this) {
            self::MagicLinkEnabled => 'Any sign in link already emailed stops working, and whoever holds one has to ask for another way in.',
            self::RegistrationEnabled => 'Anybody who reaches this instance can create an account until it is switched off again.',
            self::NotificationRetentionMonths => 'Notifications older than the new number are swept on the next run, and nothing brings them back.',
            self::OrphanedFileGracePeriodInDays => 'Files detached longer ago than the new number become eligible for the sweep, and a restored record can no longer recover them.',
            self::TelegramBotToken, self::TelegramBotWebhookSecret => 'Telegram stops reaching this instance until the bot is registered again.',
            default => null,
        };
    }

    /**
     * Which direction of change is the destructive one, so a screen asks only
     * when it is going that way.
     */
    public function warnsWhen(): ?string
    {
        return match ($this) {
            self::MagicLinkEnabled => 'switchedOff',
            self::RegistrationEnabled => 'switchedOn',
            self::NotificationRetentionMonths, self::OrphanedFileGracePeriodInDays => 'lowered',
            self::TelegramBotToken, self::TelegramBotWebhookSecret => 'replaced',
            default => null,
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::FreeStorageLimitInBytes => 'How many bytes of files an account gets before the one time purchase.',
            self::FreeVaultLimit => 'How many vaults an account gets on the free plan.',
            self::FreeContactLimit => 'How many contacts an account gets on the free plan.',
            self::PaidStorageLimitInBytes => 'How many bytes of files an account gets once it has paid.',
            self::MaxFileSizeInKilobytes => 'How many kilobytes one upload may weigh.',
            self::RememberDurationDays => 'How long a session survives when somebody asks to be remembered.',
            self::MagicLinkEnabled => 'Whether somebody may sign in with a link emailed to them.',
            self::MagicLinkDurationMinutes => 'How long a sign in link stays usable.',
            self::RegistrationEnabled => 'Whether somebody may create an account on this instance.',
            self::PreviewListEnabled => 'Whether a visitor may leave an email address to wait for an account while registration is closed.',
            self::ReminderGenerationWindowInDays => 'How far ahead reminder deadlines are generated.',
            self::NotificationMaximumAttempts => 'How many times a refused notification is attempted again.',
            self::NotificationRetentionMonths => 'How long a notification is kept before it is swept.',
            self::NotificationChannelFailureThreshold => 'How many failures deactivate a channel.',
            self::OrphanedFileGracePeriodInDays => 'How long a file nothing points at is kept before the sweep takes it.',
            self::ApiDefaultItemsPerPage => 'How many things one page of an API list holds when nothing asks.',
            self::ApiMaximumItemsPerPage => 'The largest page an API caller may ask for.',
            self::ChangelogEnabled => 'Whether people on this instance see What’s new, the list of changes made to Monica, and may vote and comment on them. Votes and comments are kept while it is off.',
            self::GithubClientId => 'The identifier GitHub knows this instance by.',
            self::GithubClientSecret => 'The secret this instance proves itself to GitHub with.',
            self::GoogleClientId => 'The identifier Google knows this instance by.',
            self::GoogleClientSecret => 'The secret this instance proves itself to Google with.',
            self::AppleClientId => 'The Services ID Apple knows this instance by.',
            self::AppleTeamId => 'The identifier of the Apple developer team that owns the Services ID.',
            self::AppleKeyId => 'The identifier of the key this instance signs its requests to Apple with.',
            self::ApplePrivateKey => 'The key this instance signs its requests to Apple with, as the text of its file or the path to it.',
            self::AppleClientSecret => 'A secret generated in advance, used only when no key is supplied. Apple accepts one for six months at most.',
            self::EntraClientId => 'The identifier Microsoft knows this instance by.',
            self::EntraClientSecret => 'The secret this instance proves itself to Microsoft with.',
            self::FacebookClientId => 'The identifier Facebook knows this instance by.',
            self::FacebookClientSecret => 'The secret this instance proves itself to Facebook with.',
            self::EntraTenant => 'The Microsoft tenant whose accounts may sign in.',
            self::KeycloakServerUrl => 'The address of the Keycloak server.',
            self::KeycloakRealm => 'The Keycloak realm people sign in to.',
            self::KeycloakClientId => 'The identifier Keycloak knows this instance by.',
            self::KeycloakClientSecret => 'The secret this instance proves itself to Keycloak with.',
            self::Auth0Domain => 'The domain of the Auth0 tenant.',
            self::Auth0ClientId => 'The identifier Auth0 knows this instance by.',
            self::Auth0ClientSecret => 'The secret this instance proves itself to Auth0 with.',
            self::OktaDomain => 'The domain of the Okta organisation.',
            self::OktaAuthServer => 'The Okta authorization server, when it is not the default one.',
            self::OktaClientId => 'The identifier Okta knows this instance by.',
            self::OktaClientSecret => 'The secret this instance proves itself to Okta with.',
            self::OpenIdConnectBaseUrl => 'The address of the OpenID Connect provider people sign in through.',
            self::OpenIdConnectName => 'The name the OpenID Connect provider is offered under.',
            self::OpenIdConnectClientId => 'The identifier the OpenID Connect provider knows this instance by.',
            self::OpenIdConnectClientSecret => 'The secret this instance proves itself to the OpenID Connect provider with.',
            self::MailMailer => 'Which transport carries the email this instance sends.',
            self::MailScheme => 'Which scheme the mail transport connects with, such as smtps.',
            self::MailHost => 'The host the mail transport connects to.',
            self::MailPort => 'The port the mail transport connects on.',
            self::MailUsername => 'The name the mail transport signs in with.',
            self::MailPassword => 'The password the mail transport signs in with.',
            self::MailFromAddress => 'The address email from this instance is sent from.',
            self::MailFromName => 'The name email from this instance is sent from.',
            self::ResendApiKey => 'The key the Resend transport sends with.',
            self::ResendWebhookSecret => 'The secret proving a delivery report really came from Resend.',
            self::VonageKey => 'The key the text message provider sends with.',
            self::VonageSecret => 'The secret the text message provider sends with.',
            self::VonageSmsFrom => 'The sender a text message recipient sees.',
            self::TelegramBotToken => 'The token the Telegram bot sends with.',
            self::TelegramBotUrl => 'The public address of the Telegram bot.',
            self::TelegramBotWebhookSecret => 'The secret proving a call really came from Telegram.',
            self::CashierCurrency => 'The currency an amount is quoted in when nothing has picked one.',
            self::SelfHosted => 'Whether this instance runs outside the cloud one.',
        };
    }

    /** @return list<string> */
    #[Override]
    public function translationKeys(): array
    {
        return array_values(array_filter([$this->description(), $this->warning()]));
    }
}
