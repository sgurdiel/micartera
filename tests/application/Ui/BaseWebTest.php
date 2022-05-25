<?php declare(strict_types=1);

namespace Tests\application\Ui;

use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Route;
use xVer\MiCartera\Application\EntityObjectRepositoryLoader;
use xVer\MiCartera\Application\Query\Account\AccountQuery;
use xVer\Symfony\Bundle\BaseAppBundle\Ui\Entity\AuthUser;
use xVer\Symfony\Bundle\BaseAppBundle\Ui\Security\Provider;

/**
 * @covers xVer\MiCartera\Ui\Controller\AccountingController
 * @covers xVer\MiCartera\Ui\Controller\PortfolioController
 * @covers xVer\MiCartera\Ui\Controller\StockController
 * @covers xVer\MiCartera\Ui\Controller\SecurityController
 * @covers xVer\MiCartera\Ui\Form\StockType
 * @covers xVer\MiCartera\Ui\Form\RegistrationFormType
 * @uses xVer\MiCartera\Application\EntityObjectRepositoryLoader
 * @uses xVer\MiCartera\Application\Query\Account\AccountQuery
 * @uses xVer\MiCartera\Application\Query\Accounting\AccountingDTO
 * @uses xVer\MiCartera\Application\Query\Accounting\AccountingQuery
 * @uses xVer\MiCartera\Application\Query\Currency\CurrencyQuery
 * @uses xVer\MiCartera\Application\Query\Portfolio\PortfolioDTO
 * @uses xVer\MiCartera\Application\Query\Portfolio\PortfolioQuery
 * @uses xVer\MiCartera\Application\Query\Stock\StockQuery
 * @uses xVer\MiCartera\Domain\Account\Account
 * @uses xVer\MiCartera\Domain\Accounting\Movement
 * @uses xVer\MiCartera\Domain\Accounting\MovementsCollection
 * @uses xVer\MiCartera\Domain\Accounting\SummaryVO
 * @uses xVer\MiCartera\Domain\Currency\Currency
 * @uses xVer\MiCartera\Domain\Currency\CurrenciesCollection
 * @uses xVer\MiCartera\Domain\MoneyVO
 * @uses xVer\MiCartera\Domain\NumberOperation
 * @uses xVer\MiCartera\Domain\Portfolio\SummaryVO
 * @uses xVer\MiCartera\Domain\Stock\Stock
 * @uses xVer\MiCartera\Domain\Stock\StocksCollection
 * @uses xVer\MiCartera\Domain\Stock\StockPriceVO
 * @uses xVer\MiCartera\Domain\Stock\Transaction\Adquisition
 * @uses xVer\MiCartera\Domain\Stock\Transaction\AdquisitionsCollection
 * @uses xVer\MiCartera\Domain\Stock\Transaction\Liquidation
 * @uses xVer\MiCartera\Domain\Stock\Transaction\TransactionAbstract
 * @uses xVer\MiCartera\Infrastructure\Account\AccountRepositoryDoctrine
 * @uses xVer\MiCartera\Infrastructure\Accounting\MovementRepositoryDoctrine
 * @uses xVer\MiCartera\Infrastructure\Currency\CurrencyRepositoryDoctrine
 * @uses xVer\MiCartera\Infrastructure\Stock\StockRepositoryDoctrine
 * @uses xVer\MiCartera\Infrastructure\Stock\Transaction\AdquisitionRepositoryDoctrine
 * @uses xVer\MiCartera\Ui\Controller\StockOperateController
 * @uses xVer\MiCartera\Ui\Form\StockOperateImportType
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
        self::configurePages($router);
        self::$user = (
            new Provider(
                new AccountQuery(
                    EntityObjectRepositoryLoader::doctrine(
                        $container->get(ManagerRegistry::class)
                    )
                )
            )
        )->loadUserByIdentifier('test@example.com');
    }

    private static function skipRoute(Route $route): bool
    {
        return (
            false !== strpos($route->getPath(), '{id}')
            ||
            false !== strpos($route->getPath(), '{type}')
            ||
            false !== strpos($route->getPath(), '{stock}')
        );
    }

    private static function configurePages(Router $router): void
    {
        foreach ($router->getRouteCollection() as $controller_method_name => $route) {
            if ($route->getDefault('_controller') === 'xVer\MiCartera\Ui\Controller\SecurityController::index') {
                continue;
            }
            if (self::skipRoute($route)) {
                continue;
            }
            $locales = explode('|', $route->getRequirement('_locale'));
            foreach ($locales as $locale) {
                $parameters = ['_locale' => $locale];
                $redirect = false;
                if ($route->getDefault('_controller') === 'xVer\MiCartera\Ui\Controller\SecurityController::logout') {
                    $redirect = $router->generate('app_login', $parameters);
                }
                self::$pages[] = [
                    'page' => $router->generate($controller_method_name, $parameters),
                    'locale' => $locale,
                    'public' => (
                        in_array(
                            $route->getDefault('_controller'), 
                            [
                                'xVer\MiCartera\Ui\Controller\SecurityController::login', 
                                'xVer\MiCartera\Ui\Controller\SecurityController::register',
                                'xVer\MiCartera\Ui\Controller\SecurityController::termsConditions'
                            ]
                        )
                    ),
                    'redirect' => $redirect
                ];
            }
        }
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
        $this->assertResponseRedirects('/en_GB/portfolio', Response::HTTP_FOUND);

        $crawler = $this->client->request('GET', "/en_GB/login");
        $this->assertResponseRedirects('/en_GB/portfolio', Response::HTTP_FOUND);

        $crawler = $this->client->request('GET', "/en_GB/register");
        $this->assertResponseRedirects('/en_GB/portfolio', Response::HTTP_FOUND);
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