<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\{AttendanceController,CalendarController,ProfileController,NotificationController,CardController,ReportController};
use App\Http\Controllers\Student\AbsenceController;
require __DIR__.'/auth.php';
Route::middleware(['auth','active'])->group(function(){
 Route::get('/dashboard',fn()=>redirect()->route(auth()->user()->homeRoute()))->name('dashboard');
 Route::get('/calendar',[CalendarController::class,'index'])->name('calendar.index');
 Route::get('/profile',[ProfileController::class,'edit'])->name('profile.edit');
 Route::put('/profile',[ProfileController::class,'update'])->name('profile.update');
 Route::get('/notifications',[NotificationController::class,'index'])->name('notifications.index');
 Route::post('/notifications/read-all',[NotificationController::class,'readAll'])->name('notifications.read-all');
 Route::post('/notifications/{id}/read',[NotificationController::class,'read'])->name('notifications.read');
 Route::get('/cards',[CardController::class,'index'])->name('cards.index');
 Route::get('/cards/{card}',[CardController::class,'show'])->name('cards.show');
 Route::get('/cards/{card}/download',[CardController::class,'download'])->name('cards.download');
 Route::get('/absence/{absence}/evidence',[AbsenceController::class,'evidence'])->name('absence.evidence');
 Route::post('/absence/{absence}/review',[AbsenceController::class,'review'])->name('absence.review');
 Route::get('/scanner',[AttendanceController::class,'scanner'])->middleware('admin')->name('scanner');
 Route::post('/scanner',[AttendanceController::class,'scan'])->middleware(['admin','throttle:90,1'])->name('scanner.scan');
 Route::middleware('can:reports')->group(function(){
  Route::get('/reports',[ReportController::class,'index'])->name('reports.index');
  Route::post('/reports',[ReportController::class,'generate'])->name('reports.generate');
  Route::get('/reports/{report}',[ReportController::class,'show'])->name('reports.show');
  Route::get('/reports/{report}/download',[ReportController::class,'download'])->name('reports.download');
  Route::post('/reports/{report}/approve',[ReportController::class,'approve'])->name('reports.approve');
 });
});
require __DIR__.'/admin.php';
require __DIR__.'/teacher.php';
require __DIR__.'/student.php';
