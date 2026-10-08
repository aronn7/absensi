<?php
namespace App\Services;
use App\Models\{Card,User};
use Endroid\QrCode\{QrCode,ErrorCorrectionLevel};
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
class QRCodeService {
 public function issue(User $user): Card {
  if($user->card)return $user->card;
  $token=bin2hex(random_bytes(32));$number='S20-'.strtoupper($user->role==='student'?'S':'G').'-'.str_pad((string)$user->id,7,'0',STR_PAD_LEFT);
  $qrPath='qr/'.bin2hex(random_bytes(16)).'.png';
  $result=(new PngWriter())->write(new QrCode(data:'SAD:'.$token,errorCorrectionLevel:ErrorCorrectionLevel::Medium,size:360,margin:16));
  Storage::disk('local')->put($qrPath,$result->getString());
  try{return Card::create(['user_id'=>$user->id,'number'=>$number,'qr_token'=>$token,'token_hash'=>hash('sha256',$token),'qr_path'=>$qrPath,'issued_at'=>now()]);}
  catch(\Throwable $e){Storage::disk('local')->delete($qrPath);throw $e;}
 }
 public function resolve(string $raw): User {
  if(!preg_match('/^SAD:([a-f0-9]{64})$/D',$raw,$matches))throw ValidationException::withMessages(['qr'=>'QR Code tidak valid.']);
  $card=Card::where('token_hash',hash('sha256',$matches[1]))->first();
  if(!$card)throw ValidationException::withMessages(['qr'=>'QR Code tidak valid.']);
  return $card->user;
 }
}
