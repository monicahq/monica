<?php

namespace App\Exceptions;

use Throwable;
use Illuminate\Session\TokenMismatchException;
use League\OAuth2\Server\Exception\OAuthServerException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that should not be reported.
     *
     * @var array<int, class-string<Throwable>>
     */
    protected $dontReport = [
        OAuthServerException::class,
        WrongIdException::class,
    ];

    /**
     * Register the exception handling callbacks for the application.
     *
     * @return void
     * @codeCoverageIgnore
     */
    public function register()
    {
        if (config('monica.sentry_support') && config('app.env') == 'production') {
            $this->reportable(function (Throwable $e) {
                if ($this->shouldReport($e) && app()->bound('sentry')) {
                    app('sentry')->captureException($e);
                }
            });
        }
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Throwable  $e
     * @return \Illuminate\Http\Response|\Symfony\Component\HttpFoundation\Response
     */
    public function render($request, Throwable $e)
    {
        // hopefully catches those pesky token expiries
        // and send them back to login.
        if ($e instanceof TokenMismatchException) {
            return redirect()->route('loginRedirect');
        }

        // Convert all non-http exceptions to a proper 500 http exception
        // if we don't do this exceptions are shown as a default template
        // instead of our own view in resources/views/errors/500.blade.php
        if ($this->shouldReport($e) && ! $this->isHttpException($e) && ! config('app.debug')) {
            $e = new HttpException(500, $e->getMessage());
        }

        return parent::render($request, $e);
    }
}
