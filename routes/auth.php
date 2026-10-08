<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
Route::middleware('guest')->group(function(){
 Route::get('/',[LoginController::class,'choose'])->name('login');
 Route::get('/login/{role}',[LoginController::class,'show'])->whereIn('role',['admin','teacher','student'])->name('login.show');
 Route::post('/login/{role}',[LoginController::class,'store'])->whereIn('role',['admin','teacher','student'])->middleware('throttle:15,1')->name('login.store');
});
Route::post('/logout',[LoginController::class,'destroy'])->middleware('auth')->name('logout');
