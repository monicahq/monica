<div class="pagehead">
  <div class="{{ Auth::user()->getFluidLayout() }}">
    <div class="row">
      <div class="col-xs-12">

        @include ('partials.notification')

        <div class="people-profile-information">

          @if ($contact->has_avatar == 'true')
            <img src="{{ $contact->getAvatarURL(110) }}" width="87">
          @else
            @if (! is_null($contact->gravatar_url))
              <img src="{{ $contact->gravatar_url }}" width="87">
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

          <h2>
            {{ $contact->getCompleteName(auth()->user()->name_order) }}
          </h2>

          <ul class="tags">
            <ul class="tags-list">
              @foreach ($contact->tags as $tag)
                <li class="pretty-tag"><a href="/people?tags={{ $tag->name_slug }}">{{ $tag->name }}</a></li>
              @endforeach
            </ul>
            <li><a href="#" id="showTagForm">{{ trans('people.tag_edit') }}</a></li>
          </ul>

          <form method="POST" action="/people/{{ $contact->id }}/tags/update" id="tagsForm">
            {{ csrf_field() }}
            <input name="tags" id="tags" value="{{ $contact->getTagsAsString() }}" />
            <div class="tagsFormActions">
              <button type="submit" class="btn btn-primary">{{ trans('app.update') }}</button>
              <a href="#" class="btn" id="tagsFormCancel">{{ trans('app.cancel') }}</a>
            </div>
          </form>

          <ul class="horizontal profile-detail-summary">
            <li>
              @if (is_null($contact->getLastActivityDate(Auth::user()->timezone)))
                {{ trans('people.last_activity_date_empty') }}
              @else
                {{ trans('people.last_activity_date', ['date' => $contact->getLastActivityDate(Auth::user()->timezone)]) }}
              @endif
            </li>
          </ul>

          <a href="/people/{{ $contact->id }}/edit" class="btn edit-information">{{ trans('people.edit_contact_information') }}</a>
        </div>
      </div>
    </div>
  </div>
</div>
