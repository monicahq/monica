{{-- The control one field of a possession type is filled in with, chosen by the kind of the field. --}}
{{-- @var array{uuid: string, id: string, name: string, kind: \App\Enums\PossessionFieldKindEnum, options: list<string>, inputName: string, errorKey: string, old: string} $field --}}
@php
  $error = $errors->get($field['errorKey']);
@endphp

@switch($field['kind'])
  @case(\App\Enums\PossessionFieldKindEnum::Number)
    <x-input type="number" step="any" :id="$field['id']" :name="$field['inputName']" :label="$field['name']" :value="$field['old']" :error="$error" />
    @break

  @case(\App\Enums\PossessionFieldKindEnum::Date)
    <x-input type="date" :id="$field['id']" :name="$field['inputName']" :label="$field['name']" :value="$field['old']" :error="$error" />
    @break

  @case(\App\Enums\PossessionFieldKindEnum::Boolean)
    <div class="flex flex-col justify-end pb-2">
      <input type="hidden" name="{{ $field['inputName'] }}" value="" />
      <x-checkbox :id="$field['id']" :name="$field['inputName']" :checked="$field['old'] === '1'" :error="$error">{{ $field['name'] }}</x-checkbox>
    </div>
    @break

  @case(\App\Enums\PossessionFieldKindEnum::Select)
    <x-select :id="$field['id']" :name="$field['inputName']" :label="$field['name']" :error="$error">
      <option value="">{{ __('Not recorded') }}</option>
      @foreach ($field['options'] as $option)
        <option value="{{ $option }}" @selected($field['old'] === $option)>{{ $option }}</option>
      @endforeach
    </x-select>
    @break

  @case(\App\Enums\PossessionFieldKindEnum::Rating)
    <fieldset class="flex flex-col gap-1.5">
      <legend class="pb-1.5 text-label text-ink">{{ $field['name'] }}</legend>

      <div class="flex flex-wrap gap-1.5">
        <x-option-row :id="$field['id'].'-none'" :name="$field['inputName']" value="" type="radio" :chip="true" :checked="$field['old'] === ''">{{ __('None') }}</x-option-row>

        @foreach (range(1, \App\Enums\PossessionFieldKindEnum::MAX_RATING) as $rating)
          <x-option-row :id="$field['id'].'-'.$rating" :name="$field['inputName']" :value="(string) $rating" type="radio" :chip="true" :checked="$field['old'] === (string) $rating">{{ $rating }}</x-option-row>
        @endforeach
      </div>

      <x-error :id="$field['id'].'-error'" :messages="$error" />
    </fieldset>
    @break

  @default
    <x-input :id="$field['id']" :name="$field['inputName']" :label="$field['name']" :value="$field['old']" :error="$error" maxlength="2000" />
@endswitch
