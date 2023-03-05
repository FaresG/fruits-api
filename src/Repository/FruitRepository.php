<?php

namespace App\Repository;

use App\Data\FilterData;
use App\Entity\Fruit;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Knp\Component\Pager\Pagination\PaginationInterface;
use Knp\Component\Pager\PaginatorInterface;

/**
 * @extends ServiceEntityRepository<Fruit>
 *
 * @method Fruit|null find($id, $lockMode = null, $lockVersion = null)
 * @method Fruit|null findOneBy(array $criteria, array $orderBy = null)
 * @method Fruit[]    findAll()
 * @method Fruit[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FruitRepository extends ServiceEntityRepository
{
    private PaginatorInterface $paginator;

    public function __construct(ManagerRegistry $registry, PaginatorInterface $paginator)
    {
        parent::__construct($registry, Fruit::class);
        $this->paginator = $paginator;
    }

    public function save(Fruit $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Fruit $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findFilter(FilterData $filterData): PaginationInterface
    {
        $query = $this->createQueryBuilder('f');

        if (!empty($filterData->q))
        {
            $query = $query->andWhere('f.name LIKE :q')
                ->orWhere('f.family LIKE :q')
                ->setParameter('q', "%{$filterData->q}%");
        }
        $query->getQuery();
        return $this->paginator->paginate(
            $query,
            $filterData->page,
            10
        );
    }

    public function findFavorite(int $pageNumber): PaginationInterface
    {
        $query = $this->createQueryBuilder('f');
        $query = $query->andWhere('f.favorite = true');
        $query->getQuery();

        return $this->paginator->paginate(
            $query,
            $pageNumber,
            10
        );
    }
}
