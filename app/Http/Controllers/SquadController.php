<?php

namespace App\Http\Controllers;

use App\Models\Squad;
use App\Services\SquadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class SquadController extends Controller
{
    public function __construct(private SquadService $squads)
    {
    }

    /**
     * GET /squad — halaman squad. Kalau belum punya squad: pilihan buat atau gabung.
     */
    public function show(Request $request): View
    {
        $user = $request->user();
        $squad = $this->squads->squadOf($user);

        if ($squad) {
            $this->squads->refreshChallenge($squad);
        }

        return view('squad', [
            'squad' => $squad ? $this->squads->state($squad, $user) : null,
            'maxMembers' => config('gamification.squad.max_members'),
        ]);
    }

    /**
     * POST /squad — buat squad baru.
     */
    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($this->squads->squadOf($user)) {
            return back()->withErrors(['name' => 'Kamu sudah berada di sebuah squad.']);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'min:3', 'max:40'],
        ], [
            'name.required' => 'Nama squad wajib diisi.',
            'name.min' => 'Nama squad minimal 3 huruf.',
            'name.max' => 'Nama squad maksimal 40 huruf.',
        ]);

        DB::transaction(fn () => $this->squads->create($user, trim($data['name'])));

        return redirect()->route('squad')->with('status', 'Squad dibuat! Bagikan kode undangan ke teman-temanmu.');
    }

    /**
     * POST /squad/join — gabung dengan kode undangan.
     */
    public function join(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($this->squads->squadOf($user)) {
            return back()->withErrors(['code' => 'Kamu sudah berada di sebuah squad.']);
        }

        // Huruf kecil dan spasi diterima, lalu dirapikan
        $request->merge(['code' => strtoupper(str_replace(' ', '', (string) $request->input('code')))]);

        $data = $request->validate([
            'code' => ['required', 'string', 'size:6'],
        ], [
            'code.required' => 'Kode undangan wajib diisi.',
            'code.size' => 'Kode undangan terdiri dari 6 karakter.',
        ]);

        $squad = Squad::where('invite_code', $data['code'])->first();

        if (! $squad) {
            return back()->withErrors(['code' => 'Kode tidak ditemukan. Periksa lagi huruf dan angkanya.'])->withInput();
        }

        if ($this->squads->isFull($squad)) {
            return back()->withErrors(['code' => 'Squad ini sudah penuh.'])->withInput();
        }

        DB::transaction(fn () => $this->squads->join($user, $squad));

        return redirect()->route('squad')->with('status', "Selamat datang di squad {$squad->name}!");
    }

    /**
     * DELETE /squad — keluar dari squad.
     */
    public function leave(Request $request): RedirectResponse
    {
        DB::transaction(fn () => $this->squads->leave($request->user()));

        return redirect()->route('squad')->with('status', 'Kamu sudah keluar dari squad.');
    }
}
