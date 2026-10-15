<?php

namespace App\Http\Controllers;

use App\Services\SummaryService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SummaryController extends Controller
{
    /**
     * GET /summary — ringkasan 7 hari terakhir.
     */
    public function __invoke(Request $request, SummaryService $summary): View
    {
        return view('summary', [
            'summary' => $summary->week($request->user()),
        ]);
    }
}
