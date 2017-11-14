<!-- Modal -->
<div class="modal fade" id="addContactFieldType" tabindex="-1">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">{{ trans('settings.personalization_contact_field_type_modal_title') }}</h5>
        <button type="button" class="close" data-dismiss="modal">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <form method="POST" action="/settings/personalization/storeContactFieldType">
          {{ csrf_field() }}

          <div class="form-group">
            <div class="form-group">
              <label for="name">{{ trans('settings.personalization_contact_field_type_modal_name') }}</label>
              <input type="text" class="form-control" name="name" id="name" required>
            </div>

            <div class="form-group">
              <label for="protocol">{{ trans('settings.personalization_contact_field_type_modal_protocol') }}</label>
              <input type="text" class="form-control" name="protocol" id="protocol" placeholder="mailto:">
              <small id="emailHelp" class="form-text text-muted">{{ trans('settings.personalization_contact_field_type_modal_protocol_help') }}</small>
            </div>

            <div class="form-group">
              <label for="icon">{{ trans('settings.personalization_contact_field_type_modal_icon') }}</label>
              <input type="text" class="form-control" name="icon" id="icon" placeholder="fa fa-address-book-o">
              <small id="emailHelp" class="form-text text-muted">{!! trans('settings.personalization_contact_field_type_modal_icon_help') !!}</small>
            </div>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ trans('app.cancel') }}</button>
        <button type="submit" class="btn btn-primary modal-cta">{{ trans('app.save') }}</button>
      </div>
    </div>
  </div>
</div>
