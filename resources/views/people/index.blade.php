@extends('layouts.skeleton')

@section('content')
  <div class="people-list">
    {{ csrf_field() }}

    {{-- Breadcrumb --}}
    <div class="breadcrumb">
      <div class="{{ Auth::user()->getFluidLayout() }}">
        <div class="row">
          <div class="col-12">
            <ul class="horizontal">
              <li>
                <a href="{{ route('dashboard.index') }}">{{ trans('app.breadcrumb_dashboard') }}</a>
              </li>
              <li>
                @if ($active)
                {{ trans('app.breadcrumb_list_contacts') }}
                @else
                {{ trans('app.breadcrumb_archived_contacts') }}
                @endif
              </li>
            </ul>
          </div>
        </div>
      </div>
    </div>

    <!-- Page content -->
    <div class="main-content">

      @if ($contactsCount == 0)

        @include('people.blank')

      @else

        <div class="{{ auth()->user()->getFluidLayout() }}">
          <div class="row">

            @if ($hasArchived and !$active)
            <div class="col-12">
              <div class="ba mb3 br3 pa3 tc b--gray-monica bg-gray-monica">
                {!! trans('people.list_link_to_active_contacts', ['url' => route('people.index')]) !!}
              </div>
            </div>
            @endif

            <div class="col-12 col-md-9 mb4">

              @if (! is_null($tags))
              <p class="clear-filter">
                {{ trans('people.people_list_filter_tag') }}
                @foreach ($tags as $tag)
                  <span class="pretty-tag">
                    {{ $tag->name }}
                  </span>
                @endforeach
                <a href="{{ route('people.index') }}">{{ trans('people.people_list_clear_filter') }}</a>
              </p>
              @endif

              @if ($tagLess)
              <p class="clear-filter">
                <span class="mr2">{{ trans('people.people_list_filter_untag') }}</span>
                <a href="{{ route('people.index') }}">{{ trans('people.people_list_clear_filter') }}</a>
              </p>
              @endif

              <ul class="list">

                {{-- Sorting options --}}
                <li class="people-list-item sorting">
                  {{ trans_choice('people.people_list_stats', $contactsCount, ['count' => $contactsCount]) }}
                  <div class="options">
                    <div class="options-dropdowns dropdown">
                      <a href="" class="dropdown-btn" data-toggle="dropdown" id="dropdownSort">{{ trans('people.people_list_sort') }}</a>
                      <div class="dropdown-menu" aria-labelledby="dropdownSort">
                        <a class="dropdown-item {{ (auth()->user()->contacts_sort_order == 'firstnameAZ')?'selected':'' }}" href="{{ route('people.index') }}?sort=firstnameAZ">
                          {{ trans('people.people_list_firstnameAZ') }}
                        </a>

                        <a class="dropdown-item {{ (auth()->user()->contacts_sort_order == 'firstnameZA')?'selected':'' }}" href="{{ route('people.index') }}?sort=firstnameZA">
                          {{ trans('people.people_list_firstnameZA') }}
                        </a>

                        <a class="dropdown-item {{ (auth()->user()->contacts_sort_order == 'lastnameAZ')?'selected':'' }}" href="{{ route('people.index') }}?sort=lastnameAZ">
                          {{ trans('people.people_list_lastnameAZ') }}
                        </a>

                        <a class="dropdown-item {{ (auth()->user()->contacts_sort_order == 'lastnameZA')?'selected':'' }}" href="{{ route('people.index') }}?sort=lastnameZA">
                          {{ trans('people.people_list_lastnameZA') }}
                        </a>

                        <a class="dropdown-item {{ (auth()->user()->contacts_sort_order == 'lastactivitydateNewtoOld')?'selected':'' }}" href="{{ route('people.index') }}?sort=lastactivitydateNewtoOld">
                          {{ trans('people.people_list_lastactivitydateNewtoOld') }}
                        </a>

                        <a class="dropdown-item {{ (auth()->user()->contacts_sort_order == 'lastactivitydateOldtoNew')?'selected':'' }}" href="{{ route('people.index') }}?sort=lastactivitydateOldtoNew">
                          {{ trans('people.people_list_lastactivitydateOldtoNew') }}
                        </a>
                      </div>
                    </div>
                  </div>
                </li>

                <div class="{{ htmldir() == 'ltr' ? 'fl' : 'fr' }} w-100">
                    <div class="br2 bg-white mb4">
                        <contact-list
                          :show-archived="{{ \Safe\json_encode(! $active) }}"
                        ></contact-list>
                    </div>
                </div>

              </ul>
            </div>

            <div class="col-12 col-md-3 sidebar">
              <a href="{{ route('people.create') }}" id="button-add-contact" class="btn btn-primary sidebar-cta">
                {{ trans('people.people_list_blank_cta') }}
              </a>

              <ul>
                @if ($hasArchived)
                  @if ($active)
                  <li><a href="{{ route('people.archived') }}">@lang('people.list_link_to_archived_contacts')</a></li>
                  @endif
                @endif
              </ul>

              {{-- Only for subscriptions --}}
              @include('partials.components.people-upgrade-sidebar')

              <ul class="mb4">
              @foreach ($tagsCount as $tag)
                @if ($tag->contact_count > 0)
                <li>
                    <span class="pretty-tag"><a href="{{ route('people.index') }}?{{$url}}tag{{$tagCount}}={{ $tag->name_slug }}">{{ $tag->name }}</a></span>
                    <span class="number-contacts-per-tag">{{ trans_choice('people.people_list_contacts_per_tags', $tag->contact_count, ['count' => $tag->contact_count]) }}</span>
                </li>
                @endif
              @endforeach

              @if ($tagsCount->count() != 0)
                <li class="f7 mt3">
                    <a href="{{ route('people.index') }}?no_tag=true">{{ trans('people.people_list_untagged') }}</a>
                </li>
              @endif

              @if ($deceasedCount > 0)
                @if ($hidingDeceased)
                    <li class="f7 mt3"><a href="{{ $active ? route('people.index') : route('people.archived') }}?show_dead=true">{{ trans('people.people_list_show_dead', ['count' => $deceasedCount]) }}</a></li>
                @else
                    <li class="f7 mt3"><a href="{{ $active ? route('people.index') : route('people.archived') }}?show_dead=false">{{ trans('people.people_list_hide_dead', ['count' => $deceasedCount]) }}</a></li>
                @endif
              @endif
              </ul>
            </div>

          </div>
        </div>

      @endif

    </div>

  </div>
@endsection
