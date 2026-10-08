<?php
use Illuminate\Support\Facades\Schedule;
Schedule::command('attendance:mark-absent')->everyMinute()->withoutOverlapping();
Schedule::command('attendance:mark-absent --date='.today()->subDay()->toDateString())->dailyAt('00:05')->withoutOverlapping();
Schedule::command('reports:monthly --month='.today()->format('Y-m'))->dailyAt('23:59')->when(fn()=>today()->isLastOfMonth())->withoutOverlapping();
Schedule::command('reports:monthly')->monthlyOn(1,'00:15')->withoutOverlapping();
