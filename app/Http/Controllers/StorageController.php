<?php

namespace App\Http\Controllers;

use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\Account\Photo;
use App\Helpers\StorageHelper;
use Illuminate\Support\Carbon;
use App\Models\Contact\Contact;
use App\Models\Contact\Document;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Response;
use League\Flysystem\FileNotFoundException;

class StorageController
{
    /**
     * Download file authorization.
     *
     * @param Request $request
     * @param string $file
     * @return \Illuminate\Http\Response|\Symfony\Component\HttpFoundation\StreamedResponse|null
     */
    public function download(Request $request, string $file)
    {
        $accountId = auth()->user()->account_id;
        $folder = Str::before($file, '/');

        switch ($folder) {
            case 'avatars':
                $flag = Contact::where([
                    'account_id' => $accountId,
                    ['avatar_default_url', 'like', "$file%"],
                ])->first();
                $filename = Str::after($file, '/');
                break;

            case 'photos':
                $flag = Photo::where([
                    'account_id' => $accountId,
                    'new_filename' => $file,
                ])->first();
                $filename = $flag ? $flag->original_filename : null;
                break;

            case 'documents':
                $flag = Document::where([
                    'account_id' => $accountId,
                    'new_filename' => $file,
                ])->first();
                $filename = $flag ? $flag->original_filename : null;
                break;

            default:
                $flag = false;
                $filename = null;
                break;
        }

        if (! $flag || ! $flag->exists) {
            abort(404);
        }

        try {
            $disk = StorageHelper::disk(config('filesystem.default'));

            $etag = Cache::rememberForever('etag.'.$file, function () {
                return '"'.Str::uuid().'"';
            });
            $lastModified = Carbon::createFromTimestamp($disk->lastModified($file), 'UTC')->locale('en');

            $headers = [
                'ETag' => $etag,
                'Cache-Control' => 'private, max-age=2592000',
                'Last-Modified' => $lastModified->isoFormat('ddd\, DD MMM YYYY HH\:mm\:ss \G\M\T'),
            ];

            if (!$this->checkConditions($request, $etag, $lastModified)) {
                return Response::noContent(304, $headers)->setNotModified();
            }

            return $disk->response($file, $filename, $headers);
        } catch (FileNotFoundException $e) {
            abort(404);
        }
    }

    private function checkConditions(Request $request, string $etag, Carbon $lastModified): bool
    {
        if ($ifMatch = $request->header('If-Match')) {
            // Only need to check entity tags if they are not *
            if ($ifMatch !== '*') {
                // There can be multiple ETags
                $ifMatch = explode(',', $ifMatch);
                $haveMatch = false;

                foreach ($ifMatch as $ifMatchItem) {
                    // Stripping any extra spaces
                    $ifMatchItem = trim($ifMatchItem, ' ');

                    if ($ifMatchItem === $etag) {
                        $haveMatch = true;
                        break;
                    }
                }
                if (!$haveMatch) {
                    abort(403, 'An If-Match header was specified, but none of the specified ETags matched.');
                }
            }
        }

        if ($ifNoneMatch = $request->header('If-None-Match')) {
            // The If-None-Match header contains an ETag.
            // If the ETag does match the current ETag, we return a 304.
            // The header can also contain *, in which case it's always true.
            $haveMatch = false;
            if ($ifNoneMatch === '*') {
                $haveMatch = true;
            } else {
                // There might be multiple ETags
                $ifNoneMatch = explode(',', $ifNoneMatch);

                foreach ($ifNoneMatch as $ifNoneMatchItem) {
                    // Stripping any extra spaces
                    $ifNoneMatchItem = trim($ifNoneMatchItem, ' ');

                    if ($ifNoneMatchItem === $etag) {
                        $haveMatch = true;
                        break;
                    }
                }
            }

            if ($haveMatch) {
                return false;
            }
        }

        if (!$ifNoneMatch && ($ifModifiedSince = $request->header('If-Modified-Since'))) {
            // The If-Modified-Since header contains a date. We will only
            // return the entity if it has been changed since that date.
            $date = Carbon::parse($ifModifiedSince);

            if ($lastModified->lessThanOrEqualTo($date)) {
                return false;
            }
        }

        if ($ifUnmodifiedSince = $request->header('If-Unmodified-Since')) {
            // The If-Unmodified-Since will allow the request if the
            // entity has not changed since the specified date.
            $date = Carbon::parse($ifUnmodifiedSince);

            // We must only check the date if it's valid
            if ($lastModified->greaterThan($date)) {
                abort(403, 'An If-Unmodified-Since header was specified, but the entity has been changed since the specified date.');
            }
        }

        return true;
    }
}
