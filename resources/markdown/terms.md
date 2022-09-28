# Terms of Service

Just so we are clear: we strongly believe that everyone has the right to the absolute privacy. Your data is yours. We have no right over it. That being said, let’s start.

The customer portal is the portal that lets customers of both Monica and OfficeLife manage their subscriptions. It’s also open source, meaning the code is freely available for anyone to read and contribute–although we don’t see why other people would want to use this portal that we’ve created for our own use.

To use the customer portal, you can either create an account, or use your Monica or Github credentials.

If you create an account yourself, you are giving the site information about yourself that we collect. This includes your name, your email address and your password, that is encrypted before being stored. We do not store any other personal information at this stage (see below for more information about what we collect after this step).

If you are using your Monica or Github credentials, we are using the Oauth protocol to link your account. In this case we’ll save your email address in our database.

Your password is encrypted with bcrypt, a password hashing algorithm that is highly secure. You can also activate two factor authentication on your account if you need an extra layer of security. Apart from those encryption mechanisms, your data is not encrypted in the database. If someone gets access to the database, they will be able to read your data. We do our best to make sure that this will never happen, but it can happen.

When you login to the service, we are using cookies to remember your login credentials. This is the only use we do with the cookies.

Monica runs on Fortrabbit and we are the only ones, apart from Fortrabbit’s employees, who have access to those servers. Fortrabbit is a PAAS that uses Amazon Web Services, so we hope everything is secure on their end (we assume they are since they pretend it is).

We do hourly backups of the database. The backup is made by Fortrabbit themselves.

If a data breach happens, we will contact the users who are affected to warn them about the breach.

Transactional emails are served through Sendgrid. Apart from your email address and your name, which is needed to actually send the emails, and the content of the email, Sendgrid does not collect any other information that we are aware of.

We use an open source tool called Sentry to track errors and logs that happen in production. Their service records the errors, but they don’t have access to any information apart the account ID, which lets us debug what’s going on.

The site does not currently and will never show ads. It also does not, and don’t intend to, sell data to a third party, with or without your consent. We are just against this. Fuck ads.

We do not use any tracking third parties, like Google Analytics or Intercom, that track user behaviours or data. We are deeply against their principles as they would use those data to profile you, which we are totally against.

All the data you put on the customer portal belongs to you. We do not have any rights on it. Please don’t put illegal stuff on it, otherwise we’d be in trouble.

We use Paddle to collect payments made to subscribe to either OfficeLife or Monica. We do not store credit card information or anything else concerning the transactions themselves on our servers. Paddle requires us to store your physical address, mainly to make sure you are not either a bot or that you credit card is valid. This information (your address) is stored on our servers, and is passed to Paddle as well the first time you make a transaction. It will be stored indefinitely until you delete your account.

Regarding the payments, you can change payments anytime you want, or cancel your subscription at any time, without having to contact us. When you do cancel, Paddle is automatically updated and we have no way to charge you again, even if we would like to. The less we deal with payment information, the happier we are.

You can not export your data at any time yourself, but we will happily do it for you if you want. Your data will be exported in the SQL format.

When you close your account, we immediately destroy all your personal information from the production database, but your information is kept in the backups that we keep for 30 days. After 30 days, your information will be completely destroyed. We can not, I repeat, we can not delete your information in those 30 days period. The process to do it is way too complex. But after 30 days, it’s gone forever.

In certain situations, we may be required to disclose personal data in response to lawful requests by public authorities, including to met national security or law enforcements requirements. We just hope that this never happens.

If you violate the terms of use we will terminate your account and notify you about it. However if you follow the "don’t be a dick" policy, nothing should ever happen to you and we’ll all be happy.

The customer portal uses only open-source projects that are mainly hosted on Github.

We will update this privacy policy as soon as we introduce new information practices. If we do, we will send an email to the email address specified in your account. We will never be a dick about it and will never, ever, introduce something in what we do that will affect your right to the absolute privacy.
