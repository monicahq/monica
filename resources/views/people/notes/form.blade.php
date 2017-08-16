<form method="POST" action="{{ $action }}">
    {{ method_field($method) }}
    {{ csrf_field() }}

    @include('partials.errors')

    {{-- Body note --}}
    <div class="form-group">
      <textarea class="form-control" id="body" name="body" rows="10">{{ old('body') ?? $note->body }}</textarea>
    </div>

    <div class="flex items-center justify-center">
      <div class="inline-flex items-center w-100 mr4">
        <button type="submit" class="w-100 btn btn-primary">{{ $buttonText }}</button>
      </div>
      <div class="inline-flex items-center w-100">
        <a href="{{ route('people.show', $contact) }}" class="w-100 btn btn-secondary">{{ trans('app.cancel') }}</a>
      </div>
    </div>
</form>
