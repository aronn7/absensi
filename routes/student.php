<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\{DashboardController,AttendanceController};
Route::prefix('student')->name('student.')->middleware(['auth','active','student'])->group(function(){
 Route::get('/dashboard',DashboardController::class)->name('dashboard');
 Route::get('/attendance',[AttendanceController::class,'index'])->name('attendance');
 Route::post('/attendance',[AttendanceController::class,'store'])->middleware('throttle:15,1')->name('attendance.store');
 Route::get('/history',[AttendanceController::class,'history'])->name('history');
 Route::get('/absences',[\App\Http\Controllers\Student\AbsenceController::class,'index'])->name('absences.index');
 Route::post('/absences',[\App\Http\Controllers\Student\AbsenceController::class,'store'])->middleware('throttle:10,1')->name('absences.store');
});
