@extends('marketing.skeleton')

@section('content')
  <body class="marketing homepage">

    <div class="top-page">
      <div class="container">
        <div class="row">
          <div class="col-xs-12">
            <div class="logo">
              <img src="/img/small-logo.png">
            </div>

            <div class="navigation">
              @if (Auth::check())
              <a href="/dashboard">Access your account</a>
              @else
              <a href="/login">Login</a>
              @endif
            </div>

            <div class="headline">
              <h1>Your social memory.</h1>
              <p>You know all those birthdays you forget? And when you last called your grandmother? When you last had coffee with that ex-colleague? Who did you lend that book to again?</p>
              <p class="cta">

                @if (Auth::check())
                <a href="/dashboard" class="btn btn-primary">Try Monica</a>
                @else
                <a href="/register" class="btn btn-primary">Try Monica</a>
                @endif
              </p>
              <img src="/img/marketing/main-screen.png">
            </div>

          </div>
        </div>
      </div>
    </div>

    <div class="before-sections">
      <div class="container">
        <div class="row">
          <div class="col-xs-12">
            <h3>Monica makes it easy to remember everything about your friends and family.</h3>
          </div>
        </div>
      </div>
    </div>

    <div class="section-homepage people">
      <div class="container">
        <div class="row">
          <div class="col-xs-12 col-sm-6">
            <h2>Add important information about people you care about</h2>
            <p>Do you remember the names of kids of the people you care about? Their birthdates? Or even the name of the significant other? Monica lets you remember them easily.</p>
          </div>
          <div class="col-xs-12 col-sm-6 visual">
            <img src="/img/marketing/homepage-important-information.svg">
          </div>
        </div>
      </div>
    </div>

    <div class="section-homepage dates">
      <div class="container">
        <div class="row">
          <div class="col-xs-12 col-sm-6 visual">
            <img src="/img/marketing/homepage-important-dates.png">
          </div>
          <div class="col-xs-12 col-sm-6">
            <h2>Keep track of important dates</h2>
            <p>Monica will remind you by email every important dates about each one of your contact. It can be about an anniversary, or to simply go out with your friend.</p>
          </div>
        </div>
      </div>
    </div>

    <div class="section-homepage activities">
      <div class="container">
        <div class="row">
          <div class="col-xs-12 col-sm-6">
            <h2>Record all activities done together.</h2>
            <p>Monica lets you record all the activities you’ve done with your friends, sorted by categories. You’ll always remember the last time you’ve done something with someone.</p>
          </div>
          <div class="col-xs-12 col-sm-6 visual">
            <img src="/img/marketing/homepage-activities.png">
          </div>
        </div>
      </div>
    </div>

    <div class="section-homepage features">
      <div class="container">
        <div class="row">
          <div class="col-xs-12">
            <h3>Here are everything you can do with Monica</h3>
          </div>
          <div class="col-xs-12 col-sm-6">
            <ul>
              <li>
                <i class="fa fa-check-square-o" aria-hidden="true"></i> Manage significant others
              </li>
              <li>
                <i class="fa fa-check-square-o" aria-hidden="true"></i> Manage food preferencies
              </li>
              <li>
                <i class="fa fa-check-square-o" aria-hidden="true"></i> Important dates about someone
              </li>
              <li>
                <i class="fa fa-check-square-o" aria-hidden="true"></i> Reminders by emails
              </li>
            </ul>
          </div>
          <div class="col-xs-12 col-sm-6">
            <ul>
              <li>
                <i class="fa fa-check-square-o" aria-hidden="true"></i> Todos and tasks about someone
              </li>
              <li>
                <i class="fa fa-check-square-o" aria-hidden="true"></i> Gifts management
              </li>
              <li>
                <i class="fa fa-check-square-o" aria-hidden="true"></i> Record activities done with someone
              </li>
              <li>
                <i class="fa fa-check-square-o" aria-hidden="true"></i> Information about the children
              </li>
            </ul>
          </div>
        </div>
      </div>
    </div>

    <div class="why">
      <div class="container">
        <div class="row">
          <div class="col-xs-12">
            <h3>Why Monica?</h3>
            <p>Monica is for people who have jobs, a family and are busy trying to find a good work/life balance. So busy, that they don’t have time anymore to remember to call a friend, say happy birthday to a nephew, or remember to invite someone special to come eat dinner next week. The older we get, the more life gets in the way. It’s sad, but it’s just a matter of priorities. I’ve created Monica to help these people.</p>
            <p>Monica is not for everyone. It’s probably not targeted at people who see their friends every day and have a super busy social life.</p>
            <p>Monica is not a social network. It’s not Facebook. It’s for your eyes only.</p>
            <p>Monica is the first real Friends Relationship Management out there.</p>
          </div>
        </div>
      </div>
    </div>

    <div class="section-homepage try">
      <div class="container">
        <div class="row">
          <div class="col-xs-12">
            <h3>Ready to get started?</h3>
            <p>
              @if (Auth::check())
              <a href="/dashboard" class="btn btn-primary">Try Monica</a>
              @else
              <a href="/register" class="btn btn-primary">Try Monica</a>
              @endif
            </p>
          </div>
        </div>
      </div>
    </div>

    @include('marketing._footer')
  </body>
@endsection
