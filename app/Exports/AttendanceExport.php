<?php
namespace App\Exports;
use PhpOffice\PhpSpreadsheet\{Spreadsheet,Cell\DataType,Style\Fill};
class AttendanceExport {
 public function workbook(array $summary,array $details,array $requests,array $late): Spreadsheet {
  $book=new Spreadsheet();$book->removeSheetByIndex(0);
  foreach(['Ringkasan'=>[['NISN','Nama','Hadir','Terlambat','Total Menit Terlambat','Sakit','Izin','Alfa','Persentase Kehadiran','Hari Sekolah'],...$summary],'Detail Harian'=>[['Tanggal','NISN / ID','Nama','Status','Masuk','Pulang','Menit Terlambat'],...$details],'Izin & Sakit'=>[['Tanggal','Nama','Jenis','Alasan','Status','Pemeriksa'],...$requests],'Keterlambatan'=>[['Tanggal','NISN / ID','Nama','Status','Masuk','Pulang','Menit Terlambat'],...$late]] as $title=>$rows){
   $sheet=$book->createSheet();$sheet->setTitle($title);
   foreach($rows as $r=>$cells)foreach(array_values($cells) as $c=>$value){$cell=\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($c+1).($r+1);$sheet->setCellValueExplicit($cell,$value??'',is_int($value)||is_float($value)?DataType::TYPE_NUMERIC:DataType::TYPE_STRING);}
   $last=\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count($rows[0]));
   $sheet->getStyle('A1:'.$last.'1')->getFont()->setBold(true)->getColor()->setARGB('FFFFFFFF');$sheet->getStyle('A1:'.$last.'1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF086F63');
   $sheet->freezePane('C2');$sheet->setAutoFilter('A1:'.$last.count($rows));
   for($i=1;$i<=count($rows[0]);$i++)$sheet->getColumnDimension(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i))->setAutoSize(true);
  }
  $book->setActiveSheetIndex(0);return $book;
 }
}
