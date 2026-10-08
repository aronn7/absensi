<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\{StudentController,TeacherController,SchoolClassController,SettingsController};
use App\Http\Controllers\{DashboardController,AttendanceController,CalendarController};
Route::prefix('admin')->name('admin.')->middleware(['auth','active','admin'])->group(function(){
 Route::get('/dashboard',DashboardController::class)->name('dashboard');
 Route::resource('students',StudentController::class);
 Route::resource('teachers',TeacherController::class);
 Route::resource('classes',SchoolClassController::class)->parameters(['classes'=>'schoolClass'])->except('show');
 Route::get('/attendance',[AttendanceController::class,'history'])->name('attendance');
 Route::get('/settings',[SettingsController::class,'edit'])->name('settings.edit');
 Route::put('/settings',[SettingsController::class,'update'])->name('settings.update');
 Route::resource('calendar',CalendarController::class)->parameters(['calendar'=>'event'])->except('index','show');
 Route::get('/requests',function(\Illuminate\Http\Request $r){$rows=\App\Models\AbsenceRequest::with('user','schoolClass')->when($r->status,fn($q)=>$q->where('status',$r->status))->when($r->q,fn($q)=>$q->whereHas('user',fn($u)=>$u->where('name','like','%'.$r->q.'%')))->latest()->paginate(15)->withQueryString();return view('admin.requests',compact('rows'));})->name('requests');
});
