@extends('marketing.skeleton')

@section('content')

<body class="marketing homepage">

  @include('marketing._menu')

  <div class="container privacy">
    <div class="row">
      <div class="col-xs-12">
        <h2>Privacy policy - Last update: Jan 01, 2016</h2>
        <p>When you create your account, you are giving the site information about yourself that I collect. This includes your name, your email address and your password, that is encrypted before being stored. I do not store any other personal information.</p>
        <p>When you login to the service, we are using cookies to remember your login credentials. This is the only use I do with the cookies.</p>
        <p>If you create your account with Facebook, we'll save the email address you use on Facebook and use it to populate your account. No other information is collected or saved.</p>
        <p>Monica runs on Linode and I'm the only one, apart from Linode's employees, who has access to those servers.</p>
        <p>Transactional emails are served through Postmark.</p>
        <p>The site will never show ads and does not sell data to a third party.</p>
        <p>Google Analytics is used on the marketing website to track visits, keywords search and to know my audience.</p>
        <p>Monica uses only open-source projects that are mainly hosted on Github.</p>
        <p>When you close your account, we will immediately destroy all your personal information and won't keep any backup.</p>
      </div>
    </div>
  </div>

  @include('marketing._footer')
</body>


@endsection
