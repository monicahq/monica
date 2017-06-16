<form method="POST" action="{{ $action }}">
    {{ method_field($method) }}
    {{ csrf_field() }}

    @include('partials.errors')

    <h2>{{ trans('people.notes_add_title', ['name' => $contact->getFirstName()]) }}</h2>

    {{-- Body note --}}
    <div class="form-group">
        <textarea class="form-control" id="body" name="body" rows="10">{{ old('body') ?? $note->body }}</textarea>
    </div>

    <div class="form-group actions">
        <button type="submit" class="btn btn-primary">{{ $buttonText }}</button>
        <a href="/people/{{ $contact->id }}" class="btn btn-secondary">{{ trans('app.cancel') }}</a>
    </div> <!-- .form-group -->
</form>
