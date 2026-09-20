<?php
declare(strict_types=1);
namespace App\Modules\Property\Services;
final class PropertyCatalogSeedPackage { public function __construct(public string $key,public int $order,public string $content,public \Closure $apply){} public function checksum():string{return hash('sha256',$this->content);} }
