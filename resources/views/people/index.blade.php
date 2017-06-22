@extends('layouts.skeleton')

@section('content')
  <div class="people-list">
    {{ csrf_field() }}

    {{-- Breadcrumb --}}
    <div class="breadcrumb">
      <div class="{{ Auth::user()->getFluidLayout() }}">
        <div class="row">
          <div class="col-xs-12">
            <ul class="horizontal">
              <li>
                <a href="/dashboard">{{ trans('app.breadcrumb_dashboard') }}</a>
              </li>
              <li>
                {{ trans('app.breadcrumb_list_contacts') }}
              </li>
            </ul>
          </div>
        </div>
      </div>
    </div>

    <!-- Page content -->
    <div class="main-content">

      @if ($contacts->count() == 0)

        <div class="blank-people-state">
          <div class="{{ Auth::user()->getFluidLayout() }}">
            <div class="row">
              <div class="col-xs-12">
                <h3>{{ trans('people.people_list_blank_title') }}</h3>
                <div class="cta-blank">
                  <a href="/people/add" class="btn btn-primary">{{ trans('people.people_list_blank_cta') }}</a>
                </div>
                <div class="illustration-blank">
                  <img src="/img/people/blank.svg">
                </div>
              </div>
            </div>
          </div>
        </div>

      @else

        <div class="{{ Auth::user()->getFluidLayout() }}">
          <div class="row">

            <div class="col-xs-12 col-md-9" id="search-list">

              <input class="search form-control" placeholder="{{ trans('people.people_list_search') }}" />

              <ul class="list">
                @foreach($contacts as $contact)

                <li class="people-list-item">
                  <a href="/people/{{ $contact->id }}">
                    @if ($contact->has_avatar == 'true')
                      <img src="{{ $contact->getAvatarURL(110) }}" width="43">
                    @else
                      @if ( $gravatarUrl = $contact->getGravatar(174) )
                        <img src="{{ $gravatarUrl }}" width="43">
                      @else
                        @if (count($contact->getInitials()) == 1)
                        <div class="avatar one-letter" style="background-color: {{ $contact->getAvatarColor() }};">
                          {{ $contact->getInitials() }}
                        </div>
                        @else
                        <div class="avatar" style="background-color: {{ $contact->getAvatarColor() }};">
                          {{ $contact->getInitials() }}
                        </div>
                        @endif
                      @endif
                    @endif
                    <span class="people-list-item-name">
                      {{ $contact->getCompleteName(auth()->user()->name_order) }}
                    </span>

                    <span class="people-list-item-information">
                      {{ trans_choice('people.people_list_number_kids', $contact->kids_count, ['count' => $contact->kids_count]) }} <br />
                      <span>{{ trans('people.people_list_last_updated') }} {{ \App\Helpers\DateHelper::getShortDate($contact->updated_at) }}</span>
                    </span>
                  </a>
                </li>

                @endforeach
              </ul>
            </div>

            <div class="col-xs-12 col-md-3 sidebar">
              <a href="/people/add" class="btn btn-primary sidebar-cta">
                {{ trans('people.people_list_blank_cta') }}
              </a>
              <ul>

                @if (Auth::user()->contacts_sort_order == 'lastnameAZ')
                  <li class="selected">
                    {{ trans('people.people_list_lastnameAZ') }}
                  </li>
                @else
                  <li>
                    <a href="/people?sort=lastnameAZ">{{ trans('people.people_list_lastnameAZ') }}</a>
                  </li>
                @endif

                @if (Auth::user()->contacts_sort_order == 'lastnameZA')
                  <li class="selected">
                    {{ trans('people.people_list_lastnameZA') }}
                  </li>
                @else
                  <li>
                    <a href="/people?sort=lastnameZA">{{ trans('people.people_list_lastnameZA') }}</a>
                  </li>
                @endif

                @if (Auth::user()->contacts_sort_order == 'firstnameAZ')
                  <li class="selected">
                    {{ trans('people.people_list_firstnameAZ') }}
                  </li>
                @else
                  <li>
                    <a href="/people?sort=firstnameAZ">{{ trans('people.people_list_firstnameAZ') }}</a>
                  </li>
                @endif

                @if (Auth::user()->contacts_sort_order == 'firstnameZA')
                  <li class="selected">
                    {{ trans('people.people_list_firstnameZA') }}
                  </li>
                @else
                  <li>
                    <a href="/people?sort=firstnameZA">{{ trans('people.people_list_firstnameZA') }}</a>
                  </li>
                @endif
              </ul>
            </div>

          </div>
        </div>

      @endif

    </div>

  </div>
@endsection
