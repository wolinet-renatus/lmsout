<?php

namespace App\Http\Controllers\Api\Instructor;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Enrollment;
use Illuminate\Http\Request;

/**
 * Course controller API Controller — handles instructor mobile/client REST endpoints.
 *
 * @author Renatus Bernardo
 */
class CourseController extends Controller
{
    /**
     * Display a listing of the resource records.
     */
    public function index(Request $request)
    {
        $courses = $request->user()->courses()
            ->withCount([
                'enrollments as students_count' => fn($q) => $q->whereIn('status', [
                    Enrollment::STATUS_APPROVED,
                    Enrollment::STATUS_COMPLETED,
                ]),
                'bundles as bundles_count',
            ])
            ->withAvg('reviews as average_rating', 'rating')
            ->latest()
            ->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $courses
        ]);
    }

    /**
     * Display the specified resource details.
     */
    public function show(Request $request, $id)
    {
        $course = $request->user()->courses()
            ->with([
                'topics.lessons:id,course_topic_id,title,duration,is_preview',
                'category:id,name',
                'language:id,name',
                'bundles:id,title,price',
                'bundles.courses:id,title,featured_image',
            ])
            ->withCount([
                'enrollments as students_count' => fn($q) => $q->whereIn('status', [
                    Enrollment::STATUS_APPROVED,
                    Enrollment::STATUS_COMPLETED,
                ]),
                'reviews as reviews_count',
            ])
            ->withAvg('reviews as average_rating', 'rating')
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $course
        ]);
    }
}
