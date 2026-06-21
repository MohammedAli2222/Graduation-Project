<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCoursesAreSetup
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        if ($user && $user->studentProfile) {

            $studentProfile = $user->studentProfile;

            $currentYear = is_object($studentProfile->academic_year) ? $studentProfile->academic_year->value : $studentProfile->academic_year;
            $currentSemester = is_object($studentProfile->semester) ? $studentProfile->semester->value : $studentProfile->semester;

            $hasCourses = $studentProfile->courses()
                ->where('year', $currentYear)
                ->where('semester', $currentSemester)
                ->exists();

            if (! $hasCourses) {
                return response_error(null, 403, 'Unauthorized: You must setup your academic courses first before accessing this feature.');
            }
        }

        return $next($request);
    }
}
