<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Vaults;

use App\Actions\AnswerReminderOccurrence;
use App\Enums\ReminderAnswerEnum;
use App\Http\Controllers\Controller;
use App\Models\ReminderOccurrence;
use App\Models\Vault;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ReminderOccurrenceAnswerController extends Controller
{
    public function update(Request $request, Vault $vault, ReminderOccurrence $reminderOccurrence): RedirectResponse
    {
        $validated = $request->validate([
            'answer' => ['required', 'string', Rule::in(ReminderAnswerEnum::values())],
        ]);

        $answer = ReminderAnswerEnum::from($validated['answer']);

        try {
            new AnswerReminderOccurrence(
                user: $request->user(),
                occurrence: $reminderOccurrence,
                answer: $answer,
            )->execute();
        } catch (AuthorizationException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('status', $answer === ReminderAnswerEnum::Remembered
            ? __('Kept as remembered.')
            : __('Kept as forgotten.'));
    }
}
