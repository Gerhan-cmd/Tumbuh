<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OnboardingController extends Controller
{
    /**
     * Halaman atur target & pengingat (muncul sekali setelah daftar).
     */
    public function show(Request $request): View|RedirectResponse
    {
        if ($request->user()->hasOnboarded()) {
            return redirect()->route('dashboard');
        }

        return view('onboarding', [
            'user' => $request->user(),
            'min' => config('gamification.water_target.min'),
            'max' => config('gamification.water_target.max'),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $min = config('gamification.water_target.min');
        $max = config('gamification.water_target.max');

        $data = $request->validate([
            'water_target' => ['required', 'integer', "between:$min,$max"],
            'reminder_enabled' => ['required', 'boolean'],
            'walk_reminder_enabled' => ['required', 'boolean'],
        ], [
            'water_target.between' => "Target minum harus antara $min dan $max gelas.",
        ]);

        $user = $request->user();
        $user->fill($data);
        $user->onboarded_at = now();
        $user->save();

        return redirect()
            ->route('dashboard')
            ->with('status', 'Bibitmu sudah ditanam! Minum segelas air untuk menyiramnya.');
    }
}
