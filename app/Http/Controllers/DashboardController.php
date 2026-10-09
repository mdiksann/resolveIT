<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(): Response
    {
        DB::select('select 1');

        return Inertia::render('Dashboard', ['environment' => app()->environment(), 'database' => 'Connected']);
    }
}
