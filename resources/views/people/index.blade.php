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
                  @if (! is_null($tags))
                      <p class="clear-filter">
                        {{ trans('people.people_list_filter_tag') }}
                        @foreach ($tags as $tag)
                            <span class="pretty-tag">
                            {{ $tag->name }}
                            </span>
                        @endforeach
                        <a class="{{ \App\Helpers\LocaleHelper::getDirection() }}" href="/people">{{ trans('people.people_list_clear_filter') }}</a>
                      </p>
                  @endif
                  @if ($tagLess)
                      <p class="clear-filter">
                        <span class="mr2">{{ trans('people.people_list_filter_untag') }}</span>
                        <a class="{{ \App\Helpers\LocaleHelper::getDirection() }}"  href="/people">{{ trans('people.people_list_clear_filter') }}</a>
                      </p>
                  @endif

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

        <div class="{{ auth()->user()->getFluidLayout() }}">
          <div class="row">

            <div class="col-xs-12 col-md-9 mb4">

              @if (! is_null($tags))
                  <p class="clear-filter">
                    {{ trans('people.people_list_filter_tag') }}
                    @foreach ($tags as $tag)
                        <span class="pretty-tag">
                        {{ $tag->name }}
                        </span>
                    @endforeach
                    <a class="{{ \App\Helpers\LocaleHelper::getDirection() }}" href="/people">{{ trans('people.people_list_clear_filter') }}</a>
                  </p>
              @endif
              @if ($tagLess)
                  <p class="clear-filter">
                    <span class="mr2">{{ trans('people.people_list_filter_untag') }}</span>
                    <a class="{{ \App\Helpers\LocaleHelper::getDirection() }}"  href="/people">{{ trans('people.people_list_clear_filter') }}</a>
                  </p>
              @endif

              <ul class="list">

                {{-- Sorting options --}}
                <li class="people-list-item sorting">
                  {{ trans_choice('people.people_list_stats', $contacts->count(), ['count' => $contacts->count()]) }}

                  <div class="options {{ \App\Helpers\LocaleHelper::getDirection() }}">
                    <div class="options-dropdowns">
                      <a href="" class="dropdown-btn" data-toggle="dropdown" id="dropdownSort">{{ trans('people.people_list_sort') }}</a>
                      <div class="dropdown-menu" aria-labelledby="dropdownSort">
                        <a class="dropdown-item {{ (auth()->user()->contacts_sort_order == 'firstnameAZ')?'selected':'' }}" href="/people?sort=firstnameAZ">
                          {{ trans('people.people_list_firstnameAZ') }}
                        </a>

                        <a class="dropdown-item {{ (auth()->user()->contacts_sort_order == 'firstnameZA')?'selected':'' }}" href="/people?sort=firstnameZA">
                          {{ trans('people.people_list_firstnameZA') }}
                        </a>

                        <a class="dropdown-item {{ (auth()->user()->contacts_sort_order == 'lastnameAZ')?'selected':'' }}" href="/people?sort=lastnameAZ">
                          {{ trans('people.people_list_lastnameAZ') }}
                        </a>

                        <a class="dropdown-item {{ (auth()->user()->contacts_sort_order == 'lastnameZA')?'selected':'' }}" href="/people?sort=lastnameZA">
                          {{ trans('people.people_list_lastnameZA') }}
                        </a>

                        <a class="dropdown-item {{ (auth()->user()->contacts_sort_order == 'lastactivitydateNewtoOld')?'selected':'' }}" href="/people?sort=lastactivitydateNewtoOld">
                          {{ trans('people.people_list_lastactivitydateNewtoOld') }}
                        </a>

                        <a class="dropdown-item {{ (auth()->user()->contacts_sort_order == 'lastactivitydateOldtoNew')?'selected':'' }}" href="/people?sort=lastactivitydateOldtoNew">
                          {{ trans('people.people_list_lastactivitydateOldtoNew') }}
                        </a>
                      </div>
                    </div>

                  </div>
                </li>

                @foreach($contacts as $contact)

                <li class="people-list-item bg-white">
                  <a href="{{ route('people.show', $contact) }}">
                    @if ($contact->has_avatar)
                      <img src="{{ $contact->getAvatarURL(110) }}" width="43">
                    @else
                      @if (! is_null($contact->gravatar_url))
                        <img src="{{ $contact->gravatar_url }}" width="43">
                      @else
                        @if (strlen($contact->getInitials()) == 1)
                        <div class="avatar one-letter" style="background-color: {{ $contact->getAvatarColor() }};">
                          {{ $contact->getInitials() }}
                        </div>
                        @else
                        <div class="avatar {{ \App\Helpers\LocaleHelper::getDirection() }}" style="background-color: {{ $contact->getAvatarColor() }};">
                          {{ $contact->getInitials() }}
                        </div>
                        @endif
                      @endif
                    @endif
                    <span class="people-list-item-name">
                      {{ $contact->name }}
                    </span>

                    <span class="people-list-item-information {{ \App\Helpers\LocaleHelper::getDirection() }}">
                      {{ trans('people.people_list_last_updated') }} {{ \App\Helpers\DateHelper::getShortDate($contact->last_consulted_at) }}
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

              {{-- Only for subscriptions --}}
              @include('partials.components.people-upgrade-sidebar')

              <ul>
              @foreach ($userTags as $dbtag)
                @if ($dbtag->contacts()->count() > 0)
                <li>
                    <span class="pretty-tag"><a href="/people?{{$url}}tag{{$tagCount}}={{ $dbtag->name_slug }}">{{ $dbtag->name }}</a></span>
                    <span class="number-contacts-per-tag {{ \App\Helpers\LocaleHelper::getDirection() }}">{{ trans_choice('people.people_list_contacts_per_tags', $dbtag->contacts()->count(), ['count' => $dbtag->contacts()->count()]) }}</span>
                </li>
                @endif
              @endforeach

              @if ($userTags->count() != 0)
                <li class="f7 mt3">
                    <a href="/people?no_tag=true">{{ trans('people.people_list_untagged') }}</a>
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
