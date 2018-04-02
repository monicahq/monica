<div class="pagehead">
  <div class="{{ Auth::user()->getFluidLayout() }}">
    <div class="row">
      <div class="col-xs-12">

        @include ('partials.notification')

        <div class="people-profile-information">

          @if ($contact->has_avatar)
            <img src="{{ $contact->getAvatarURL(110) }}" width="87">
          @else
            @if (! is_null($contact->gravatar_url))
              <img src="{{ $contact->gravatar_url }}" width="87">
            @else
              @if (strlen($contact->getInitials()) == 1)
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
            @if ($contact->birthday_special_date_id)
              @if ($contact->birthdate->getAge())
                <span class="ml3 light-silver f4">(<i class="fa fa-birthday-cake mr1"></i> {{ $contact->birthdate->getAge() }})</span>
              @endif
            @endif
          </h2>

          <ul class="horizontal profile-detail-summary">
            @if ($contact->is_dead)
              <li>
                @if (! is_null($contact->deceasedDate))
                  {{ trans('people.deceased_label_with_date', ['date' => $contact->deceasedDate->toShortString()]) }}
                @else
                  {{ trans('people.deceased_label') }}
                @endif
              </li>
            @endif
            <li>
              @if (is_null($contact->getLastCalled()))
                {{ trans('people.last_called_empty') }}
              @else
                {{ trans('people.last_called', ['date' => \App\Helpers\DateHelper::getShortDate($contact->getLastCalled())]) }}
              @endif
            </li>
            <li>
              @if (is_null($contact->getLastActivityDate()))
                {{ trans('people.last_activity_date_empty') }}
              @else
                {{ trans('people.last_activity_date', ['date' => \App\Helpers\DateHelper::getShortDate($contact->getLastActivityDate())]) }}
              @endif
            </li>
          </ul>

          <ul class="tags">
            <ul class="tags-list">
              @foreach ($contact->tags as $tag)
                <li class="pretty-tag"><a href="/people?tag1={{ $tag->name_slug }}">{{ $tag->name }}</a></li>
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

          <ul class="horizontal quick-actions">
            <li>
              <a href="/people/{{ $contact->id }}/edit" class="btn edit-information">{{ trans('people.edit_contact_information') }}</a>
            </li>
          </ul>
        </div>
      </div>
    </div>
  </div>
</div>
