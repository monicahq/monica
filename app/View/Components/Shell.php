<?php

declare(strict_types=1);

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;
use Override;

/**
 * The shell of every signed in screen that has somewhere to navigate: a column
 * down the left, a bar across the top, and the screen between them.
 *
 * `wide` swaps the reading column for the content column, for a screen built
 * around something too wide to read as sentences.
 */
class Shell extends Component
{
    public function __construct(
        public ?string $title = null,
        public bool $wide = false,
    ) {}

    #[Override]
    public function render(): View
    {
        return view('layouts.shell');
    }
}
