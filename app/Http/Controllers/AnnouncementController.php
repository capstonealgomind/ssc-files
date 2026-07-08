<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class AnnouncementController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('AnnouncementsManage', [
            'announcements' => Announcement::query()
                ->with('user:id,name')
                ->latest()
                ->get()
                ->map(fn (Announcement $announcement) => $announcement->managePayload())
                ->values()
                ->all(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->prepareAnnouncementRequest($request);
        $validated = $this->validateAnnouncement($request);

        Announcement::create([
            'user_id'     => $request->user()->id,
            'title'       => $validated['title'],
            'body'        => $validated['body'],
            'links'       => $this->normalizeLinks($validated['links'] ?? []),
            'image_paths' => $this->storeUploadedImages($request),
        ]);

        return redirect()->route('announcements.manage')
            ->with('success', 'Announcement published successfully.');
    }

    public function update(Request $request, Announcement $announcement): RedirectResponse
    {
        $this->prepareAnnouncementRequest($request);
        $validated = $this->validateAnnouncement($request, updating: true);

        $removePaths = collect($validated['remove_image_paths'] ?? [])
            ->filter()
            ->values()
            ->all();

        $currentPaths = $announcement->image_paths ?? [];
        $remainingPaths = array_values(array_filter(
            $currentPaths,
            fn (string $path) => ! in_array($path, $removePaths, true),
        ));

        foreach ($removePaths as $path) {
            if (in_array($path, $currentPaths, true)) {
                Storage::disk('public')->delete($path);
            }
        }

        $newPaths = $this->storeUploadedImages($request);
        $imagePaths = array_values(array_filter(array_merge($remainingPaths, $newPaths)));

        $announcement->update([
            'title'       => $validated['title'],
            'body'        => $validated['body'],
            'links'       => $this->normalizeLinks($validated['links'] ?? []),
            'image_paths' => $imagePaths ?: null,
        ]);

        return redirect()->route('announcements.manage')
            ->with('success', 'Announcement updated successfully.');
    }

    public function destroy(Announcement $announcement): RedirectResponse
    {
        $this->deleteImages($announcement->image_paths ?? []);
        $announcement->delete();

        return redirect()->route('announcements.manage')
            ->with('success', 'Announcement deleted successfully.');
    }

    private function prepareAnnouncementRequest(Request $request): void
    {
        $links = collect($request->input('links', []))
            ->filter(fn ($link) => filled($link['label'] ?? null) || filled($link['url'] ?? null))
            ->values()
            ->all();

        $request->merge(['links' => $links]);
    }

    private function validateAnnouncement(Request $request, bool $updating = false): array
    {
        $rules = [
            'title'                  => 'required|string|max:255',
            'body'                   => 'required|string|max:10000',
            'links'                  => 'nullable|array|max:10',
            'links.*.label'          => 'required_with:links.*.url|string|max:255',
            'links.*.url'            => 'required_with:links.*.label|url|max:2048',
            'images'                 => 'nullable|array|max:10',
            'images.*'               => 'image|mimes:jpg,jpeg,png,webp,gif|max:5120',
            'remove_image_paths'     => 'nullable|array|max:20',
            'remove_image_paths.*'   => 'string|max:255',
        ];

        if (! $updating) {
            $rules['images'] = 'nullable|array|max:10';
        }

        return $request->validate($rules, [
            'title.required'     => 'Enter an announcement title.',
            'body.required'      => 'Enter the announcement body.',
            'links.*.url.url'    => 'Each link must be a valid URL.',
            'images.*.image'     => 'Each file must be a valid image.',
            'images.*.mimes'     => 'Images must be JPG, PNG, WebP, or GIF.',
            'images.*.max'       => 'Each image must be 5MB or smaller.',
        ]);
    }

    /**
     * @param  array<int, array{label?: string, url?: string}>  $links
     * @return array<int, array{label: string, url: string}>|null
     */
    private function normalizeLinks(array $links): ?array
    {
        $normalized = collect($links)
            ->filter(fn ($link) => filled($link['label'] ?? null) && filled($link['url'] ?? null))
            ->map(fn ($link) => [
                'label' => trim($link['label']),
                'url'   => trim($link['url']),
            ])
            ->values()
            ->all();

        return $normalized ?: null;
    }

    /**
     * @return array<int, string>
     */
    private function storeUploadedImages(Request $request): array
    {
        if (! $request->hasFile('images')) {
            return [];
        }

        $paths = [];

        foreach ($request->file('images') as $image) {
            if ($image) {
                $paths[] = $image->store('announcements', 'public');
            }
        }

        return $paths;
    }

    /**
     * @param  array<int, string>  $paths
     */
    private function deleteImages(array $paths): void
    {
        foreach ($paths as $path) {
            Storage::disk('public')->delete($path);
        }
    }
}
