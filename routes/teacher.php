<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\{DashboardController,AttendanceController};
Route::prefix('teacher')->name('teacher.')->middleware(['auth','active','teacher'])->group(function(){
 Route::get('/dashboard',DashboardController::class)->name('dashboard');
 Route::get('/attendance',[AttendanceController::class,'index'])->name('attendance');
 Route::post('/attendance',[AttendanceController::class,'store'])->middleware('throttle:15,1')->name('attendance.store');
 Route::get('/history',[AttendanceController::class,'history'])->name('history');
 Route::get('/classes',[\App\Http\Controllers\Teacher\ClassroomController::class,'index'])->name('classes.index');
 Route::get('/classes/{schoolClass}/unlock',[\App\Http\Controllers\Teacher\ClassroomController::class,'unlock'])->name('classes.unlock');
 Route::post('/classes/{schoolClass}/unlock',[\App\Http\Controllers\Teacher\ClassroomController::class,'verify'])->middleware('throttle:5,1')->name('classes.verify');
 Route::get('/classes/{schoolClass}',[\App\Http\Controllers\Teacher\ClassroomController::class,'show'])->middleware('homeroom')->name('classes.show');
});
