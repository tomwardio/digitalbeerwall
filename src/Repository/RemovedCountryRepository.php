<?php

namespace App\Repository;

use App\Entity\RemovedCountry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<RemovedCountry>
 *
 * @method RemovedCountry|null find($id, $lockMode = null, $lockVersion = null)
 * @method RemovedCountry|null findOneBy(array $criteria, array $orderBy = null)
 * @method RemovedCountry[]    findAll()
 * @method RemovedCountry[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class RemovedCountryRepository extends ServiceEntityRepository
{
  public function __construct(ManagerRegistry $registry)
  {
    parent::__construct($registry, RemovedCountry::class);
  }
}
