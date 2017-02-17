@extends('marketing.skeleton')

@section('content')

<body class="marketing homepage">

  @include('marketing._menu')

  <div class="container statistics">
    <div class="row">
      <div class="col-xs-12">
        <h2>Statistics</h2>
        <p>These statistics are calculated every night at midnight and show the total number in each column.</p>
        <table class="table">
          <thead>
            <tr>
              <th>Date</th>
              <th>Users</th>
              <th>Contacts</th>
              <th>Notes</th>
              <th>Reminders</th>
              <th>Tasks</th>
            </tr>
          </thead>
          <tbody>
            @foreach ($statistics as $stat)
            <tr>
              <td>{{ \App\Helpers\DateHelper::getShortDate($stat->created_at, 'en') }}</td>
              <td>{{ $stat->number_of_users }}</td>
              <td>{{ $stat->number_of_contacts }}</td>
              <td>{{ $stat->number_of_notes }}</td>
              <td>{{ $stat->number_of_reminders }}</td>
              <td>{{ $stat->number_of_tasks }}</td>
            </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </div>
  </div>

  @include('marketing._footer')
</body>

@endsection
