<?php

namespace App\Repository;

use App\Entity\Todo;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Todo>
 */
class TodoRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Todo::class);
    }

    /**
     * @return list<Todo>
     */
    public function findAllOrdered(): array
    {
        return $this->createQueryBuilder('todo')
            ->orderBy('todo.isDone', 'ASC')
            ->addOrderBy('todo.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function countOpen(): int
    {
        return $this->count(['isDone' => false]);
    }

    public function countCompleted(): int
    {
        return $this->count(['isDone' => true]);
    }
}
