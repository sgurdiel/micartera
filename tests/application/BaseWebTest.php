<?php declare(strict_types=1);

namespace Tests\application;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use xVer\Symfony\Bundle\BaseAppBundle\Entity\AuthUser;
use xVer\Symfony\Bundle\BaseAppBundle\Security\Provider;

/**
* @covers App\Controller\AccountingController
* @covers App\Controller\PortfolioController
* @covers App\Controller\StockController
* @covers App\Controller\SecurityController
* @covers App\Controller\TransactionController
* @covers App\Form\StockType
* @covers App\Form\TransactionType
* @covers App\Form\RegistrationFormType
* @uses xVer\MiCartera\Domain\Account\Account
* @uses xVer\MiCartera\Domain\AccountingMovement\AccountingDTO
* @uses xVer\MiCartera\Domain\Currency\Currency
* @uses xVer\MiCartera\Domain\MoneyVO
* @uses xVer\MiCartera\Domain\NumberOperation
* @uses xVer\MiCartera\Infrastructure\Account\AccountRepositoryDoctrine
* @uses xVer\MiCartera\Infrastructure\AccountingMovement\AccountingMovementRepositoryDoctrine
* @uses xVer\Component\PersistanceDoctrineComponent\Infrastructure\PersistanceDoctrine
* @uses xVer\MiCartera\Application\Query\AccountingQuery
* @uses xVer\MiCartera\Application\Query\PortfolioQuery
* @uses xVer\Bundle\DomainBundle\Application\Query\QueryResponse
* @uses xVer\MiCartera\Application\Query\StockQuery
* @uses xVer\MiCartera\Application\Query\TransactionQuery
* @uses xVer\MiCartera\Domain\AccountingMovement\AccountingMovement
* @uses xVer\MiCartera\Domain\Stock\Stock
* @uses xVer\MiCartera\Domain\Stock\StockPriceVO
* @uses xVer\MiCartera\Domain\Transaction\PortfolioDTO
* @uses xVer\MiCartera\Domain\Transaction\Transaction
* @uses xVer\MiCartera\Infrastructure\Stock\StockRepositoryDoctrine
* @uses xVer\MiCartera\Infrastructure\Transaction\TransactionRepositoryDoctrine
* @uses xVer\MiCartera\Infrastructure\Currency\CurrencyRepositoryDoctrine
*/
class BaseWebTest extends WebTestCase
{
    private KernelBrowser $client;
    private static array $pages = [];
    private static AuthUser $user;

    public static function setUpBeforeClass(): void
    {
        // (1) boot the Symfony kernel
        self::bootKernel(['debug' => false]);

        // (2) use static::getContainer() to access the service container
        $container = static::getContainer();

        /** @var Router */
        $router = $container->get('router');
        foreach ($router->getRouteCollection() as $key => $route) {
            if ($route->getDefault('_controller') === 'App\Controller\SecurityController::index') {
                continue;
            }
            $locales = explode('|', $route->getRequirement('_locale'));
            foreach ($locales as $locale) {
                $parameters = [];
                $parameters['_locale'] = $locale;
                if (false !== strpos($route->getPath(), '{id}')) {
                    continue;
                }
                $redirect = false;
                if ($route->getDefault('_controller') === 'App\Controller\SecurityController::logout') {
                    $redirect = $router->generate('app_login', $parameters);
                }
                self::$pages[] = [
                    'page' => $router->generate($key, $parameters),
                    'locale' => $locale,
                    'public' => (
                        in_array(
                            $route->getDefault('_controller'), 
                            [
                                'App\Controller\SecurityController::login', 
                                'App\Controller\SecurityController::register',
                                'App\Controller\SecurityController::termsConditions'
                            ]
                        )
                    ),
                    'redirect' => $redirect
                ];
            }
        }

        $accountRepo = $container->get(Provider::class);
        self::$user = $accountRepo->loadUserByIdentifier('test@example.com');
    }

    public function setUp(): void
    {
        static::ensureKernelShutdown();
        $this->client = static::createClient();
    }

    public function testPagesRedirectToPortfolioWhenAccessedWhileLoggedIn(): void
    {
        $this->client->loginUser(self::$user);
        $crawler = $this->client->request('GET', "/");
        $this->assertResponseRedirects('/en_GB/portfolio/', Response::HTTP_FOUND);

        $crawler = $this->client->request('GET', "/en_GB/login");
        $this->assertResponseRedirects('/en_GB/portfolio/', Response::HTTP_FOUND);

        $crawler = $this->client->request('GET', "/en_GB/register");
        $this->assertResponseRedirects('/en_GB/portfolio/', Response::HTTP_FOUND);
    }

    public function testRedirectsToLoginIfNotAuthenticated(): void
    {
        foreach (self::$pages as $page) {
            if (!$page['public']) {
                $crawler = $this->client->request('GET', $page['page']);
                $this->assertResponseRedirects('http://localhost/'.$page['locale'].'/login', Response::HTTP_FOUND);
            }
        }
    }

    public function testNonPublicSuccessfulResponse(): void
    {
        $this->client->loginUser(self::$user);

        foreach (self::$pages as $page) {
            if (!$page['public'] && !$page['redirect']) {
                $crawler = $this->client->request('GET', $page['page']);
                $this->assertResponseIsSuccessful('Requested page: '.$page['page']);
            }
        }
    }

    public function testPublicSuccessfulResponse(): void
    {
        foreach (self::$pages as $page) {
            if ($page['public']) {
                $crawler = $this->client->request('GET', $page['page']);
                $this->assertResponseIsSuccessful();
            }
        }
    }

    public function testRedirectResponses(): void
    {
        foreach (self::$pages as $page) {
            if ($page['redirect']) {
                $crawler = $this->client->request('GET', $page['page']);
                $this->assertResponseRedirects('http://localhost'.$page['redirect'], Response::HTTP_FOUND);
            }
        }
    }
}