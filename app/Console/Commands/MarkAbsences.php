<?php
namespace App\Console\Commands;
use Illuminate\Console\Command;
use App\Services\AttendanceService;
use Carbon\Carbon;
class MarkAbsences extends Command {
 protected $signature='attendance:mark-absent {--date= : YYYY-MM-DD, default today}';
 protected $description='Catat Alfa setelah penutupan absensi pada hari sekolah';
 public function handle(AttendanceService $service): int {
  $raw=$this->option('date')?:today()->toDateString();if(!Carbon::canBeCreatedFromFormat($raw,'Y-m-d')){$this->error('Tanggal harus YYYY-MM-DD.');return self::FAILURE;}
  $this->info($service->markAbsent(Carbon::parse($raw)).' catatan Alfa dibuat.');return self::SUCCESS;
 }
}
