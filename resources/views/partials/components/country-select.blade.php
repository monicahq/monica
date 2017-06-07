<select name="country" class="form-control" required>
  @foreach ( $countries as $country )
    @if ($country->id == $selectionID)
    <option value="{{ $country->id }}" selected>{{ $country->country }}</option>
    @else
    <option value="{{ $country->id }}" >{{ $country->country }}</option>
    @endif
  @endforeach
</select>
