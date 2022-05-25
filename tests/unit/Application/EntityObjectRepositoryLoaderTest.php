<?php declare(strict_types=1);

namespace Tests\unit\Application;

use Doctrine\Persistence\ManagerRegistry;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use xVer\Bundle\DomainBundle\Domain\EntityObjectRepositoryInterface;
use xVer\MiCartera\Application\EntityObjectRepositoryLoader;
use xVer\MiCartera\Domain\Account\AccountRepositoryInterface;

/**
 * @covers xVer\MiCartera\Application\EntityObjectRepositoryLoader
 * @uses xVer\MiCartera\Infrastructure\Account\AccountRepositoryDoctrine
 */
class EntityObjectRepositoryLoaderTest extends TestCase
{
    public function testCannotBeInstantiated(): void
    {
        $this->expectException(\Error::class);
        new EntityObjectRepositoryLoader(EntityObjectRepositoryLoader::REPO_DOCTRINE, null);
    }

    public function testNonExistentRepoInterfaceThrowsException(): void
    {
        $managerRegistry = $this->createStub(ManagerRegistry::class);
        $repoLoader = EntityObjectRepositoryLoader::doctrine($managerRegistry);
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Non existent repository interface <Tests\unit\Application\NonExistentRepositoryInterface>');
        $repoLoader->load(NonExistentRepositoryInterface::class);
    }

    public function testNonExistentConcreteRepoThrowsException(): void
    {
        $managerRegistry = $this->createStub(ManagerRegistry::class);
        $repoLoader = EntityObjectRepositoryLoader::doctrine($managerRegistry);
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot instantiate non existent concrete repository <xVer\Bundle\InfrastructureBundle\Infrastructure\EntityObjectRepositoryDoctrine>');
        $repoLoader->load(EntityObjectRepositoryInterface::class);
    }

    public function testRepositoryFactory(): void
    {
        $managerRegistry = $this->createStub(ManagerRegistry::class);
        $repoLoader = EntityObjectRepositoryLoader::doctrine($managerRegistry);
        $repo = $repoLoader->load(AccountRepositoryInterface::class);
        $this->assertInstanceOf(EntityObjectRepositoryInterface::class, $repo);
    }
}
