<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;

class PageController extends Controller
{
    public function elections(): Response
    {
        return Inertia::render('Elections');
    }

    public function candidates(): Response
    {
        return Inertia::render('Candidates');
    }

    public function voters(): Response
    {
        return Inertia::render('Voters');
    }

    public function monitoring(): Response
    {
        return Inertia::render('Monitoring');
    }

    public function settings(): Response
    {
        return Inertia::render('Settings');
    }
}
