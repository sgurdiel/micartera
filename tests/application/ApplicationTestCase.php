<?php declare(strict_types=1);

namespace Tests\application;

use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Contracts\Translation\TranslatorInterface;
use xVer\MiCartera\Application\EntityObjectRepositoryLoader;
use xVer\MiCartera\Application\Query\Account\AccountQuery;
use xVer\Symfony\Bundle\BaseAppBundle\Ui\Entity\AuthUser;
use xVer\Symfony\Bundle\BaseAppBundle\Ui\Security\Provider;

class ApplicationTestCase extends WebTestCase
{
    protected static ManagerRegistry $registry;
    protected static bool $loadFixtures = false;
    protected EntityObjectRepositoryLoader $repoLoader;
    protected KernelBrowser $client;
    protected static AuthUser $user;
    protected static TranslatorInterface $translator;

    public static function setUpBeforeClass(): void
    {
        // (1) boot the Symfony kernel
        self::bootKernel(['debug' => false]);
        
        // (2) use static::getContainer() to access the service container
        $container = static::getContainer();
        
        self::$registry = $container->get('doctrine');

        self::$translator = $container->get(TranslatorInterface::class);

        self::$user = (new Provider(new AccountQuery(EntityObjectRepositoryLoader::doctrine(self::$registry))))->loadUserByIdentifier('test@example.com');

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
        static::ensureKernelShutdown();
        $this->client = static::createClient();

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
