<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\LegalEntity;
use Illuminate\Contracts\View\View;
use Livewire\Component;


class Dashboard extends Component
{
    public function mount(LegalEntity $legalEntity): void
    {
    }

    public function render(): View
    {

        return view('dashboard');
    }
}
