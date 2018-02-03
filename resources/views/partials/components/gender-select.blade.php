<select name="gender" id="gender" class="form-control" required>
  @foreach ( $genders as $gender )
    @if ($gender->id == $selectionID)
    <option value="{{ $gender->id }}" selected>{{ $gender->name }}</option>
    @else
    <option value="{{ $gender->id }}" >{{ $gender->name }}</option>
    @endif
  @endforeach
</select>
