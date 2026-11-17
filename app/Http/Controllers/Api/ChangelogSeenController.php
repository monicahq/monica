<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\MarkChangelogSeen;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ChangelogSeenController extends Controller
{
    public function update(Request $request): Response
    {
        new MarkChangelogSeen(user: $request->user())->execute();

        return response()->noContent(204);
    }
}
