{{--
  A form that carries the csrf token and the method spoofing field, so no screen
  has to remember either.

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
  <input type="hidden" name="_token" value="{{ csrf_token() }}" autocomplete="off" />
  <input type="hidden" name="_method" value="{{ $method }}" />
  {{ $slot }}
</form>
