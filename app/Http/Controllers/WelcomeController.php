<?php

namespace App\Http\Controllers;

use App\Models\GalleryImage;
use App\Models\GallerySetting;
use App\Models\SscMemberImage;
use Inertia\Inertia;
use Inertia\Response;

class WelcomeController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Welcome', [
            'sscMembers' => SscMemberImage::publicPayload(),
            'galleryImages' => GalleryImage::publicPayload(),
            'galleryStyle' => GallerySetting::current()->style,
        ]);
    }
}
