@extends('layouts.skeleton')

@section('content')
  <section class="ph3 ph0-ns mt4">

    <div class="mw7 center br3 ba b--gray-monica bg-white mb5 pa5">
      <h2 class="mb4">{{ trans('app.compliance_title') }}</h2>
      <p>{!! trans('app.compliance_desc', ['url' => 'https://monicahq.com/privacy', 'urlterm' => 'https://monicahq.com/terms', 'hreflang' => 'en', ]) !!}</p>
      <p>{{ trans('app.compliance_desc_end') }}</p>

      <form action="compliance/sign" method="POST" class="tc mt4">
        @csrf
        <button class="btn btn-primary" name="save" type="submit">{{ trans('app.compliance_terms') }}</button>
      </form>
    </div>
  </div>
@endsection
