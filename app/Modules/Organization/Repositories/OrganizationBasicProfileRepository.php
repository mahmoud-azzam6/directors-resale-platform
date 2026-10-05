<?php
declare(strict_types=1);
namespace App\Modules\Organization\Repositories;
use App\Core\Database\QueryBuilderInterface;
final class OrganizationBasicProfileRepository {
 public function __construct(private QueryBuilderInterface $q){}
 public function find(int $organizationId):?array{return $this->q->table('organization_basic_profiles')->where('organization_id','=',$organizationId)->first();}
 public function create(array $data):array{$id=$this->q->table('organization_basic_profiles')->insert($data);return $this->findById($id)??throw new \RuntimeException('Organization basic profile missing.');}
 public function update(int $organizationId,array $data):?array{$this->q->table('organization_basic_profiles')->where('organization_id','=',$organizationId)->update($data);return $this->find($organizationId);}
 private function findById(int $id):?array{return $this->q->table('organization_basic_profiles')->where('id','=',$id)->first();}
}
