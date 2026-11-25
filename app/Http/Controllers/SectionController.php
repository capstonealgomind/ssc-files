<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Section;
use App\Models\YearLevel;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SectionController extends Controller
{
    public function store(Request $request)
    {
        return $this->save($request, new Section);
    }

    public function update(Request $request, Section $section)
    {
        return $this->save($request, $section);
    }

    public function destroy(Section $section)
    {
        $section->delete();

        return back()->with('success', 'Section deleted.');
    }

    private function save(Request $request, Section $section)
    {
        $data = $request->validate([
            'department_id' => 'required|integer|exists:departments,id',
            'course_id' => ['required', 'integer', Rule::exists('courses', 'id')->where('department_id', $request->input('department_id'))],
            'year_level_id' => 'required|integer|exists:year_levels,id',
            'letter' => 'required|string|regex:/\A[A-Z]\z/',
        ]);

        if (YearLevel::findOrFail($data['year_level_id'])->sort_order > Course::findOrFail($data['course_id'])->duration_years) {
            throw ValidationException::withMessages(['year_level_id' => 'This year level is unavailable for the selected course.']);
        }

        try {
            $section->fill($data)->save();
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages(['letter' => 'This section already exists for the selected course and year level.']);
        }

        return back()->with('success', 'Section saved.');
    }
}
