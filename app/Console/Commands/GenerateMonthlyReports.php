<?php
namespace App\Console\Commands;
use Illuminate\Console\Command;
use App\Models\SchoolClass;
use App\Services\ReportService;
use Carbon\Carbon;
class GenerateMonthlyReports extends Command {
 protected $signature='reports:monthly {--month= : YYYY-MM, default previous month}';
 protected $description='Buat laporan bulanan untuk pemeriksaan wali kelas';
 public function handle(ReportService $service): int {
  $raw=$this->option('month')?:today()->subMonthNoOverflow()->format('Y-m');if(!preg_match('/^20\d{2}-(0[1-9]|1[0-2])$/D',$raw)){$this->error('Bulan harus YYYY-MM.');return self::FAILURE;}
  $date=Carbon::createFromFormat('!Y-m',$raw);$count=0;
  SchoolClass::where('active',true)->each(function($class)use($service,$date,&$count){$service->generate($class,$date->year,$date->month);$count++;});$this->info($count.' laporan siap diperiksa.');return self::SUCCESS;
 }
}
