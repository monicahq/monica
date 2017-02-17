@extends('marketing.skeleton')

@section('content')

<body class="marketing homepage">

  @include('marketing._menu')

  <div class="container releases">
    <div class="row">
      <div class="col-xs-12">
        <h2>All the changes made on Monica since launch</h2>
        <h3>Feb 15 2017</h3>
        <ul>
          <li>Add reminders for the next 30 days on the dashboard.</li>
        </ul>
        <h3>Feb 14 2017</h3>
        <ul>
          <li>Add counters in all tabs in the contact view.</li>
          <li>Localize multiple terms that were not in the translation system.</li>
        </ul>
        <h3>Feb 13 2017</h3>
        <ul>
          <li>Adding a note is now done on a separate page.</li>
        </ul>
        <h3>Feb 12 2017</h3>
        <ul>
          <li>Add a personal diary feature.</li>
        </ul>
        <h3>Feb 11 2017</h3>
        <ul>
          <li>Add a search bar to contact list.</li>
          <li>Add ability to add photos to contacts.</li>
        </ul>
        <h3>Feb 10 2017</h3>
        <ul>
          <li>Major rewrite (again). This change was needed to add unit tests to the application. Most of the code is now tested automatically. I hope you will encounter less bugs from now on.</li>
          <li>The application is now open source.</li>
          <li>Slight redesign of the contact view.</li>
          <li>Redesign of the contact list.</li>
        </ul>
        <h3>Jan 14 2017</h3>
        <ul>
          <li>Add the ability to signup with Facebook.</li>
          <li>Contacts have now their own avatars, composed of their initials.</li>
        </ul>
        <h3>Jan 01 2017</h3>
        <ul>
          <li>New marketing website. Happy new year.</li>
        </ul>
        <h3>Dec 27 2016</h3>
        <ul>
          <li>Add a newsletter. Please subscribe to be informed of what's new, straight in your inbox.</li>
        </ul>
        <h3>Dec 25 2016</h3>
        <ul>
          <li>Add a better list of contacts. The list is now sortable and searchable.</li>
          <li>Add a better layout on mobile for the header.</li>
        </ul>
        <h3>Dec 22 2016</h3>
        <ul>
          <li>Add gift management. You can now track gifts you would like to offer to someone, or gifts you already offered.</li>
          <li>Fix a bug about the display of number of kids in the People list</li>
        </ul>
        <h3>Dec 12 2016</h3>
        <ul>
          <li>Add French translation. The translation system is now in place and I'll be able to translate the application in any language from now on.</li>
        </ul>
        <h3>Dec 09 2016</h3>
        <ul>
          <li>Add first version of the dashboard. The second version will add statistics.</li>
        </ul>
        <h3>Dec 06 2016</h3>
        <ul>
          <li>Major redesign of the application. It took me a lot of time. This will allow to add features more easily.</li>
        </ul>
        <h3>Oct 28 2016</h3>
        <ul>
          <li>Add encryption for all the data in the database. If the database is stolen or the information leaked, data are unreadable without the encryption key.</li>
        </ul>
        <h3>Oct 27 2016</h3>
        <ul>
          <li>Add weather information for the city the contact lives in</li>
          <li>Add ability to set an address for a contact</li>
        </ul>
        <h3>Oct 25 2016</h3>
        <ul>
          <li>Add better support for using Monica on your mobile phone. It should work better now.</li>
        </ul>
        <h3>Oct 24 2016</h3>
        <ul>
          <li>Add better icons in the application. I'll add more icons in the coming days to add more life to the interface.</li>
        </ul>
        <h3>Oct 23 2016</h3>
        <ul>
          <li>When you delete your account, it now truly deletes it completely from the database. No possible recoveries.</li>
          <li>Add public statistics page</li>
        </ul>
        <h3>Oct 20 2016</h3>
        <ul>
          <li>Add ability to define food preferencies</li>
          <li>Add ability to choose between a full width layout or a smaller one (activated through Settings page).</li>
          <li>Add ability to update the name of a contact.</li>
          <li>Add privacy policy page.</li>
        </ul>
        <h3>Oct 14 2016</h3>
        <ul>
          <li>Add ability to delete account.</li>
          <li>Add release notes page.</li>
        </ul>
        <h3>Oct 09 2016</h3>
        <ul>
          <li>First release of Monica to the public after months of hard work.</li>
        </ul>
      </div>
    </div>
  </div>

  @include('marketing._footer')
</body>

@endsection
