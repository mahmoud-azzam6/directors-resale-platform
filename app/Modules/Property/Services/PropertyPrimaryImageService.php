<?php
declare(strict_types=1);
namespace App\Modules\Property\Services;

use App\Core\Database\DatabaseConnectionInterface;
use App\Exceptions\ValidationException;
use App\Http\UploadedFile;
use App\Modules\Property\Repositories\OrganizationPropertyRepository;
use RuntimeException;

/** A single private processed image; no media library or Profile revision mutation. */
final class PropertyPrimaryImageService
{
    public function __construct(private DatabaseConnectionInterface $database, private OrganizationPropertyRepository $properties) {}

    public function metadata(int $id, int $organization): ?array
    {
        if ($this->properties->findInOrganization($id, $organization) === null) $this->fail('PROPERTY_NOT_FOUND');
        $row = $this->row($id, $organization);
        return $row === null ? null : ['reference'=>$row['storage_key'], 'mime_type'=>'image/webp', 'width'=>(int)$row['width'], 'height'=>(int)$row['height'], 'byte_size'=>(int)$row['byte_size']];
    }

    public function read(int $id, int $organization): string
    {
        $image = $this->metadata($id, $organization);
        if ($image === null) $this->fail('IMAGE_NOT_FOUND');
        $bytes = @file_get_contents($this->path($image['reference']));
        if ($bytes === false) throw new RuntimeException('Primary image storage is unavailable.');
        return $bytes;
    }

    public function replace(int $id, int $organization, int $actor, UploadedFile $file): array
    {
        if ($this->properties->findInOrganization($id, $organization) === null) $this->fail('PROPERTY_NOT_FOUND');
        $processed = $this->process($file);
        $key = bin2hex(random_bytes(16));
        $path = $this->path($key);
        $stream = @fopen($path, 'xb');
        if ($stream === false) throw new RuntimeException('Private image storage is unavailable.');
        try { if (fwrite($stream, $processed['bytes']) !== strlen($processed['bytes'])) throw new RuntimeException('Image write failed.'); }
        catch (\Throwable $e) { fclose($stream); @unlink($path); throw $e; }
        fclose($stream);
        @chmod($path, 0600);
        try {
            $old = $this->database->transaction(function () use ($id,$organization,$actor,$key,$processed): ?array {
                $property = $this->properties->findForUpdate($id,$organization);
                if ($property === null) $this->fail('PROPERTY_NOT_FOUND');
                if ($property['status'] === 'archived') $this->fail('PROPERTY_ARCHIVED');
                $old = $this->row($id,$organization);
                if ($old === null) {
                    $s=$this->database->connection()->prepare('INSERT INTO property_primary_images (organization_property_id,organization_id,storage_key,width,height,byte_size,updated_by_user_id) VALUES (?,?,?,?,?,?,?)');
                    $s->execute([$id,$organization,$key,$processed['width'],$processed['height'],strlen($processed['bytes']),$actor]);
                } else {
                    $s=$this->database->connection()->prepare('UPDATE property_primary_images SET storage_key=?,width=?,height=?,byte_size=?,updated_by_user_id=? WHERE organization_property_id=? AND organization_id=?');
                    $s->execute([$key,$processed['width'],$processed['height'],strlen($processed['bytes']),$actor,$id,$organization]);
                }
                return $old;
            });
        } catch (\Throwable $e) { @unlink($path); throw $e; }
        if ($old !== null) $this->deleteStored($old['storage_key']);
        return $this->metadata($id,$organization);
    }

    public function remove(int $id, int $organization): void
    {
        $old=$this->database->transaction(function () use ($id,$organization): ?array {
            $property=$this->properties->findForUpdate($id,$organization);
            if ($property === null) $this->fail('PROPERTY_NOT_FOUND');
            if ($property['status'] === 'archived') $this->fail('PROPERTY_ARCHIVED');
            $old=$this->row($id,$organization);
            $s=$this->database->connection()->prepare('DELETE FROM property_primary_images WHERE organization_property_id=? AND organization_id=?');
            $s->execute([$id,$organization]);
            return $old;
        });
        if ($old !== null) $this->deleteStored($old['storage_key']);
    }

    private function row(int $id,int $organization): ?array
    {
        $s=$this->database->connection()->prepare('SELECT * FROM property_primary_images WHERE organization_property_id=? AND organization_id=?');
        $s->execute([$id,$organization]);
        return $s->fetch(\PDO::FETCH_ASSOC) ?: null;
    }

    private function path(string $key): string
    {
        if (!preg_match('/^[a-f0-9]{32}$/D',$key)) throw new RuntimeException('Invalid internal image reference.');
        // Default is outside both repository and XAMPP htdocs. Never serve files statically.
        $root=$_ENV['PROPERTY_IMAGE_STORAGE_PATH'] ?? dirname(__DIR__,6).'/private/directors-resale-platform/property-images';
        $repository=realpath(dirname(__DIR__,4));
        $documentRoot=realpath($_SERVER['DOCUMENT_ROOT'] ?? dirname(__DIR__,5));
        $logical=str_replace('\\','/',$root);
        if (!preg_match('~^(?:[A-Za-z]:/|/)~',$logical) || preg_match('~(?:^|/)\.{1,2}(?:/|$)~',$logical)) throw new RuntimeException('Private image storage requires an absolute path without traversal.');
        foreach ([$repository,$documentRoot] as $unsafe) if ($unsafe && str_starts_with(strtolower(rtrim($logical,'/').'/'),strtolower(str_replace('\\','/',$unsafe).'/'))) throw new RuntimeException('Image storage must be outside repository and document root.');
        if (!is_dir($root) && !@mkdir($root,0700,true) && !is_dir($root)) throw new RuntimeException('Private image storage cannot be created.');
        $resolved=realpath($root);
        if ($resolved === false) throw new RuntimeException('Private image storage cannot be resolved.');
        foreach ([$repository,$documentRoot] as $unsafe) if ($unsafe && str_starts_with(strtolower(str_replace('\\','/',$resolved).'/'),strtolower(str_replace('\\','/',$unsafe).'/'))) throw new RuntimeException('Image storage must be outside repository and document root.');
        return $resolved.DIRECTORY_SEPARATOR.$key.'.webp';
    }

    private function deleteStored(string $key): void
    {
        $path=$this->path($key);
        if (is_file($path) && !@unlink($path)) error_log('Primary image orphan cleanup failed for internal reference '.$key);
    }

    private function process(UploadedFile $file): array
    {
        if (!$file->isValid() || !is_file($file->temporaryPath()) || $file->size() < 1 || $file->size() > 10485760) $this->fail('INVALID_IMAGE_SIZE');
        $bytes=file_get_contents($file->temporaryPath());
        if ($bytes === false || strlen($bytes) !== $file->size()) $this->fail('INVALID_IMAGE_SIZE');
        $mime=(new \finfo(FILEINFO_MIME_TYPE))->buffer($bytes);
        if (!in_array($mime,['image/jpeg','image/png','image/webp'],true)) $this->fail('INVALID_IMAGE_TYPE');
        $size=@getimagesizefromstring($bytes);
        if (!$size || $size[0]<600 || $size[1]<600 || $size[0]>8192 || $size[1]>8192 || $size[0]*$size[1]>4000000) $this->fail('INVALID_IMAGE_DIMENSIONS');
        // Animated WebP/APNG are outside the single static image contract.
        if (($mime==='image/webp' && str_contains($bytes,'ANIM')) || ($mime==='image/png' && str_contains($bytes,'acTL'))) $this->fail('ANIMATED_IMAGE_UNSUPPORTED');
        if (!extension_loaded('gd') || !function_exists('imagewebp')) throw new RuntimeException('GD/WebP runtime is required.');
        $image=@imagecreatefromstring($bytes);
        if ($image===false) $this->fail('INVALID_IMAGE_CONTENT');
        try {
            if ($mime==='image/jpeg') {
                if (!function_exists('exif_read_data')) throw new RuntimeException('EXIF runtime is required for JPEG orientation.');
                $exif=@exif_read_data($file->temporaryPath());
                $orientation=(int)($exif['Orientation'] ?? 1);
                if (in_array($orientation,[2,4,5,7],true)) imageflip($image,IMG_FLIP_HORIZONTAL);
                $angle=match($orientation) { 3,4=>180,5,6=>-90,7,8=>90,default=>0 };
                if ($angle!==0) { $rotated=imagerotate($image,$angle,0); imagedestroy($image); $image=$rotated; }
            }
            imagepalettetotruecolor($image);
            imagealphablending($image,false);
            imagesavealpha($image,true);
            ob_start();
            try { if (!imagewebp($image,null,85)) throw new RuntimeException('WebP encoding failed.'); $output=ob_get_contents(); }
            finally { ob_end_clean(); }
            $verified=@imagecreatefromstring($output);
            if ($verified===false || strlen($output)>10485760) $this->fail('INVALID_PROCESSED_IMAGE');
            $result=['bytes'=>$output,'width'=>imagesx($verified),'height'=>imagesy($verified)];
            imagedestroy($verified);
            return $result;
        } finally { imagedestroy($image); }
    }

    private function fail(string $code): never { throw new ValidationException([$code=>$code]); }
}
