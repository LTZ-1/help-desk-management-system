// app/Http/Middleware/ITAdminMiddleware.php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Department;

class ITAdminMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        
        if (!$user) {
            return redirect()->route('login');
        }

        // Check if user is admin and belongs to IT department
        if (!$user->is_admin || !$user->department_id) {
            return redirect()->route('dashboard')->with('error', 'IT Department administrator access required.');
        }

        // Get IT department
        $itDepartment = Department::where('slug', 'it')->first();

        if (!$itDepartment || $user->department_id !== $itDepartment->id) {
            return redirect()->route('dashboard')->with('error', 'IT Department administrator access required.');
        }

        return $next($request);
    }
}