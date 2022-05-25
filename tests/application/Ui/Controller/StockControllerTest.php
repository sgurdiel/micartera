<?php declare(strict_types=1);

namespace Tests\application\Ui\Controller;

use Symfony\Component\HttpFoundation\Response;
use Tests\application\ApplicationTestCase;
use xVer\MiCartera\Domain\Stock\StockPriceVO;

/**
 * @covers xVer\MiCartera\Ui\Controller\StockController
 * @covers xVer\MiCartera\Ui\Form\StockType
 * @uses xVer\MiCartera\Application\Command\Stock\StockCommand
 * @uses xVer\MiCartera\Application\EntityObjectRepositoryLoader
 * @uses xVer\MiCartera\Application\Query\Account\AccountQuery
 * @uses xVer\MiCartera\Application\Query\Stock\StockQuery
 * @uses xVer\MiCartera\Domain\Account\Account
 * @uses xVer\MiCartera\Domain\Currency\Currency
 * @uses xVer\MiCartera\Domain\NumberOperation
 * @uses xVer\MiCartera\Domain\Stock\Stock
 * @uses xVer\MiCartera\Domain\Stock\StockPriceVO
 * @uses xVer\MiCartera\Domain\Stock\StocksCollection
 * @uses xVer\MiCartera\Domain\Stock\Transaction\AdquisitionsCollection
 * @uses xVer\MiCartera\Infrastructure\Account\AccountRepositoryDoctrine
 * @uses xVer\MiCartera\Infrastructure\EntityObjectRepositoryDoctrine
 * @uses xVer\MiCartera\Infrastructure\Stock\StockRepositoryDoctrine
 * @uses xVer\MiCartera\Infrastructure\Stock\Transaction\AdquisitionRepositoryDoctrine
 */
class StockControllerTest extends ApplicationTestCase
{
    public function testNew(): void
    {
        $this->client->loginUser(self::$user);
        $crawler = $this->client->request('GET', "/en_GB/stock/form-new");

        // select the button
        $buttonCrawlerNode = $crawler->selectButton('stock_cmdSubmit');

        // retrieve the Form object for the form belonging to this button
        $form = $buttonCrawlerNode->form();
        $formName = $form->getName();

        // set values on a form object
        $formFields = [
            $formName.'[code]' => 'ABCD',
            $formName.'[name]' => 'ABCD Name',
            $formName.'[price]' => '6.5467'
        ];

        // test new
        $this->client->submit($form, $formFields);
        $this->assertResponseRedirects('/en_GB/stock', Response::HTTP_SEE_OTHER);
        $crawler = $this->client->followRedirect();
        $this->assertSelectorTextContains('.flash-success', self::$translator->trans('actionCompletedSuccessfully', [], 'messages'));

        // test domain exception
        $this->client->submit($form, $formFields);
        $this->assertRouteSame('stock_new');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('.flash-error', self::$translator->trans('stockExists', [], 'validators'));
    }

    /**
     * @depends testNew
     */
    public function testUpdate(): void
    {
        $this->client->loginUser(self::$user);
        $crawler = $this->client->request('GET', "/en_GB/stock/ABCD");

        // select the button
        $buttonCrawlerNode = $crawler->selectButton('stock_cmdSubmit');

        // retrieve the Form object for the form belonging to this button
        $form = $buttonCrawlerNode->form();
        $formName = $form->getName();

        // set values on a form object
        $formFields = [
            $formName.'[name]' => 'ABCD New Name',
            $formName.'[price]' => '6.5467',
            $formName.'[refererPage]' => '/en_GB/stock?page=1'
        ];

        // submit the Form object
        $this->client->submit($form, $formFields);
        $this->assertResponseRedirects('/en_GB/stock?page=1', Response::HTTP_SEE_OTHER);
        $crawler = $this->client->followRedirect();
        $this->assertSelectorTextContains('.flash-success', self::$translator->trans('actionCompletedSuccessfully', [], 'messages'));

        // submit when no referer
        $formFields[$formName.'[refererPage]'] = '';
        $this->client->submit($form, $formFields);
        $this->assertResponseRedirects('/en_GB/stock', Response::HTTP_SEE_OTHER);
        $crawler = $this->client->followRedirect();
        $this->assertSelectorTextContains('.flash-success', self::$translator->trans('actionCompletedSuccessfully', [], 'messages'));

        // test domain exception
        $formFields[$formName.'[price]'] = '999999999';
        $this->client->submit($form, $formFields);
        $this->assertRouteSame('stock_update');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('.flash-error', self::$translator->trans('numberBetween', ['minimum' => '0', 'maximum' => StockPriceVO::HIGHEST_PRICE], 'validators'));
    }

    /**
     * @depends testUpdate
     */
    public function testDelete(): void
    {
        $this->client->loginUser(self::$user);
        $crawler = $this->client->request('GET', "/en_GB/stock");

        // select the button
        $buttonCrawlerNode = $crawler->selectButton('cmdDelete_ABCD');

        // retrieve the Form object for the form belonging to this button
        $form = $buttonCrawlerNode->form();

        // submit the Form object
        $crawler = $this->client->submit($form);
        $this->assertResponseRedirects('/en_GB/stock', Response::HTTP_SEE_OTHER);
        $crawler = $this->client->followRedirect();
        $this->assertSelectorTextContains('.flash-success', self::$translator->trans('actionCompletedSuccessfully', [], 'messages'));

        // test invalid token
        $values = $form->getValues();
        $values['_token'] = 'BADTOKEN';
        $form->setValues($values);
        $crawler = $this->client->submit($form);
        $this->assertResponseRedirects('http://localhost/en_GB/stock', Response::HTTP_SEE_OTHER);
        $crawler = $this->client->followRedirect();
        $this->assertSelectorTextContains('.flash-error', self::$translator->trans('invalidFormToken', [], 'validators'));

        // test invalid token and no referer
        $values = $form->getValues();
        $values['_token'] = 'BADTOKEN';
        $form->setValues($values);
        $this->client->setServerParameter('HTTP_REFERER', '');
        $crawler = $this->client->submit($form);
        $this->assertResponseRedirects('/en_GB/stock', Response::HTTP_SEE_OTHER);
        $crawler = $this->client->followRedirect();
        $this->assertSelectorTextContains('.flash-error', self::$translator->trans('invalidFormToken', [], 'validators'));

        // test domain exception
        $buttonCrawlerNode = $crawler->selectButton('cmdDelete_CABK');
        $form = $buttonCrawlerNode->form();
        $this->client->setServerParameter('HTTP_REFERER', '');
        $crawler = $this->client->submit($form);
        $this->assertResponseRedirects('/en_GB/stock', Response::HTTP_SEE_OTHER);
        $crawler = $this->client->followRedirect();
        $this->assertSelectorTextContains('.flash-error', self::$translator->trans('stockHasTransactions', [], 'validators'));
    }
}