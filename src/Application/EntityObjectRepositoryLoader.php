<?php

namespace xVer\MiCartera\Application;

use Doctrine\Persistence\ManagerRegistry;
use InvalidArgumentException;
use xVer\Bundle\DomainBundle\Domain\EntityObjectRepositoryInterface;
use xVer\Bundle\DomainBundle\Domain\EntityObjectRepositoryLoaderInterface;

class EntityObjectRepositoryLoader implements EntityObjectRepositoryLoaderInterface
{
    final public const REPO_DOCTRINE = 1;

    private function __construct(
        private readonly int $repoType,
        private readonly ?ManagerRegistry $managerRegistry = null
    ) {
    }

    public static function doctrine(ManagerRegistry $managerRegistry = null): self
    {
        return new self(self::REPO_DOCTRINE, $managerRegistry);
    }

    /**
     * @template TRepo of EntityObjectRepositoryInterface
     *
     * @param class-string<TRepo> $repoInterface
     *
     * @return TRepo
     */
    public function load(string $repoInterface): EntityObjectRepositoryInterface
    {
        if (
            !interface_exists($repoInterface)
            || false === is_a($repoInterface, EntityObjectRepositoryInterface::class, true)
        ) {
            throw new InvalidArgumentException(
                sprintf('Non existent repository interface <%s>', $repoInterface)
            );
        }

        $repoConcreate = str_replace(
            ['Interface', 'Domain'],
            ['Doctrine','Infrastructure'],
            $repoInterface
        );

        if (
            !class_exists($repoConcreate)
            || !is_a($repoConcreate, $repoInterface, true)
            || !is_a($repoConcreate, EntityObjectRepositoryInterface::class, true)
        ) {
            throw new InvalidArgumentException(
                sprintf('Cannot instantiate non existent concrete repository <%s>', $repoConcreate)
            );
        }

        try {
            /**
             * @var EntityObjectRepositoryInterface|null
             * @psalm-var TRepo|null
             */
            $returnRepo = (new \ReflectionClass($repoConcreate))->newInstanceArgs([$this->managerRegistry]);
            // @codeCoverageIgnoreStart
        } catch (\ReflectionException $e) {
            throw new \ReflectionException(
                $e->getMessage(),
                $e->getCode(),
                $e
            );
        }
        if (is_null($returnRepo)) {
            throw new InvalidArgumentException(
                sprintf('Reflection failed for concrete repository <%s>', $repoConcreate)
            );
        }
        // @codeCoverageIgnoreEnd

        return $returnRepo;
    }
}
