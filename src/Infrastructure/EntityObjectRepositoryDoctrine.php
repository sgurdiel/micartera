<?php

namespace xVer\MiCartera\Infrastructure;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use xVer\Bundle\DomainBundle\Domain\EntityObjectInterface;
use xVer\Bundle\DomainBundle\Domain\EntityObjectRepositoryInterface;

/**
 * @template T of EntityObjectInterface
 * @template-extends ServiceEntityRepository<T>
 */
abstract class EntityObjectRepositoryDoctrine extends ServiceEntityRepository implements EntityObjectRepositoryInterface
{
    public function flush(): void
    {
        $this->_em->flush();
    }

    public function beginTransaction(): void
    {
        $this->_em->beginTransaction();
    }

    public function commit(): void
    {
        $this->_em->commit();
    }

    public function rollBack(): void
    {
        $this->_em->rollback();
    }
}
