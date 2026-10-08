<?php
namespace App\Console\Commands;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use App\Models\User;
class PrepareBrowserFixtures extends Command {
 protected $signature='qa:browser-fixtures';
 protected $description='Prepare development-only QR image and synthetic camera for browser tests';
 public function handle(): int {
  if(!app()->environment(['local','testing'])){$this->error('Local/testing only.');return self::FAILURE;}
  $card=User::where('email','murid1@sekolah.test')->first()?->card;
  if(!$card){$this->error('Run development seeder first.');return self::FAILURE;}
  $dir=base_path('.qa');if(!is_dir($dir))mkdir($dir,0755,true);$bytes=Storage::disk('local')->get($card->qr_path);file_put_contents($dir.'/demo-qr.png',$bytes);
  $source=imagecreatefromstring($bytes);$canvas=imagecreatetruecolor(640,480);imagefill($canvas,0,0,imagecolorallocate($canvas,255,255,255));imagecopyresized($canvas,$source,230,150,0,0,180,180,imagesx($source),imagesy($source));
  $y='';for($row=0;$row<480;$row++)for($col=0;$col<640;$col++){$rgb=imagecolorat($canvas,$col,$row);$grey=($rgb>>16)&255;$y.=chr((int)(16+219*$grey/255));}
  $frame=$y.str_repeat(chr(128),640*480/2);$handle=fopen($dir.'/demo-camera.y4m','wb');fwrite($handle,"YUV4MPEG2 W640 H480 F10:1 Ip A1:1 C420jpeg\n");for($i=0;$i<10;$i++)fwrite($handle,"FRAME\n".$frame);fclose($handle);imagedestroy($source);imagedestroy($canvas);$this->info('Fixtures ready in .qa. No physical camera is used.');return self::SUCCESS;
 }
}
