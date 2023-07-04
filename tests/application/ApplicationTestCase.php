<?php declare(strict_types=1);

namespace Tests\application;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Contracts\Translation\TranslatorInterface;
use Tests\TestCaseTrait;
use xVer\MiCartera\Application\EntityObjectRepositoryLoader;
use xVer\MiCartera\Application\Query\Account\AccountQuery;
use xVer\Symfony\Bundle\BaseAppBundle\Ui\Entity\AuthUser;
use xVer\Symfony\Bundle\BaseAppBundle\Ui\Security\Provider;

class ApplicationTestCase extends WebTestCase
{
    use TestCaseTrait;

    protected KernelBrowser $client;
    protected static TranslatorInterface $translator;
    protected static AuthUser $user;

    public static function setUpBeforeClass(): void
    {
        self::_setUpBeforeClass();
        self::$translator = static::getContainer()->get(TranslatorInterface::class);
        self::$user = (new Provider(new AccountQuery(EntityObjectRepositoryLoader::doctrine(self::$registry))))->loadUserByIdentifier('test@example.com');
    }

    public function setUp(): void
    {
        $this->_setUp();
        static::ensureKernelShutdown();
        $this->client = static::createClient();   
    }
}
