<?php

declare(strict_types=1);

namespace App\View\Components;

use App\ViewModels\Admin\AdminNavigationViewModel;
use Illuminate\View\Component;
use Illuminate\View\View;
use Override;

class AdminLayout extends Component
{
    public function __construct(
        public ?string $title = null,
    ) {}

    #[Override]
    public function render(): View
    {
        return view('layouts.admin', [
            'navigation' => new AdminNavigationViewModel,
        ]);
    }
}
