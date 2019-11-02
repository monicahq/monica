<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTermsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('terms', function (Blueprint $table) {
            $table->increments('id');
            $table->string('term_version');
            $table->mediumText('term_content');
            $table->string('privacy_version');
            $table->mediumText('privacy_content');
            $table->timestamps();
        });

        Schema::create('term_user', function (Blueprint $table) {
            $table->unsignedInteger('account_id');
            $table->unsignedInteger('user_id');
            $table->unsignedInteger('term_id');
            $table->string('ip_address')->nullable();
            $table->timestamps();
        });

        $privacy = '
Monica is an open source project. The hosted version has a premium plan that let us collect money so we can pay for the servers and additional servers, but the main goal is not to make money (otherwise we wouldn’t have opened source it).

Monica comes in two flavors: you can either use our hosted version, or download it and run it yourself. In the latter case, we do not track anything at all. We don’t know that you’ve even downloaded the product. Do whatever you want with it (but respect your local laws).

When you create your account on our hosted version, you are giving the site information about yourself that we collect. This includes your name, your email address and your password, that is encrypted before being stored. We do not store any other personal information.

When you login to the service, we are using cookies to remember your login credentials. This is the only use we do with the cookies.

Monica runs on Linode and we are the only ones, apart from Linode’s employees, who have access to those servers.

We do hourly backups of the database.

Your password is encrypted with bcrypt, a password hashing algorithm that is highly secure. You can also activate two factor authentication on your account if you need an extra layer of security. Apart from those encryptions mechanism, your data is not encrypted in the database. If someone gets access to the database, they will be able to read your data. We do our best to make sure that this will never happen, but it can happen.

If a data breach happens, we will contact the users who are affected to warn them about the breach.

Transactional emails are dserved through Postmark.

We use an open source tool called Sentry to track errors that happen in production. Their service records the errors, but they don’t have access to any information apart the account ID, which lets me debug what’s going on.

The site does not currently and will never show ads. It also does not, and don’t intend to, sell data to a third party, with or without your consent. We are just against this. Fuck ads.

We do no use any tracking third parties, like Google Analytics or Intercom, that track user behaviours or data, neither on the marketing site or the hosted version. We are deeply against their principles as they would use those data to profile you, which we are totally against.

All the data you put on Monica belongs to you. We do not have any rights on it. Please don’t put illegal stuff on it, otherwise we’d be in trouble.

All the information about the contacts you put on Monica are private to you. We do not cross link information between accounts or use one information in an account to populate another account (unlike Facebook for instance).

We use Stripe to collect payments made to access the paid version. We do not store credit card information or anything concerning the transactions themselves on our servers. However, as per the open source library we use to process the payments (Laravel Cashier), we store the last 4 digits of the credit card, the brand name (VISA or MasterCard). As a user, you are identified on Stripe by a random number that they generate and use.

Regarding the payments, you can downgrade to the free plan whenever you like. When you do, Stripe is automatically updated and we have no way to charge you again, even if we would like to. The less we deal with payment information, the happier we are.

You can export your data at any time. You can also use the API to export all your data if you know how to do it. You can also request that we process this ourselves and send it to you. Your data will be exported in the SQL format.

When you close your account, we immediately destroy all your personal information and don’t keep any backup. While you have control over this, we can delete an account for you if you ask us.

In certain situations, we may be required to disclose peronal data in response to lawful requests by public authorities, including to met national security or law enforcements requirements. We just hope that this never happens.

If you violate the terms of use we will terminate your account and notify you about it. However if you follow the "don’t be a dick" policy, nothing should ever happen to you and we’ll all be happy.

Monica uses only open-source projects that are mainly hosted on Github.

We will update this privacy policy as soon as we introduce new information practices. If we do, we will send an email to the email address specified in your account. We will never be a dick about it and will never, ever, introduce something in what we do that will affect your right to the absolute privacy.';

        $term = '
Scope of service
Monica supports the following browsers:

Internet Explorer (11+)
Firefox (50+)
Chrome (latest)
Safari (latest)
I do not guarantee that the site will work with other browsers, but it’s very likely that it will just work.

Rights
You don’t have to provide your real name when you register to an account. You do however need a valid email address if you want to upgrade your account to the paid version, or receive reminders by email.

You have the right to close your account at any time.

You have the right to export your data at any time, in the SQL format.

Your data will not be intentionally shown to other users or shared with third parties.

Your personal data will not be shared with anyone without your consent.

Your data is backed up every hour.

If the site ceases operation, you will receive an opportunity to export all your data before the site dies.

Any new features that affect privacy will be strictly opt-in.

Responsibilities
You will not use the site to store illegal information or data under the Canadian law (or any law).

You have to be at least 18+ to create an account and use the site.

You must not abuse the site by knowingly posting malicious code that could harm you or the other users.

You must only use the site to do things that are widely accepted as morally good.

You may not make automated requests to the site.

You may not abuse the invitation system.

You are responsible for keeping your account secure.

I reserve the right to close accounts that abuse the system (thousands of contacts with hundred of thousands of reminders for instance) or use it in an unreasonable manner.

Other important legal stuff
Though I want to provide a great service, there are certain things about the service I cannot promise. For example, the services and software are provided “as-is”, at your own risk, without express or implied warranty or condition of any kind. I also disclaim any warranties of merchantability, fitness for a particular purpose or non-infringement. Monica will have no responsibility for any harm to your computer system, loss or corruption of data, or other harm that results from your access to or use of the Services or Software.

These Terms can change at any time, but I’ll never be a dick about it. Running this site is a dream come true to me, and I hope I’ll be able to run it as long as I can.
        ';

        $id = DB::table('terms')->insertGetId([
            'privacy_content' => $privacy,
            'privacy_version' => '2',
            'term_content' => $term,
            'term_version' => '2',
            'created_at' => '2018-04-12',
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('terms');
        Schema::dropIfExists('term_user');
    }
}
