<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Department;
use App\Models\LocationRangeSetting;
use App\Models\Partylist;
use App\Models\Position;
use App\Models\SscMemberImage;
use App\Models\YearLevel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller
{
    private function departmentAcronymRule(?int $ignoreId = null): array
    {
        $unique = Rule::unique('departments', 'acronym');

        if ($ignoreId !== null) {
            $unique->ignore($ignoreId);
        }

        return ['required', 'string', 'max:20', 'regex:/^[A-Za-z0-9][A-Za-z0-9\\s\\-]*$/', $unique];
    }

    private function departmentColorRule(): array
    {
        return ['required', 'string', Rule::in(array_keys(Department::COLORS))];
    }

    private function partylistAcronymRule(?int $ignoreId = null): array
    {
        $unique = Rule::unique('partylists', 'acronym');

        if ($ignoreId !== null) {
            $unique->ignore($ignoreId);
        }

        return ['nullable', 'string', 'max:20', 'regex:/^[A-Za-z0-9][A-Za-z0-9\\s\\-]*$/', $unique];
    }

    public function index(Request $request): Response
    {
        $locationRange = LocationRangeSetting::current();
        $initialAdvancedTab = in_array($request->query('advanced'), ['rangeLimit', 'sscMembers'], true)
            ? $request->query('advanced')
            : null;

        return Inertia::render('Settings', [
            'initialAdvancedTab' => $initialAdvancedTab,
            'departmentColors' => Department::COLORS,
            'locationRange' => [
                'is_enabled'   => $locationRange->is_enabled,
                'latitude'     => $locationRange->latitude,
                'longitude'    => $locationRange->longitude,
                'range_meters' => $locationRange->range_meters,
            ],
            'departments' => Department::query()
                ->orderBy('name')
                ->get(['id', 'name', 'acronym', 'color', 'created_at'])
                ->map(fn (Department $department) => [
                    'id'         => $department->id,
                    'name'       => $department->name,
                    'acronym'    => $department->acronym,
                    'color'      => $department->color,
                    'created_at' => $department->created_at?->format('M d, Y'),
                ])
                ->values()
                ->all(),
            'courses' => Course::query()
                ->with('department:id,name')
                ->orderBy('name')
                ->get(['id', 'department_id', 'name', 'duration_years', 'created_at'])
                ->map(fn (Course $course) => [
                    'id'              => $course->id,
                    'name'            => $course->name,
                    'duration_years'  => $course->duration_years,
                    'department_id'   => $course->department_id,
                    'department_name' => $course->department?->name,
                    'created_at'      => $course->created_at?->format('M d, Y'),
                ])
                ->values()
                ->all(),
            'yearLevels' => YearLevel::query()
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(['id', 'name', 'sort_order', 'created_at'])
                ->map(fn (YearLevel $yearLevel) => [
                    'id'          => $yearLevel->id,
                    'name'        => $yearLevel->name,
                    'sort_order'  => $yearLevel->sort_order,
                    'created_at'  => $yearLevel->created_at?->format('M d, Y'),
                ])
                ->values()
                ->all(),
            'positions' => Position::query()
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(['id', 'name', 'sort_order', 'created_at'])
                ->map(fn (Position $position) => [
                    'id'          => $position->id,
                    'name'        => $position->name,
                    'sort_order'  => $position->sort_order,
                    'created_at'  => $position->created_at?->format('M d, Y'),
                ])
                ->values()
                ->all(),
            'partylists' => Partylist::query()
                ->orderBy('name')
                ->get(['id', 'name', 'acronym', 'description', 'created_at'])
                ->map(fn (Partylist $partylist) => [
                    'id'          => $partylist->id,
                    'name'        => $partylist->name,
                    'acronym'     => $partylist->acronym,
                    'description' => $partylist->description,
                    'created_at'  => $partylist->created_at?->format('M d, Y'),
                ])
                ->values()
                ->all(),
            'sscMembers' => SscMemberImage::ordered()
                ->map(fn (SscMemberImage $image) => [
                    'id'         => $image->id,
                    'image_url'  => asset('storage/'.$image->image_path),
                    'sort_order' => $image->sort_order,
                    'created_at' => $image->created_at?->format('M d, Y'),
                ])
                ->values()
                ->all(),
        ]);
    }

    public function storeDepartment(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name'    => 'required|string|max:255|unique:departments,name',
            'acronym' => $this->departmentAcronymRule(),
            'color'   => $this->departmentColorRule(),
        ]);

        Department::create($validated);

        return redirect()->route('settings')
            ->with('success', 'Department created successfully.');
    }

    public function updateDepartment(Request $request, Department $department): RedirectResponse
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('departments', 'name')->ignore($department->id),
            ],
            'acronym' => $this->departmentAcronymRule($department->id),
            'color'   => $this->departmentColorRule(),
        ]);

        $department->update($validated);

        return redirect()->route('settings')
            ->with('success', 'Department updated successfully.');
    }

    public function destroyDepartment(Department $department): RedirectResponse
    {
        if ($department->courses()->exists()) {
            return redirect()->route('settings')
                ->with('error', 'Cannot delete a department that still has courses.');
        }

        if ($department->users()->exists()) {
            return redirect()->route('settings')
                ->with('error', 'Cannot delete a department assigned to voters.');
        }

        $department->delete();

        return redirect()->route('settings')
            ->with('success', 'Department deleted successfully.');
    }

    public function storeCourse(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name'           => 'required|string|max:255',
            'department_id'  => 'required|exists:departments,id',
            'duration_years' => 'required|integer|min:1|max:6',
        ]);

        $exists = Course::query()
            ->where('department_id', $validated['department_id'])
            ->where('name', $validated['name'])
            ->exists();

        if ($exists) {
            return back()->withErrors([
                'name' => 'This course already exists in the selected department.',
            ])->withInput();
        }

        Course::create($validated);

        return redirect()->route('settings')
            ->with('success', 'Course created successfully.');
    }

    public function updateCourse(Request $request, Course $course): RedirectResponse
    {
        $validated = $request->validate([
            'name'           => 'required|string|max:255',
            'department_id'  => 'required|exists:departments,id',
            'duration_years' => 'required|integer|min:1|max:6',
        ]);

        $exists = Course::query()
            ->where('department_id', $validated['department_id'])
            ->where('name', $validated['name'])
            ->where('id', '!=', $course->id)
            ->exists();

        if ($exists) {
            return back()->withErrors([
                'name' => 'This course already exists in the selected department.',
            ]);
        }

        $course->update($validated);

        return redirect()->route('settings')
            ->with('success', 'Course updated successfully.');
    }

    public function destroyCourse(Course $course): RedirectResponse
    {
        if ($course->users()->exists()) {
            return redirect()->route('settings')
                ->with('error', 'Cannot delete a course assigned to voters.');
        }

        $course->delete();

        return redirect()->route('settings')
            ->with('success', 'Course deleted successfully.');
    }

    public function storeYearLevel(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name'       => 'required|string|max:255',
            'sort_order' => 'required|integer|min:0|max:255',
        ]);

        YearLevel::create($validated);

        return redirect()->route('settings')
            ->with('success', 'Year level created successfully.');
    }

    public function updateYearLevel(Request $request, YearLevel $yearLevel): RedirectResponse
    {
        $validated = $request->validate([
            'name'       => 'required|string|max:255',
            'sort_order' => 'required|integer|min:0|max:255',
        ]);

        $yearLevel->update($validated);

        return redirect()->route('settings')
            ->with('success', 'Year level updated successfully.');
    }

    public function destroyYearLevel(YearLevel $yearLevel): RedirectResponse
    {
        if ($yearLevel->users()->exists()) {
            return redirect()->route('settings')
                ->with('error', 'Cannot delete a year level assigned to voters.');
        }

        $yearLevel->delete();

        return redirect()->route('settings')
            ->with('success', 'Year level deleted successfully.');
    }

    public function storePosition(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name'       => 'required|string|max:255|unique:positions,name',
            'sort_order' => 'required|integer|min:0|max:255',
        ]);

        Position::create($validated);

        return redirect()->route('settings')
            ->with('success', 'Position created successfully.');
    }

    public function updatePosition(Request $request, Position $position): RedirectResponse
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('positions', 'name')->ignore($position->id),
            ],
            'sort_order' => 'required|integer|min:0|max:255',
        ]);

        $position->update($validated);

        return redirect()->route('settings')
            ->with('success', 'Position updated successfully.');
    }

    public function destroyPosition(Position $position): RedirectResponse
    {
        if ($position->candidates()->exists()) {
            return redirect()->route('settings')
                ->with('error', 'Cannot delete a position assigned to candidates.');
        }

        $position->delete();

        return redirect()->route('settings')
            ->with('success', 'Position deleted successfully.');
    }

    public function storePartylist(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:255|unique:partylists,name',
            'acronym'     => $this->partylistAcronymRule(),
            'description' => 'nullable|string|max:1000',
        ]);

        Partylist::create($validated);

        return redirect()->route('settings')
            ->with('success', 'Partylist created successfully.');
    }

    public function updatePartylist(Request $request, Partylist $partylist): RedirectResponse
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('partylists', 'name')->ignore($partylist->id),
            ],
            'acronym'     => $this->partylistAcronymRule($partylist->id),
            'description' => 'nullable|string|max:1000',
        ]);

        $partylist->update($validated);

        return redirect()->route('settings')
            ->with('success', 'Partylist updated successfully.');
    }

    public function destroyPartylist(Partylist $partylist): RedirectResponse
    {
        if ($partylist->candidates()->exists()) {
            return redirect()->route('settings')
                ->with('error', 'Cannot delete a partylist assigned to candidates.');
        }

        $partylist->delete();

        return redirect()->route('settings')
            ->with('success', 'Partylist deleted successfully.');
    }

    public function updateLocationRange(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'is_enabled'   => 'required|boolean',
            'latitude'     => 'nullable|numeric|between:-90,90|required_if:is_enabled,true',
            'longitude'    => 'nullable|numeric|between:-180,180|required_if:is_enabled,true',
            'range_meters' => 'nullable|integer|min:1|max:100000|required_if:is_enabled,true',
        ], [
            'latitude.required_if'     => 'Set a location before enabling the range limit.',
            'longitude.required_if'    => 'Set a location before enabling the range limit.',
            'range_meters.required_if' => 'Enter how many meters away users can access the site.',
        ]);

        $settings = LocationRangeSetting::current();

        if (! $validated['is_enabled']) {
            $settings->update(['is_enabled' => false]);

            return redirect()->route('settings')
                ->with('success', 'Location range limit disabled.');
        }

        $settings->update([
            'is_enabled'   => true,
            'latitude'     => $validated['latitude'],
            'longitude'    => $validated['longitude'],
            'range_meters' => $validated['range_meters'],
        ]);

        return redirect()->route('settings')
            ->with('success', 'Location range limit saved successfully.');
    }

    public function storeSscMembers(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'images'   => 'required|array|min:1',
            'images.*' => 'required|image|mimes:jpg,jpeg,png,webp|max:5120',
        ], [
            'images.required' => 'Select at least one image to upload.',
            'images.min'      => 'Select at least one image to upload.',
            'images.*.image'  => 'Each file must be a valid image.',
            'images.*.mimes'  => 'Images must be JPG, PNG, or WebP.',
            'images.*.max'    => 'Each image must be 5MB or smaller.',
        ]);

        $nextSortOrder = (int) SscMemberImage::query()->max('sort_order') + 1;

        foreach ($validated['images'] as $image) {
            SscMemberImage::create([
                'image_path' => $image->store('ssc-members', 'public'),
                'sort_order' => $nextSortOrder,
            ]);

            $nextSortOrder++;
        }

        return redirect()->route('settings', ['advanced' => 'sscMembers'])
            ->with('success', 'SSC member images saved successfully.');
    }

    public function destroySscMember(SscMemberImage $sscMemberImage): RedirectResponse
    {
        if ($sscMemberImage->image_path) {
            Storage::disk('public')->delete($sscMemberImage->image_path);
        }

        $sscMemberImage->delete();

        return redirect()->route('settings', ['advanced' => 'sscMembers'])
            ->with('success', 'SSC member image removed.');
    }

    public function destroyAllSscMembers(): RedirectResponse
    {
        $images = SscMemberImage::ordered();

        foreach ($images as $image) {
            if ($image->image_path) {
                Storage::disk('public')->delete($image->image_path);
            }
        }

        SscMemberImage::query()->delete();

        return redirect()->route('settings', ['advanced' => 'sscMembers'])
            ->with('success', 'All SSC member images removed.');
    }
}
