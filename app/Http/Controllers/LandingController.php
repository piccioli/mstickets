<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Filament\Facades\Filament;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

/**
 * Landing pubblica su "/" (v0.3.0): una sola CTA verso il login del pannello.
 * Chi ha già una sessione attiva non deve vederla — va dritto alla dashboard.
 */
class LandingController extends Controller
{
    public function __invoke(): View|RedirectResponse
    {
        if (Auth::guard('web')->check()) {
            return redirect(Filament::getUrl());
        }

        return view('marketing.landing');
    }
}
