@extends('layouts.skeleton')

@section('content')
  <div class="people-show">

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
                <a href="/people">{{ trans('app.breadcrumb_list_contacts') }}</a>
              </li>
              <li>
                {{ $contact->getCompleteName() }}
              </li>
            </ul>
          </div>
        </div>
      </div>
    </div>

    <!-- Page header -->
    @include('people._header')

    <!-- Page content -->
    <div class="main-content gifts modal">
      <div class="{{ Auth::user()->getFluidLayout() }}">
        <div class="row">
          <div class="col-xs-12 col-sm-6 col-sm-offset-3">
            <form method="POST" action="/people/{{ $contact->id }}/gifts/store">
              {{ csrf_field() }}

              <h2>{{ trans('people.gifts_add_title', ['name' => $contact->getFirstName()]) }}</h2>

              @include('partials.errors')

              {{-- Nature of gift --}}
              <fieldset class="form-group">
                <label class="form-check-inline">
                  <input type="radio" class="form-check-input" name="gift-offered" value="is_an_idea" checked>
                  {{ trans('people.gifts_add_gift_idea') }}
                </label>

                <label class="form-check-inline">
                  <input type="radio" class="form-check-input" name="gift-offered" value="has_been_offered">
                  {{ trans('people.gifts_add_gift_already_offered') }}
                </label>
              </fieldset>

              {{-- Title --}}
              <div class="form-group">
                <label for="title">{{ trans('people.gifts_add_gift_title') }}</label>
                <input type="text" class="form-control" name="title" required>
              </div>

              {{-- URL --}}
              <div class="form-group">
                <label for="url">{{ trans('people.gifts_add_link') }}</label>
                <input type="text" class="form-control" name="url" placeholder="https://">
              </div>

              {{-- Value --}}
              <div class="form-group">
                <label for="value">{{ trans('people.gifts_add_value') }}</label>
                <input type="number" class="form-control" name="value" placeholder="$0.00">
              </div>

              {{-- Comment --}}
              <div class="form-group">
                <label for="comment">{{ trans('people.gifts_add_comment') }}</label>
                <textarea class="form-control" id="comment" name="comment" rows="3"></textarea>
              </div>

              @if (! is_null($contact->getCurrentSignificantOther()) or $contact->getNumberOfKids() != 0)
              <div class="form-group">
                <div class="form-check">
                  <label class="form-check-label">
                    <input class="form-check-input" type="checkbox" name="giftForSomeone">
                    {{ trans('people.gifts_add_someone', ['name' => $contact->getLastName()]) }}
                  </label>
                </div>
                <select id="lovedOne" name="lovedOne" class="form-control" required>

                  {{-- Significant other --}}
                  @if (! is_null($contact->getCurrentSignificantOther()))
                    <option value="S{{ $contact->getCurrentSignificantOther()->id }}">{{ $contact->getCurrentSignificantOther()->getFirstName() }}</option>
                  @endif

                  {{-- Kids --}}
                  @foreach($contact->getKids() as $kid)
                  <option value="K{{ $kid->id }}">{{ $kid->getFirstName() }}</option>
                  @endforeach
                </select>
              </div>
              @endif

              <div class="form-group actions">
                <button type="submit" class="btn btn-primary">{{ trans('people.gifts_add_cta') }}</button>
                <a href="/people/{{ $contact->id }}/activities" class="btn btn-secondary">{{ trans('app.cancel') }}</a>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>

  </div>
@endsection
