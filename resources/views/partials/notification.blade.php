@if(session('success'))

<div class="alert alert-success" :timeout="4000">
  {{ session('success') }}
</div>

@endif
