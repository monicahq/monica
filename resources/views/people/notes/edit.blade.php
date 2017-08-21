@extends('layouts.skeleton')

@section('content')
  <div class="people-show kids">

    {{-- Breadcrumb --}}
    <div class="breadcrumb mb4">
      <div class="{{ Auth::user()->getFluidLayout() }}">
        <div class="row">
          <div class="col-xs-12">
            <ul class="horizontal">
              <li>
                <a href="/dashboard">{{ trans('app.breadcrumb_dashboard') }}</a>
              </li>
              <li>
                <a href="/people">{{ trans('app.breadcrumb_list_contacts') }}</a>
              </li>
              <li>
                <a href="/people/{{ $contact->id }}">{{ $contact->getCompleteName(auth()->user()->name_order) }}</a>
              </li>
              <li>
                {{ trans('app.breadcrumb_edit_note') }}
              </li>
            </ul>
          </div>
        </div>
      </div>
    </div>

    <!-- Page content -->
    <div class="mw6 center mb4 ph3 ph0-ns">

      <h2 class="f3 fw3 measure tc">{{ trans('people.notes_edit_title', ['name' => $contact->getFirstName()]) }}</h2>
      <p>{{ trans('app.markdown_description')}} <a href="https://guides.github.com/features/mastering-markdown/"  target="_blank">{{ trans('app.markdown_link') }}</a></p>

      @include('people.notes.form', [
        'method' => 'PUT',
        'action' => route('people.notes.update', [$contact, $note]),
        'buttonText' => trans('people.notes_edit_cta')
      ])
    </div>
  </div>
@endsection
