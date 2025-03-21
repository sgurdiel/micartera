<?php

namespace xVer\MiCartera\Domain\Exchange;

use xVer\Bundle\DomainBundle\Domain\EntityObjectRepositoryInterface;
use xVer\MiCartera\Domain\Exchange\Exchange;

interface ExchangeRepositoryInterface extends EntityObjectRepositoryInterface
{
    public function persist(Exchange $exchange): Exchange;

    public function findById(string $code): ?Exchange;

    public function findByIdOrThrowException(string $id): Exchange;

    public function all(): ExchangesCollection;
}
