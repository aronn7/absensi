<?php
namespace App\Services;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
class UploadService {
 public function image(UploadedFile $file,string $folder,string $disk='local'): string {
  $image=@imagecreatefromstring($file->get());
  if(!$image)throw ValidationException::withMessages(['photo'=>'Gambar tidak dapat dibaca.']);
  ob_start();imagejpeg($image,null,85);$bytes=ob_get_clean();imagedestroy($image);
  $path=$folder.'/'.bin2hex(random_bytes(20)).'.jpg';Storage::disk($disk)->put($path,$bytes);return $path;
 }
}
