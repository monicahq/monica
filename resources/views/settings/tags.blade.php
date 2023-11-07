@extends('layouts.skeleton')

@section('content')

<div class="settings">

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
              <a href="{{ route('settings.index') }}">{{ trans('app.breadcrumb_settings') }}</a>
            </li>
            <li>
              {{ trans('app.breadcrumb_settings_tags') }}
            </li>
          </ul>
        </div>
      </div>
    </div>
  </div>

  <div class="{{ Auth::user()->getFluidLayout() }}">
    <div class="row">

      @include('settings._sidebar')

      <div class="col-12 col-sm-9 tags-list">
        <div class="br3 ba b--gray-monica bg-white mb4">
          <div class="pa3 bb b--gray-monica">
            @if (auth()->user()->account->tags->count() == 0)

              <div class="col-12 col-sm-9 blank-screen">

              <img src="img/settings/tags/tags.png">

              <h2>{{ trans('settings.tags_blank_title') }}</h2>

              <p>{{ trans('settings.tags_blank_description') }}</p>

            </div>

            @else

              <h3 class="with-actions">
                {{ trans('settings.tags_list_title') }}
              </h3>

              <p>{{ trans('settings.tags_list_description') }}</p>

              @if (session('success'))
                <div class="alert alert-success">
                    {{ session('success') }}
                </div>
              @endif

              <ul class="table">
              @foreach (auth()->user()->account->tags as $tag)
                <li class="table-row" data-tag-id="{{ $tag->id }}">
                  <div class="table-cell fl">
                    <form method="POST" action="{{ route('settings.tags.update', $tag) }}" class="fl edit-tag-form hidden">
                      @method('PUT')
                      @csrf
                      <input name="name" value="{{ $tag->name }}" class="di br2 f5 ba b--black-40 pa2 outline-0 edit-tag-input" />
                      <a class="pointer" html="" onclick="this.parentNode.submit(); return false;">{{ trans('app.save') }}</a>
                      <a class="pointer" html="" onclick="toggleEditInput(this); return false;">{{ trans('app.close') }}</a>
                    </form>
                    <div class="tag-content">
                      <span class="tag-name">{{ $tag->name }}</span>
                      <span class="tags-list-contact-number">({{ trans_choice('settings.tags_list_contact_number', $tag->contacts()->count(), ['count' => $tag->contacts()->count()]) }})</span>
                      <ul>
                        @foreach($tag->contacts as $contact)
                        <li class="di mr1"><a href="people/{{ $contact->hashID() }}">{{ $contact->name }}</a></li>
                        @endforeach
                      </ul>
                    </div>
                  </div>
                  <div class="table-cell actions">
                    <span class="fl edit-icon-btn" onclick="toggleEditInput(this)">
                      <i class="fa fa-pencil pointer" aria-hidden="true"></i>
                    </span>

                    <form method="POST" action="{{ route('settings.tags.delete', $tag) }}" class="fl">
                      @method('DELETE')
                      @csrf
                      <confirm message="{{ trans('settings.tags_list_delete_confirmation') }}">
                        <i class="fa fa-trash-o pointer" aria-hidden="true"></i>
                      </confirm>
                    </form>
                  </div>

                </li>
              @endforeach
              </ul>

            @endif
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

@endsection
<script>
  function toggleEditInput(editIcon) {
    let tableRow = editIcon.closest('.table-row');
    let tagContent = tableRow.querySelector('.tag-content');
    let editInput = tableRow.querySelector('.edit-tag-form');
    
    tagContent.classList.toggle('hidden');
    editInput.classList.toggle('hidden');
  }
</script>
