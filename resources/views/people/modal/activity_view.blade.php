<!-- Modal -->
<div class="modal log-call fade" id="activity{{$activity->id}}Modal" tabindex="-1">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        {{ $activity->getSummary() }}
        <button type="button" class="close" data-dismiss="modal">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">

              <label for="summary">{{ trans('people.activities_who_was_involved') }}</label>
                  <ul class="contacts">
                      <ul class="contacts-list">
                          @foreach ($activity->contacts as $contact)
                              <li class="pretty-tag"><a href="{{ route('people.show', $contact) }}">{{ $contact->first_name }} {{ $contact->last_name }}</a></li>
                          @endforeach
                      </ul>
                  </ul>
                  <br>

                  <label for="date_it_happened">{{ trans('people.activities_add_date_occured') }}</label>
                  <p>{{ \App\Helpers\DateHelper::getShortDate($activity->date_it_happened) }}</p>
                  <br>

                  <label for="activity_type_id">{{ trans('people.activities_activity') }}</label>
                  @if (isset($activity->type))
                     <p>{{ $activity->type->name }}</p>
                  @else
                     <p>{{ trans('app.unknown')}}</p>
                  @endif
                  <br>

                  <label for="description">{{ trans('people.activities_add_optional_comment') }}</label>
                  <div class="markdown">
                  {!! $activity->parsed_content !!}
                  </div>

      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ trans('app.close') }}</button>
      </div>
    </div>
  </div>
</div>
