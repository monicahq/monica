<select name="currency_id" id="currency_id" class="form-control" required>
  @foreach ( $currencies as $currency )
    @if ($currency->id == $selectionID)
    <option value="{{ $currency->id }}" selected>{{ $currency->name }}</option>
    @else
    <option value="{{ $currency->id }}" >{{ $currency->name }}</option>
    @endif
  @endforeach
</select>
