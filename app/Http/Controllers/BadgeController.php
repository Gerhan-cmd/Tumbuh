<?php

namespace App\Http\Controllers;

use App\Services\BadgeService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BadgeController extends Controller
{
    /**
     * GET /badges — koleksi lencana: yang sudah didapat dan progres menuju yang belum.
     */
    public function __invoke(Request $request, BadgeService $badges): View
    {
        $user = $request->user();
        $badges->check($user);   // jaga-jaga ada lencana yang belum sempat diberikan

        $list = $badges->all($user);

        return view('badges', [
            'badges' => $list,
            'earnedCount' => count(array_filter($list, fn ($b) => $b['earned'])),
        ]);
    }
}
