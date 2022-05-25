<?php declare(strict_types=1);

namespace Tests\integration;

use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Input\StringInput;
use xVer\MiCartera\Application\EntityObjectRepositoryLoader;

class IntegrationTestCase extends KernelTestCase
{
    protected static ManagerRegistry $registry;
    protected static bool $loadFixtures = false;
    protected EntityObjectRepositoryLoader $repoLoader;

    public static function setUpBeforeClass(): void
    {
        // (1) boot the Symfony kernel
        self::bootKernel(['debug' => false]);
        
        // (2) use static::getContainer() to access the service container
        $container = static::getContainer();
        
        self::$registry = $container->get('doctrine');

        self::loadFixtures();
    }

    public static function runConsoleCommand($command): void
    {
        $application = new Application(
            static::createKernel(['debug' => false])
        );
        $application->setAutoExit(false);
        $command = sprintf('%s --quiet --env=test', $command);
        $application->run(new StringInput($command));
    }

    public function setUp(): void
    {
        if (self::$loadFixtures) {
            self::loadFixtures();
        }
        $this->resetEntityManager();
    }

    private static function loadFixtures(): void
    {
        self::runConsoleCommand('doctrine:fixtures:load');
        self::$loadFixtures = false;
    }

    protected function resetEntityManager(): void
    {
        self::$registry->resetManager();
        $this->repoLoader = EntityObjectRepositoryLoader::doctrine(self::$registry);
    }

    protected function detachEntity($entity): void
    {
        self::$registry->getManager()->detach($entity);
    }
}
