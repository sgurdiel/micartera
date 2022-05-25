<?php declare(strict_types=1);

namespace Tests\application\Controller;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Response;
use xVer\MiCartera\Domain\Stock\Stock;
use xVer\MiCartera\Domain\Transaction\Transaction;
use xVer\MiCartera\Infrastructure\AccountingMovement\AccountingMovementRepositoryDoctrine;
use xVer\MiCartera\Infrastructure\Stock\StockRepositoryDoctrine;
use xVer\MiCartera\Infrastructure\Transaction\TransactionRepositoryDoctrine;
use xVer\Symfony\Bundle\BaseAppBundle\Entity\AuthUser;
use xVer\Symfony\Bundle\BaseAppBundle\Security\Provider;

/**
* @covers App\Controller\TransactionController
* @covers App\Form\TransactionType
* @uses xVer\MiCartera\Application\Command\AddTransactionCommand
* @uses xVer\MiCartera\Domain\Account\Account
* @uses xVer\MiCartera\Domain\Currency\Currency
* @uses xVer\Bundle\DomainBundle\Domain\DomainException
* @uses xVer\MiCartera\Domain\MoneyVO
* @uses xVer\MiCartera\Domain\Stock\Stock
* @uses xVer\MiCartera\Domain\Stock\StockPriceVO
* @uses xVer\MiCartera\Domain\Transaction\Transaction
* @uses xVer\Bundle\DomainBundle\Domain\TranslationVO
* @uses xVer\MiCartera\Infrastructure\Account\AccountRepositoryDoctrine
* @uses xVer\MiCartera\Infrastructure\AccountingMovement\AccountingMovementFifoContract
* @uses xVer\MiCartera\Infrastructure\AccountingMovement\AccountingMovementRepositoryDoctrine
* @uses xVer\Component\PersistanceDoctrineComponent\Infrastructure\PersistanceDoctrine
* @uses xVer\MiCartera\Infrastructure\Stock\StockRepositoryDoctrine
* @uses xVer\MiCartera\Infrastructure\Transaction\TransactionRepositoryDoctrine
* @uses xVer\MiCartera\Infrastructure\Transaction\TransactionRepositoryTrait
* @uses xVer\MiCartera\Application\Command\UpdateTransactionCommand
* @uses xVer\MiCartera\Application\Command\RemoveTransactionCommand
* @uses xVer\Bundle\DomainBundle\Application\Query\QueryResponse
* @uses xVer\MiCartera\Application\Query\TransactionQuery
* @uses xVer\MiCartera\Domain\AccountingMovement\AccountingMovement
*/
class TransactionControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private static AuthUser $user;
    private static TransactionRepositoryDoctrine $repoTrans;
    private static AccountingMovementRepositoryDoctrine $repoAccountingMovement;
    private static string $buyTransactionId;
    private static string $sellTransactionId;
    private static Stock $stock;

    public static function setUpBeforeClass(): void
    {
        // (1) boot the Symfony kernel
        self::bootKernel(['debug' => false]);

        // (2) use static::getContainer() to access the service container
        $container = static::getContainer();

        $accountRepo = $container->get(Provider::class);
        self::$user = $accountRepo->loadUserByIdentifier('test@example.com');

        /** @var TransactionRepositoryDoctrine */
        self::$repoTrans = $container->get(TransactionRepositoryDoctrine::class);

        /** @var StockRepositoryDoctrine */
        $repoStock = $container->get(StockRepositoryDoctrine::class);
        self::$stock = $repoStock->findById('SAN');

        /** @var AccountingMovementRepositoryDoctrine */
        self::$repoAccountingMovement = $container->get(AccountingMovementRepositoryDoctrine::class);
    }

    public function setUp(): void
    {
        static::ensureKernelShutdown();
        $this->client = static::createClient();
    }

    public function testNew(): void
    {
        $this->client->loginUser(self::$user);
        $crawler = $this->client->request('GET', "/en_GB/transaction/new");

        // select the button
        $buttonCrawlerNode = $crawler->selectButton('cmdSubmit');

        // retrieve the Form object for the form belonging to this button
        $form = $buttonCrawlerNode->form();
        $formName = $form->getName();

        $dateTime = new \DateTime('yesterday', new \DateTimeZone('UTC'));
        // set values on a form object
        $formFields = [
            $formName.'[type]' => '0',
            $formName.'[stock]' => 'SAN',
            $formName.'[datetime]' => $dateTime->format('Y-m-d H:i:s'),
            $formName.'[amount]' => '100',
            $formName.'[price]' => '3.4566',
            $formName.'[expenses]' => '6.44'
        ];
        // submit the Form object
        $this->client->submit($form, $formFields);
        $this->assertResponseRedirects('/en_GB/transaction/', Response::HTTP_SEE_OTHER);

        $dateTime2 = clone $dateTime;
        // set values on a form object
        $formFields2 = [
            $formName.'[type]' => '1',
            $formName.'[stock]' => 'SAN',
            $formName.'[datetime]' => $dateTime2->add(new \DateInterval('PT30S'))->format('Y-m-d H:i:s'),
            $formName.'[amount]' => '10',
            $formName.'[price]' => '3.4566',
            $formName.'[expenses]' => '6.44'
        ];        
        // submit the Form object
        $this->client->submit($form, $formFields2);
        $this->assertResponseRedirects('/en_GB/transaction/', Response::HTTP_SEE_OTHER);

        // submit the Form object
        $this->client->submit($form, $formFields);
        $this->assertResponseIsUnprocessable();

        /** @var Transaction[] */
        $transactions = self::$repoTrans->findByStockId(self::$stock, 2, 0);
        foreach ($transactions as $transaction) {
            if (Transaction::TYPE_BUY === $transaction->getType()) {
                self::$buyTransactionId = $transaction->getId()->toRfc4122();
            } else {
                self::$sellTransactionId = $transaction->getId()->toRfc4122();
            }
        }
    }

    /**
     * @depends testNew
     */
    public function testEdit(): void
    {
        $this->client->loginUser(self::$user);
        $crawler = $this->client->request('GET', "/en_GB/transaction/".self::$buyTransactionId."/edit");

        // select the button
        $buttonCrawlerNode = $crawler->selectButton('cmdSubmit');

        // retrieve the Form object for the form belonging to this button
        $form = $buttonCrawlerNode->form();
        $formName = $form->getName();

        // set values on a form object
        $formFields = [
            $formName.'[price]' => '3.4566',
            $formName.'[expenses]' => '8.44'
        ];

        // submit the Form object
        $this->client->submit($form, $formFields);
        $this->assertResponseRedirects('/en_GB/transaction/', Response::HTTP_SEE_OTHER);
    }

    /**
     * @depends testEdit
     */
    public function testShow(): void
    {
        $crawler = $this->client->request('GET', "/en_GB/transaction/".self::$buyTransactionId);
        $this->assertResponseRedirects('http://localhost/en_GB/login', Response::HTTP_FOUND);

        $this->client->loginUser(self::$user);
        $crawler = $this->client->request('GET', "/en_GB/transaction/".self::$buyTransactionId);
        $this->assertResponseIsSuccessful();
    }

    /**
     * @depends testShow
     */
    public function testDelete(): void
    {
        $this->client->loginUser(self::$user);
        $crawler = $this->client->request('GET', "/en_GB/transaction/");

        // select the button
        $buttonCrawlerNode = $crawler->selectButton('cmdDelete_'.self::$buyTransactionId);

        // retrieve the Form object for the form belonging to this button
        $form = $buttonCrawlerNode->form();

        // submit the Form object
        $this->client->submit($form);
        $this->assertResponseIsUnprocessable();

        // select the button
        $buttonCrawlerNode = $crawler->selectButton('cmdDelete_'.self::$sellTransactionId);

        // retrieve the Form object for the form belonging to this button
        $form = $buttonCrawlerNode->form();

        // submit the Form object
        $this->client->submit($form);
        $this->assertResponseRedirects('/en_GB/transaction/', Response::HTTP_SEE_OTHER);

        // select the button
        $buttonCrawlerNode = $crawler->selectButton('cmdDelete_'.self::$buyTransactionId);

        // retrieve the Form object for the form belonging to this button
        $form = $buttonCrawlerNode->form();

        // submit the Form object
        $this->client->submit($form);
        $this->assertResponseRedirects('/en_GB/transaction/', Response::HTTP_SEE_OTHER);
    }

    /**
     * @depends testDelete
     */
    public function testFromCsv(): void
    {
        // Create temp file
        $filePath = '/tmp/micartera.csv';
        
        $this->client->loginUser(self::$user);
        $crawler = $this->client->request('GET', "/en_GB/transaction/fromcsv");

        // select the button
        $buttonCrawlerNode = $crawler->selectButton('form[upload]');

        // retrieve the Form object for the form belonging to this button
        $form = $buttonCrawlerNode->form();
        $formName = $form->getName();

        // set values on a form object
        $formFields = [
            $formName.'[csv]' => ''
        ];
        // submit the Form object
        $this->client->submit($form, $formFields);
        $this->assertResponseIsUnprocessable();

        // Test incorrect number of cols
        $fp = fopen($filePath, 'w+');
        fputs($fp, "date('Y-m-d H:i:s'),type,stock,price,amount");
        $file = new UploadedFile($filePath, 'micartera.csv', null, \UPLOAD_ERR_PARTIAL, true);
        // set values on a form object
        $formFields = [
            $formName.'[csv]' => $file
        ];
        // submit the Form object
        $this->client->submit($form, $formFields);
        $this->assertResponseIsUnprocessable();

        // Test stock not found
        $fp = fopen($filePath, 'w+');
        fputs($fp, "date('Y-m-d H:i:s'),type,stock,price,amount,expenses");
        $file = new UploadedFile($filePath, 'micartera.csv', null, \UPLOAD_ERR_PARTIAL, true);
        // set values on a form object
        $formFields = [
            $formName.'[csv]' => $file
        ];
        // submit the Form object
        $this->client->submit($form, $formFields);
        $this->assertResponseIsUnprocessable();

        // Test invalid price field
        $fp = fopen($filePath, 'w+');
        fputs($fp, "date('Y-m-d H:i:s'),type,".self::$stock->getId().",price,amount,expenses");
        $file = new UploadedFile($filePath, 'micartera.csv', null, \UPLOAD_ERR_PARTIAL, true);
        // set values on a form object
        $formFields = [
            $formName.'[csv]' => $file
        ];
        // submit the Form object
        $this->client->submit($form, $formFields);
        $this->assertResponseIsUnprocessable();

        // Test invalid date field
        $fp = fopen($filePath, 'w+');
        fputs($fp, "date('Y-m-d H:i:s'),type,".self::$stock->getId().",6.5543,amount,expenses");
        $file = new UploadedFile($filePath, 'micartera.csv', null, \UPLOAD_ERR_PARTIAL, true);
        // set values on a form object
        $formFields = [
            $formName.'[csv]' => $file
        ];
        // submit the Form object
        $this->client->submit($form, $formFields);
        $this->assertResponseIsUnprocessable();

        // Test invalid expenses field
        $fp = fopen($filePath, 'w+');
        $dateTime = new \DateTime('now', new \DateTimeZone('UTC'));
        fputs($fp, $dateTime->format('Y-m-d H:i:s').",type,".self::$stock->getId().",6.5543,amount,expenses");
        $file = new UploadedFile($filePath, 'micartera.csv', null, \UPLOAD_ERR_PARTIAL, true);
        // set values on a form object
        $formFields = [
            $formName.'[csv]' => $file
        ];
        // submit the Form object
        $this->client->submit($form, $formFields);
        $this->assertResponseIsUnprocessable();

        // Test invalid transaction field
        $fp = fopen($filePath, 'w+');
        $dateTime = new \DateTime('now', new \DateTimeZone('UTC'));
        fputs($fp, $dateTime->format('Y-m-d H:i:s').",type,".self::$stock->getId().",6.5543,amount,5.43");
        $file = new UploadedFile($filePath, 'micartera.csv', null, \UPLOAD_ERR_PARTIAL, true);
        // set values on a form object
        $formFields = [
            $formName.'[csv]' => $file
        ];
        // submit the Form object
        $this->client->submit($form, $formFields);
        $this->assertResponseIsUnprocessable();


        // Test valid transaction field
        $fp = fopen($filePath, 'w+');
        $dateTime = new \DateTime('now', new \DateTimeZone('UTC'));
        fputs($fp, $dateTime->format('Y-m-d H:i:s').",0,".self::$stock->getId().",6.5543,100,5.43");
        $file = new UploadedFile($filePath, 'micartera.csv', null, \UPLOAD_ERR_PARTIAL, true);
        // set values on a form object
        $formFields = [
            $formName.'[csv]' => $file
        ];
        // submit the Form object
        $this->client->submit($form, $formFields);
        $this->assertResponseRedirects('/en_GB/transaction/', Response::HTTP_SEE_OTHER);

        // Clean up
        $transactions = self::$repoTrans->findByStockId(self::$stock);
        foreach ($transactions as $transaction) {
            self::$repoTrans->remove($transaction, self::$repoAccountingMovement);
        }
    }
}