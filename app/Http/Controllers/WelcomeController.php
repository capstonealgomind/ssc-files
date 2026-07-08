<?php

namespace App\Http\Controllers;

use App\Models\SscMemberImage;
use Inertia\Inertia;
use Inertia\Response;

class WelcomeController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Welcome', [
            'sscMembers' => SscMemberImage::publicPayload(),
        ]);
    }
}
