<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;
class TeacherMiddleware {public function handle(Request $request, Closure $next){abort_unless($request->user()?->role==='teacher',403);return $next($request);}}
