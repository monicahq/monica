<form method="POST" action="{{ $action }}">
    {{ method_field($method) }}
    {{ csrf_field() }}

    <h2>{{ trans('people.gifts_add_title', ['name' => $contact->first_name]) }}</h2>

    @include('partials.errors')

    {{-- Nature of gift --}}
    <fieldset class="form-group">
        <label class="form-check-inline" for="idea">
            <input type="radio" class="form-check-input" name="offered" id="idea" value="idea" @if(old('idea') !== true || $gift->is_an_idea) checked @endif>
            {{ trans('people.gifts_add_gift_idea') }}
        </label>

        <label class="form-check-inline" for="offered">
            <input type="radio" class="form-check-input" name="offered" id="offered" value="offered" @if(old('offered') === true || $gift->has_been_offered) checked @endif>
            {{ trans('people.gifts_add_gift_already_offered') }}
        </label>

        <label class="form-check-inline" for="received">
            <input type="radio" class="form-check-input" name="offered" id="received" value="received" @if(old('received') === true || $gift->has_been_received) checked @endif>
            {{ trans('people.gifts_add_gift_received') }}
        </label>
    </fieldset>

    {{-- Title --}}
    <div class="form-group">
        <label for="name">{{ trans('people.gifts_add_gift_title') }}</label>
        <input type="text" class="form-control" name="name" id="name" value="{{ old('name') ?? $gift->name }}" required>
    </div>

    {{-- URL --}}
    <div class="form-group">
        <label for="url">{{ trans('people.gifts_add_link') }}</label>
        <input type="text" class="form-control" name="url" id="url" dir="ltr" value="{{ old('url') ?? $gift->url }}" placeholder="https://">
    </div>

    {{-- Value --}}
    <div class="form-group">
        <label for="value">{{ trans('people.gifts_add_value') }}  ({{ Auth::user()->currency->symbol}})</label>
        <input type="number" class="form-control" name="value" id="value" placeholder="0" value="{{ old('value') ?? $gift->value }}">
    </div>

    {{-- Comment --}}
    <div class="form-group">
        <label for="comment">{{ trans('people.gifts_add_comment') }}</label>
        <textarea class="form-control" id="comment" name="comment" rows="3">{{ old('comment') ?? $gift->comment }}</textarea>
    </div>

    @if ($familyRelationships->count() !== 0)
        <div class="form-group">
            <div class="form-check">
                <label class="form-check-label" id="has_recipient">
                    <input class="form-check-input" type="checkbox" name="has_recipient" id="has_recipient" value="1" {{ $gift->hasParticularRecipient() ? 'checked' : '' }}>
                    {{ trans('people.gifts_add_someone', ['name' => $contact->first_name]) }}
                </label>
            </div>
            <select id="recipient" name="recipient" class="form-control">
                @foreach($familyRelationships as $familyRelationship)
                    <option value="{{ $familyRelationship->ofContact->id }}"
                        @if($gift->is_for === $familyRelationship->ofContact->id)
                            selected
                        @endif
                    >{{ $familyRelationship->ofContact->first_name }}</option>
                @endforeach
            </select>
        </div>
    @endif

    <div class="form-group actions">
        <button type="submit" cy-name="save-gift-button" class="btn btn-primary">{{ trans('app.save') }}</button>
        <a href="{{ route('people.show', $contact) }}" class="btn btn-secondary">{{ trans('app.cancel') }}</a>
    </div>
</form>
