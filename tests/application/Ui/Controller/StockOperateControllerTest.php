<?php declare(strict_types=1);

namespace Tests\application\Ui\Controller;

use DateTime;
use DateTimeZone;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Response;
use Tests\application\ApplicationTestCase;
use xVer\Bundle\DomainBundle\Domain\TranslationVO;
use xVer\MiCartera\Domain\Stock\Stock;
use xVer\MiCartera\Domain\Stock\Transaction\TransactionAmountVO;

/**
 * @covers xVer\MiCartera\Ui\Controller\StockOperateController
 * @covers xVer\MiCartera\Ui\Form\StockOperateType
 * @covers xVer\MiCartera\Ui\Form\StockOperateImportType
 * @uses xVer\MiCartera\Application\Query\Stock\Accounting\AccountingDTO
 * @uses xVer\MiCartera\Application\Query\Stock\Accounting\AccountingQuery
 * @uses xVer\MiCartera\Application\Query\Stock\StockQuery
 * @uses xVer\MiCartera\Application\Command\Stock\StockOperateCommand
 * @uses xVer\MiCartera\Application\EntityObjectRepositoryLoader
 * @uses xVer\MiCartera\Application\Query\Account\AccountQuery
 * @uses xVer\MiCartera\Application\Query\Stock\Portfolio\PortfolioDTO
 * @uses xVer\MiCartera\Application\Query\Stock\Portfolio\PortfolioQuery
 * @uses xVer\MiCartera\Domain\Account\Account
 * @uses xVer\MiCartera\Domain\Exchange\Exchange
 * @uses xVer\MiCartera\Domain\Currency\Currency
 * @uses xVer\MiCartera\Domain\MoneyVO
 * @uses xVer\MiCartera\Domain\Number\Number
 * @uses xVer\MiCartera\Domain\Number\NumberOperation
 * @uses xVer\MiCartera\Domain\Stock\Accounting\Movement
 * @uses xVer\MiCartera\Domain\Stock\Accounting\MovementsCollection
 * @uses xVer\MiCartera\Domain\Stock\Accounting\SummaryVO
 * @uses xVer\MiCartera\Domain\Stock\Accounting\SummaryDTO
 * @uses xVer\MiCartera\Domain\Stock\Portfolio\SummaryVO
 * @uses xVer\MiCartera\Domain\Stock\Stock
 * @uses xVer\MiCartera\Domain\Stock\StocksCollection
 * @uses xVer\MiCartera\Domain\Stock\StockPriceVO
 * @uses xVer\MiCartera\Domain\Stock\Transaction\Acquisition
 * @uses xVer\MiCartera\Domain\Stock\Transaction\AcquisitionsCollection
 * @uses xVer\MiCartera\Domain\Stock\Transaction\Criteria\FifoCriteria
 * @uses xVer\MiCartera\Domain\Stock\Transaction\Liquidation
 * @uses xVer\MiCartera\Domain\Stock\Transaction\LiquidationsCollection
 * @uses xVer\MiCartera\Domain\Stock\Transaction\TransactionAbstract
 * @uses xVer\MiCartera\Domain\Stock\Transaction\TransactionAmountOutstandingVO
 * @uses xVer\MiCartera\Domain\Stock\Transaction\TransactionAmountVO
 * @uses xVer\MiCartera\Infrastructure\Account\AccountRepositoryDoctrine
 * @uses xVer\MiCartera\Infrastructure\Stock\Accounting\MovementRepositoryDoctrine
 * @uses xVer\MiCartera\Infrastructure\EntityObjectRepositoryDoctrine
 * @uses xVer\MiCartera\Infrastructure\Exchange\ExchangeRepositoryDoctrine
 * @uses xVer\MiCartera\Infrastructure\Currency\CurrencyRepositoryDoctrine
 * @uses xVer\MiCartera\Infrastructure\Stock\StockRepositoryDoctrine
 * @uses xVer\MiCartera\Infrastructure\Stock\Transaction\AcquisitionRepositoryDoctrine
 * @uses xVer\MiCartera\Infrastructure\Stock\Transaction\LiquidationRepositoryDoctrine
 * @uses xVer\MiCartera\Ui\Controller\StockAccountingController
 * @uses xVer\MiCartera\Ui\Controller\StockPortfolioController
 * @uses xVer\MiCartera\Ui\Controller\StockController
 */
class StockOperateControllerTest extends ApplicationTestCase
{
    /** @depends testFromCsv */
    public function testAcquisition(): void
    {
        $this->client->loginUser(self::$user);
        $crawler = $this->client->request('GET', "/en_GB/stockoperate/purchase/SAN");

        // select the button
        $buttonCrawlerNode = $crawler->selectButton('stock_operate_cmdSubmit');

        // retrieve the Form object for the form belonging to this button
        $form = $buttonCrawlerNode->form();
        $formName = $form->getName();

        // set values on a form object
        $dateTime = new DateTime('yesterday', new DateTimeZone('UTC'));
        $formFields = [
            $formName.'[datetime]' => $dateTime->format('Y-m-d H:i:s'),
            $formName.'[amount]' => '100',            
            $formName.'[price]' => '3.4566',
            $formName.'[expenses]' => '6.44'
        ];

        // test new
        $this->client->submit($form, $formFields);
        $this->assertResponseRedirects('/en_GB/portfolio/stock', Response::HTTP_SEE_OTHER);
        $crawler = $this->client->followRedirect();
        $this->assertSelectorTextContains('.flash-success', self::$translator->trans('actionCompletedSuccessfully', [], 'messages'));

        // test domain exception
        $formFields[$formName.'[amount]'] = 0; 
        $this->client->submit($form, $formFields);
        $this->assertRouteSame('stockoperate_new');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('.flash-error', self::$translator->trans('numberBetween', ['minimum' => TransactionAmountVO::LOWEST_AMOUNT, 'maximum' => TransactionAmountVO::HIGHEST_AMOUNT], TranslationVO::DOMAIN_VALIDATORS));
    }

    /** @depends testAcquisition */
    public function testLiquidation(): void
    {
        $this->client->loginUser(self::$user);
        $crawler = $this->client->request('GET', "/en_GB/stockoperate/sell/SAN");

        // select the button
        $buttonCrawlerNode = $crawler->selectButton('stock_operate_cmdSubmit');

        // retrieve the Form object for the form belonging to this button
        $form = $buttonCrawlerNode->form();
        $formName = $form->getName();

        // set values on a form object
        $dateTime = new DateTime('30 minutes ago', new DateTimeZone('UTC'));
        $formFields = [
            $formName.'[datetime]' => $dateTime->format('Y-m-d H:i:s'),
            $formName.'[amount]' => '10',            
            $formName.'[price]' => '3.4566',
            $formName.'[expenses]' => '6.44'
        ];

        // test new
        $this->client->submit($form, $formFields);
        $this->assertResponseRedirects('/en_GB/portfolio/stock', Response::HTTP_SEE_OTHER);
        $crawler = $this->client->followRedirect();
        $this->assertSelectorTextContains('.flash-success', self::$translator->trans('actionCompletedSuccessfully', [], 'messages'));

        // test new with referer
        $formFields[$formName.'[datetime]'] = (new DateTime('29 minutes ago', new DateTimeZone('UTC')))->format('Y-m-d H:i:s');
        $referer = 'http://localhost/en_GB/portfolio/stock?page=3';
        $formFields[$formName.'[refererPage]'] = $referer;
        $this->client->submit($form, $formFields);
        $this->assertResponseRedirects($referer, Response::HTTP_SEE_OTHER);
        $crawler = $this->client->followRedirect();
        $this->assertSelectorTextContains('.flash-success', self::$translator->trans('actionCompletedSuccessfully', [], 'messages'));

        // test domain exception
        $formFields[$formName.'[amount]'] = 0; 
        $this->client->submit($form, $formFields);
        $this->assertRouteSame('stockoperate_new');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('.flash-error', self::$translator->trans('numberBetween', ['minimum' => TransactionAmountVO::LOWEST_AMOUNT, 'maximum' => TransactionAmountVO::HIGHEST_AMOUNT], TranslationVO::DOMAIN_VALIDATORS));
    }

    /** @depends testLiquidation */
    public function testDeleteAcquisitionThrowsDomainError(): void
    {
        $this->client->loginUser(self::$user);
        $crawler = $this->client->request('GET', '/en_GB/portfolio/stock');

        // select the button
        $buttonCrawlerNode = $crawler->selectButton('cmdDelete_1');

        // retrieve the Form object for the form belonging to this button
        $form = $buttonCrawlerNode->form();

        // submit the Form object
        $this->client->setServerParameter('HTTP_REFERER', '');
        $crawler = $this->client->submit($form);
        $this->assertResponseRedirects('/en_GB/portfolio/stock', Response::HTTP_SEE_OTHER);
        $crawler = $this->client->followRedirect();
        $this->assertSelectorTextContains('.flash-error', self::$translator->trans('transBuyCannotBeRemovedWithoutFullAmountOutstanding', [], 'validators'));
    }

    /** @depends testDeleteAcquisitionThrowsDomainError */
    public function testDeleteLiquidation(): void
    {
        $this->client->loginUser(self::$user);
        $crawler = $this->client->request('GET', '/en_GB/accounting/stock');

        // select the button
        $buttonCrawlerNode = $crawler->selectButton('cmdDelete_0');

        // retrieve the Form object for the form belonging to this button
        $form = $buttonCrawlerNode->form();

        // submit the Form object
        $this->client->setServerParameter('HTTP_REFERER', '');
        $crawler = $this->client->submit($form);
        $this->assertResponseRedirects('/en_GB/accounting/stock', Response::HTTP_SEE_OTHER);
        $crawler = $this->client->followRedirect();
        $this->assertSelectorTextContains('.flash-success', self::$translator->trans('actionCompletedSuccessfully', [], 'messages'));

        // submit the Form object to delete remaining liquidation
        // select the button
        $buttonCrawlerNode = $crawler->selectButton('cmdDelete_0');
        $form = $buttonCrawlerNode->form();
        $crawler = $this->client->submit($form);
        $this->assertResponseRedirects('/en_GB/accounting/stock', Response::HTTP_SEE_OTHER);
        $crawler = $this->client->followRedirect();
        $this->assertSelectorTextContains('.flash-success', self::$translator->trans('actionCompletedSuccessfully', [], 'messages'));

        // test invalid token
        $values = $form->getValues();
        $values['_token'] = 'BADTOKEN';
        $form->setValues($values);
        $referer = 'http://localhost/en_GB/accounting/stock?year='.(new DateTime('now', new DateTimeZone('UTC')))->format('Y');
        $this->client->setServerParameter('HTTP_REFERER', $referer);
        $crawler = $this->client->submit($form);
        $this->assertResponseRedirects($referer, Response::HTTP_SEE_OTHER);
        $crawler = $this->client->followRedirect();
        $this->assertSelectorTextContains('.flash-error', self::$translator->trans('invalidFormToken', [], 'validators'));

        // test invalid token and no referer
        $values = $form->getValues();
        $values['_token'] = 'BADTOKEN';
        $form->setValues($values);
        $this->client->setServerParameter('HTTP_REFERER', '');
        $crawler = $this->client->submit($form);
        $this->assertResponseRedirects('/en_GB/accounting/stock', Response::HTTP_SEE_OTHER);
        $crawler = $this->client->followRedirect();
        $this->assertSelectorTextContains('.flash-error', self::$translator->trans('invalidFormToken', [], 'validators'));
    }

    /** @depends testDeleteLiquidation */
    public function testDeleteAcquisition(): void
    {
        $this->client->loginUser(self::$user);
        $crawler = $this->client->request('GET', '/en_GB/portfolio/stock');

        // select the button
        $buttonCrawlerNode = $crawler->selectButton('cmdDelete_1');

        // retrieve the Form object for the form belonging to this button
        $form = $buttonCrawlerNode->form();

        // submit the Form object
        $this->client->setServerParameter('HTTP_REFERER', '');
        $crawler = $this->client->submit($form);
        $this->assertResponseRedirects('/en_GB/portfolio/stock', Response::HTTP_SEE_OTHER);
        $crawler = $this->client->followRedirect();
        $this->assertSelectorTextContains('.flash-success', self::$translator->trans('actionCompletedSuccessfully', [], 'messages'));

        // test invalid token
        $values = $form->getValues();
        $values['_token'] = 'BADTOKEN';
        $form->setValues($values);
        $this->client->setServerParameter('HTTP_REFERER', '');
        $crawler = $this->client->submit($form);
        $this->assertResponseRedirects('/en_GB/portfolio/stock', Response::HTTP_SEE_OTHER);
        $crawler = $this->client->followRedirect();
        $this->assertSelectorTextContains('.flash-error', self::$translator->trans('invalidFormToken', [], 'validators'));
    }

    public function testFromCsv(): void
    {
        self::$loadFixtures = true;

        // Create temp file
        $filePath = '/tmp/micartera.csv';
        
        $this->client->loginUser(self::$user);
        $crawler = $this->client->request('GET', '/en_GB/stockoperate/import');

        // select the button
        $buttonCrawlerNode = $crawler->selectButton('stock_operate_import[upload]');

        // retrieve the Form object for the form belonging to this button
        $form = $buttonCrawlerNode->form();
        $formName = $form->getName();

        // Test add acquisition and liquidation
        $fp = fopen($filePath, 'w+');
        $dateAcquisition = (new DateTime('30 mins ago', new DateTimeZone('UTC')))->format('Y-m-d H:i:s');
        $dateLiquidation = (new DateTime('20 mins ago', new DateTimeZone('UTC')))->format('Y-m-d H:i:s');
        $fileContent = $dateAcquisition.',acquisition,SAN,1,2,3'.PHP_EOL;
        $fileContent .= $dateLiquidation.',liquidation,SAN,1,2,3';
        fputs($fp, $fileContent);
        $file = new UploadedFile($filePath, 'micartera.csv', null, \UPLOAD_ERR_PARTIAL, true);
        $formFields = [
            $formName.'[csv]' => $file
        ];
        $this->client->submit($form, $formFields);
        $this->assertResponseRedirects('/en_GB/stock', Response::HTTP_SEE_OTHER);
        $crawler = $this->client->followRedirect();
        $this->assertSelectorTextContains('.flash-success', self::$translator->trans('actionCompletedSuccessfully', [], 'messages'));

        // Test file upload error
        $file = new UploadedFile('/tmp/nonexistent.csv', 'micartera.csv', null, \UPLOAD_ERR_PARTIAL, true);
        $formFields = [
            $formName.'[csv]' => $file
        ];
        $this->client->submit($form, $formFields);
        $this->assertSelectorTextContains('.flash-error', self::$translator->trans('invalidUploadedFile', [], 'validators'));

        // Test invalid column count exception
        $fp = fopen($filePath, 'w+');
        $dateLiquidation = (new DateTime('20 mins ago', new DateTimeZone('UTC')))->format('Y-m-d H:i:s');
        $fileContent = $dateLiquidation.',liquidation,SAN,1,2';
        fputs($fp, $fileContent);
        $file = new UploadedFile($filePath, 'micartera.csv', null, \UPLOAD_ERR_PARTIAL, true);
        $formFields = [
            $formName.'[csv]' => $file
        ];
        $this->client->submit($form, $formFields);
        $this->assertSelectorTextContains('.flash-error', self::$translator->trans('csvInvalidColumnCount', ['row' => 1, 'expected' => 6, 'got' => 5], 'validators'));

        // Test domain exception
        $fp = fopen($filePath, 'w+');
        $dateLiquidation = (new DateTime('20 mins ago', new DateTimeZone('UTC')))->format('Y-m-d H:i:s');
        $fileContent = $dateLiquidation.',liquidation,NOEXISTE,1,2,3';
        fputs($fp, $fileContent);
        $file = new UploadedFile($filePath, 'micartera.csv', null, \UPLOAD_ERR_PARTIAL, true);
        $formFields = [
            $formName.'[csv]' => $file
        ];
        $this->client->submit($form, $formFields);
        $this->assertSelectorTextContains('.flash-error', self::$translator->trans('expectedPersistedObjectNotFound', [], 'validators'));
    }
}
