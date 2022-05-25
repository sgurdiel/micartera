<?php declare(strict_types=1);

namespace Tests\application\Controller;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use xVer\Symfony\Bundle\BaseAppBundle\Entity\AuthUser;
use xVer\Symfony\Bundle\BaseAppBundle\Security\Provider;

/**
* @covers App\Controller\StockController
* @covers App\Form\StockType
* @uses xVer\MiCartera\Application\Command\AddStockCommand
* @uses xVer\MiCartera\Application\Command\UpdateStockCommand
* @uses xVer\MiCartera\Domain\Account\Account
* @uses xVer\MiCartera\Domain\Currency\Currency
* @uses xVer\MiCartera\Domain\Stock\Stock
* @uses xVer\MiCartera\Domain\Stock\StockPriceVO
* @uses xVer\MiCartera\Infrastructure\Account\AccountRepositoryDoctrine
* @uses xVer\Component\PersistanceDoctrineComponent\Infrastructure\PersistanceDoctrine
* @uses xVer\MiCartera\Infrastructure\Stock\StockRepositoryDoctrine
* @uses xVer\MiCartera\Infrastructure\Stock\StockRepositoryTrait
* @uses xVer\Bundle\DomainBundle\Domain\DomainException
* @uses xVer\Bundle\DomainBundle\Domain\TranslationVO
* @uses xVer\MiCartera\Application\Command\RemoveStockCommand
* @uses xVer\Bundle\DomainBundle\Application\Query\QueryResponse
* @uses xVer\MiCartera\Application\Query\StockQuery
* @uses xVer\MiCartera\Infrastructure\Transaction\TransactionRepositoryDoctrine
*/
class StockControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private static AuthUser $user;

    public static function setUpBeforeClass(): void
    {
        // (1) boot the Symfony kernel
        self::bootKernel(['debug' => false]);

        // (2) use static::getContainer() to access the service container
        $container = static::getContainer();

        $accountRepo = $container->get(Provider::class);
        self::$user = $accountRepo->loadUserByIdentifier('test@example.com');
    }

    public function setUp(): void
    {
        static::ensureKernelShutdown();
        $this->client = static::createClient();
    }

    public function testNew(): void
    {
        $this->client->loginUser(self::$user);
        $crawler = $this->client->request('GET', "/en_GB/stock/new");

        // select the button
        $buttonCrawlerNode = $crawler->selectButton('cmdSubmit');

        // retrieve the Form object for the form belonging to this button
        $form = $buttonCrawlerNode->form();
        $formName = $form->getName();

        // set values on a form object
        $formFields = [
            $formName.'[code]' => 'ABCD',
            $formName.'[name]' => 'ABCD Name',
            $formName.'[price]' => '6.5467'
        ];

        // submit the Form object
        $this->client->submit($form, $formFields);

        $this->assertResponseRedirects('/en_GB/stock/', Response::HTTP_SEE_OTHER);

        // set values on a form object
        $formFields = [
            $formName.'[code]' => '',
            $formName.'[name]' => 'ABCD Name',
            $formName.'[price]' => '6.5467'
        ];

        // submit the Form object
        $this->client->submit($form, $formFields);
        
        $this->assertResponseIsUnprocessable();

        // set values on a form object
        $formFields = [
            $formName.'[code]' => 'CABK',
            $formName.'[name]' => 'ABCD Name',
            $formName.'[price]' => '6.5467'
        ];

        // submit the Form object
        $this->client->submit($form, $formFields);
        
        $this->assertResponseIsUnprocessable();
    }

    /**
     * @depends testNew
     */
    public function testEdit(): void
    {
        $this->client->loginUser(self::$user);
        $crawler = $this->client->request('GET', "/en_GB/stock/ABCD/edit");

        // select the button
        $buttonCrawlerNode = $crawler->selectButton('cmdSubmit');

        // retrieve the Form object for the form belonging to this button
        $form = $buttonCrawlerNode->form();
        $formName = $form->getName();

        // set values on a form object
        $formFields = [
            $formName.'[name]' => 'ABCD New Name',
            $formName.'[price]' => '6.5467'
        ];

        // submit the Form object
        $this->client->submit($form, $formFields);

        $this->assertResponseRedirects('/en_GB/stock/', Response::HTTP_SEE_OTHER);

        // set values on a form object
        $formFields = [
            $formName.'[name]' => '',
            $formName.'[price]' => '6.5467'
        ];

        // submit the Form object
        $this->client->submit($form, $formFields);
        
        $this->assertResponseIsUnprocessable();
    }

    /**
     * @depends testEdit
     */
    public function testShow(): void
    {
        $crawler = $this->client->request('GET', "/en_GB/stock/ABCD");
        $this->assertResponseRedirects('http://localhost/en_GB/login', Response::HTTP_FOUND);

        $this->client->loginUser(self::$user);
        $crawler = $this->client->request('GET', "/en_GB/stock/ABCD");
        $this->assertResponseIsSuccessful();
    }

    /**
     * @depends testShow
     */
    public function testDelete(): void
    {
        $this->client->loginUser(self::$user);
        $crawler = $this->client->request('GET', "/en_GB/stock/");

        // select the button
        $buttonCrawlerNode = $crawler->selectButton('cmdDelete_CABK');

        // retrieve the Form object for the form belonging to this button
        $form = $buttonCrawlerNode->form();

        // submit the Form object
        $this->client->submit($form); 
        $this->assertResponseIsUnprocessable();

        // select the button
        $buttonCrawlerNode = $crawler->selectButton('cmdDelete_ABCD');

        // retrieve the Form object for the form belonging to this button
        $form = $buttonCrawlerNode->form();

        // submit the Form object
        $this->client->submit($form);
        $this->assertResponseRedirects('/en_GB/stock/', Response::HTTP_SEE_OTHER);
    }
}