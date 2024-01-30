<?php

namespace App\Repository;

use App\Entity\Beer;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\Query\ResultSetMapping;
use Symfony\Component\Security\Core\Role\RoleHierarchyInterface;

/**
 * @extends ServiceEntityRepository<Beer>
 *
 * @method Beer|null find($id, $lockMode = null, $lockVersion = null)
 * @method Beer|null findOneBy(array $criteria, array $orderBy = null)
 * @method Beer[]    findAll()
 * @method Beer[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class BeerRepository extends ServiceEntityRepository
{
  private RoleHierarchyInterface $roleHierarchy;

  public function __construct(ManagerRegistry $registry, RoleHierarchyInterface $roleHierarchy)
  {
    parent::__construct($registry, Beer::class);
    $this->roleHierarchy = $roleHierarchy;
  }

  public function getBeersWithName(string $beerName)
  {
    return $this->createQueryBuilder('o')
      ->where('o.name LIKE :name')
      ->andWhere('o.deleted = FALSE')
      ->setParameter('name', '%' . $beerName . '%')
      ->getQuery()
      ->getResult();
  }

  public function getAllBeersByDateAdded(?int $limit = null, ?int $offset = null): array
  {
    $entityManager = $this->getEntityManager();
    $query = $entityManager->createQuery(
      'SELECT b FROM App\Entity\Beer b
            WHERE b.deleted = FALSE
            ORDER BY b.dateadded DESC'
    );

    $query = $query->setMaxResults($limit)->setFirstResult($offset);
    return $query->getResult();
  }

  public function getTotalNumBeers(): int
  {
    return $this->createQueryBuilder('o')
      ->select('COUNT(o.id) as numbeers')
      ->where('o.deleted = FALSE')
      ->getQuery()
      ->setMaxResults(1)->getOneOrNullResult()['numbeers'];
  }

  public function getContributorsTotalBeers(UserRepository $userRepository): array
  {
    $rsm = new ResultSetMapping();
    $rsm->addScalarResult('id', 'id');
    $rsm->addScalarResult('username', 'username');
    $rsm->addScalarResult('numbeers', 'numbeers', 'integer');
    $rsm->addScalarResult('numsignatures', 'numsignatures', 'integer');
    $rsm->addScalarResult('numcountries', 'numcountries', 'integer');


    $result = $this->getEntityManager()->createNativeQuery(
      'SELECT u.id as id, 
                u.username AS username, 
                IFNULL(b.numbeers, 0) AS numbeers, 
                IFNULL(b.numsignatures, 0) AS numsignatures,
                IFNULL(b.numcountries,0) AS numcountries
            FROM user u
                LEFT JOIN (SELECT 
                    COUNT(b1.id) numbeers, b1.user_id user_id, 
                    SUM(b1.issignature) numsignatures, 
                    COUNT(DISTINCT(b1.country)) numcountries
            FROM beer b1
                WHERE b1.deleted = false
                GROUP BY b1.user_id) b ON u.id = b.user_id
            ORDER BY numbeers DESC, numcountries DESC, numsignatures DESC, username ASC',
      $rsm
    )->getResult();

    $contributors = array();

    foreach ($result as $contributor) {
      $user = $userRepository->find($contributor['id']);
      $is_contributor = in_array(
        'ROLE_CONTRIBUTOR',
        $this->roleHierarchy->getReachableRoleNames($user->getRoles())
      );

      if ($is_contributor) {
        $contributors[] = $contributor;
      }
    }

    return $contributors;
  }

  public function findCountrySignature(string $country): ?Beer
  {
    return $this->findOneBy(array('country' => $country, 'issignature' => true));
  }

  public function getAllSignatureBeers(): array
  {
    return $this->findBy(
      array('deleted' => false, 'issignature' => true),
      array('country' => 'ASC')
    );
  }

  public function getCountiesBeerCount(): array
  {
    return $this->createQueryBuilder('o')
      ->select('COUNT(o.id) AS numbeers')
      ->where('o.deleted = FALSE')
      ->addSelect('o.country')
      ->groupBy('o.country')
      ->orderBy('numbeers', 'DESC')
      ->addOrderBy('o.country', 'ASC')
      ->getQuery()
      ->getResult();
  }

  public function getTotalBeersByDate(): array
  {
    $now = new \DateTime();
    $interval = new \DateInterval('PT24H');

    $rsm = new ResultSetMapping();
    $rsm->addScalarResult('numbeers', 'numbeers', 'integer');
    $rsm->addScalarResult('dateadded', 'dateadded', 'datetime');

    $sql_results = $this->getEntityManager()->createNativeQuery(
      'SELECT date_format(b.dateadded, "%Y-%m-%d 00:00:00") as dateadded, 
              COUNT(b.id) as numbeers
            FROM beer as b
            WHERE b.deleted = FALSE
            GROUP BY date_format(b.dateadded, "%Y-%m-%d");',
      $rsm
    )->getResult();

    $results = array();

    if (!count($sql_results)) {
      return $results;
    }

    for ($t = clone $sql_results[0]['dateadded']; $t < $now; $t->add($interval)) {

      $found = false;
      foreach ($sql_results as $value) {
        if ($value['dateadded']->format('Y-m-d 00:00') == $t->format('Y-m-d 00:00')) {
          $results[] = $value;
          $found = true;
          break;
        }
      }

      if ($found == false) {
        $result = array();
        $result['dateadded'] = clone $t;
        $result['numbeers'] = 0;
        $results[] = $result;
      }
    }

    return $results;
  }

  public function getAllBeersForUser(User $user): array
  {
    return $this->findBy(['user' => $user, 'deleted' => false], ['name' => 'ASC']);
  }

  public function getAllDeletedBeers(): array
  {
    return $this->findBy(['deleted' => true], ['dateadded' => 'DESC']);
  }

  public function getAllBeersForCountry($country)
  {
    return $this->findBy(
      ['country' => $country, 'deleted' => false],
      ['issignature' => 'DESC', 'name' => 'ASC']
    );
  }
}
