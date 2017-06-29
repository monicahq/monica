<form method="POST" action="{{ $action }}">
    {{ method_field($method) }}
    {{ csrf_field() }}

    <h2>{{ trans('people.gifts_add_title', ['name' => $contact->getFirstName()]) }}</h2>

    @include('partials.errors')

    {{-- Nature of gift --}}
    <fieldset class="form-group">
        <label class="form-check-inline" for="offered_0">
            <input type="radio" class="form-check-input" name="offered" id="offered_0" value="0" @if(old('offered') !== true || $gift->is_an_idea) checked @endif>
            {{ trans('people.gifts_add_gift_idea') }}
        </label>

        <label class="form-check-inline" for="offered_1">
            <input type="radio" class="form-check-input" name="offered" id="offered_1" value="1" @if(old('offered') === true || $gift->has_been_offered) checked @endif>
            {{ trans('people.gifts_add_gift_already_offered') }}
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
        <input type="text" class="form-control" name="url" id="url" value="{{ old('url') ?? $gift->url }}" placeholder="https://">
    </div>

    {{-- Value --}}
    <div class="form-group">
        <label for="value_in_dollars">{{ trans('people.gifts_add_value') }}  ({{ Auth::user()->currency->symbol}})</label>
        <input type="number" class="form-control" name="value_in_dollars" id="value_in_dollars" placeholder="0.00" value="{{ old('value_in_dollars') ?? $gift->value_in_dollars }}">
    </div>

    {{-- Comment --}}
    <div class="form-group">
        <label for="comment">{{ trans('people.gifts_add_comment') }}</label>
        <textarea class="form-control" id="comment" name="comment" rows="3">{{ old('comment') ?? $gift->comment }}</textarea>
    </div>

    @if ($contact->significantOther || $contact->kids()->count() !== 0)
        <div class="form-group">
            <div class="form-check">
                <label class="form-check-label" id="has_recipient">
                    <input class="form-check-input" type="checkbox" name="has_recipient" id="has_recipient" value="1">
                    {{ trans('people.gifts_add_someone', ['name' => $contact->getFirstName()]) }}
                </label>
            </div>
            <select id="recipient" name="recipient" class="form-control">

                {{-- Significant other --}}
                @if ($contact->significantOther)
                    <option value="S{{ $contact->significantOther->id }}"
                            @if((old('recipient') && old('recipient') === $contact->significantOther->id)
                                || ($gift->about_object_id === $contact->significantOther->id)
                            )
                                selected
                            @endif
                    >{{ $contact->significantOther->first_name }}</option>
                @endif

                {{-- Kids --}}
                @foreach($contact->kids as $kid)
                    <option value="K{{ $kid->id }}"
                            @if((old('recipient') && old('recipient') === $kid->id) || ($gift->about_object_id === $kid->id))
                                selected
                            @endif
                    >{{ $kid->first_name }}</option>
                @endforeach
            </select>
        </div>
    @endif

    <div class="form-group actions">
        <button type="submit" class="btn btn-primary">{{ trans('people.gifts_add_cta') }}</button>
        <a href="/people/{{ $contact->id }}" class="btn btn-secondary">{{ trans('app.cancel') }}</a>
    </div>
</form>
