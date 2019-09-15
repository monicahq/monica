<!-- Modal -->
<div class="modal log-call fade" id="logCallModal" tabindex="-1">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">{{ trans('people.modal_call_title') }}</h5>
        <button type="button" class="close" data-dismiss="modal">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <form method="POST" action="{{ route('people.calls.store', $contact) }}">
          {{ csrf_field() }}

          <div class="form-group">
            <label for="content" class="col-form-label b">{{ trans('people.modal_call_comment') }}</label>
            <textarea class="form-control mb3" name="content" id="content" maxlength="2500" autofocus>
            </textarea>

            <p class="f6">{{ trans('app.markdown_description')}} <a href="https://guides.github.com/features/mastering-markdown/" target="_blank" rel="noopener noreferrer">{{ trans('app.markdown_link') }}</a></p>

            <p class="date-it-happened">
              {{ trans('people.modal_call_date') }} <a href="#" class="change-date-happened">{{ trans('people.modal_call_change') }}</a>
            </p>

            <p class="exact-date">
              {{ trans('people.modal_call_exact_date') }}
              <input type="date" name="called_at" class="form-control"
                           value="{{ now(\App\Helpers\DateHelper::getTimezone())->toDateString() }}"
                           min="{{ now(\App\Helpers\DateHelper::getTimezone())->subYears(120)->toDateString() }}"
                           max="{{ now(\App\Helpers\DateHelper::getTimezone())->toDateString() }}">
            </p>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ trans('app.cancel') }}</button>
        <button type="button" cy-name="save-call-button" class="btn btn-primary modal-cta">{{ trans('app.save') }}</button>
      </div>
    </div>
  </div>
</div>
