{{-- A form carrying the csrf token and the method spoofing field, so no screen remembers either. --}}
{{--
  @var string $method
  @var string $action
  @var bool $upload
--}}
@props([
  'method' => 'get',
  'action' => '',
  'upload' => false,
])

<form method="{{ $method !== 'get' ? 'post' : 'get' }}" action="{{ $action }}" {{ $attributes->merge(['enctype' => $upload ? 'multipart/form-data' : null]) }}>
  @csrf
  <input type="hidden" name="_method" value="{{ $method }}" />
  {{ $slot }}
</form>
