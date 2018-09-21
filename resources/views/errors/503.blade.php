@extends('errors::layout')

@section('title', trans('app.error_unavailable'))

@section('message', trans('app.error_maintenance'))

@section('content')
  <p>
      @lang('app.error_help')
      @lang('app.error_twitter', ['twitter' => config('monica.twitter_account')])
  </p>
@endsection
