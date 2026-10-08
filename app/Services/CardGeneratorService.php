<?php
namespace App\Services;
use App\Models\{Card,AttendanceSetting};
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
class CardGeneratorService {
 public function data(Card $card): array {
  $card->load('user.student.schoolClass','user.teacher');$school=AttendanceSetting::current();
  $qr='data:image/png;base64,'.base64_encode(Storage::disk('local')->get($card->qr_path));
  $photo=$card->user->photo_path?'data:image/jpeg;base64,'.base64_encode(Storage::disk('public')->get($card->user->photo_path)):null;
  $logo=$school->logo_path?'data:image/jpeg;base64,'.base64_encode(Storage::disk('public')->get($school->logo_path)):null;
  return compact('card','school','qr','photo','logo');
 }
 public function download(Card $card){return Pdf::loadView('cards.pdf',$this->data($card))->setPaper('a4')->download($card->number.'.pdf');}
}
