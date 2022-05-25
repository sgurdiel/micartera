<?php declare(strict_types=1);

namespace Tests\application\Ui\Controller;

use Symfony\Component\HttpFoundation\Response;
use Tests\application\ApplicationTestCase;

/**
 * @covers xVer\MiCartera\Ui\Controller\SecurityController
 * @covers xVer\MiCartera\Ui\Form\RegistrationFormType
 * @uses xVer\MiCartera\Application\Command\Account\AccountCommand
 * @uses xVer\MiCartera\Application\Query\Currency\CurrencyQuery
 * @uses xVer\MiCartera\Domain\Account\Account
 * @uses xVer\MiCartera\Domain\Currency\CurrenciesCollection
 * @uses xVer\MiCartera\Domain\Currency\Currency
 * @uses xVer\MiCartera\Infrastructure\Account\AccountRepositoryDoctrine
 * @uses xVer\MiCartera\Infrastructure\Currency\CurrencyRepositoryDoctrine
 * @uses xVer\MiCartera\Infrastructure\EntityObjectRepositoryDoctrine
 * @uses xVer\MiCartera\Application\EntityObjectRepositoryLoader
 */
class SecurityControllerTest extends ApplicationTestCase
{
    public function testRegister(): void
    {
        self::$loadFixtures = true;

        $crawler = $this->client->request('GET', "/en_GB/register");

        // select the button
        $buttonCrawlerNode = $crawler->selectButton('cmdSubmit');

        // retrieve the Form object for the form belonging to this button
        $form = $buttonCrawlerNode->form();
        $formName = $form->getName();

        // set values on a form object
        $formFields = [
            $formName.'[email]' => 'test2@example.com',
            $formName.'[plainPassword][first]' => 'password',
            $formName.'[plainPassword][second]' => 'password',
            $formName.'[currency]' => 'EUR',
            $formName.'[timezone]' => 'Europe/Madrid',
            $formName.'[agreeTerms]' => '1'
        ];

        // test new
        $this->client->submit($form, $formFields);
        $this->assertResponseRedirects('/en_GB/login', Response::HTTP_SEE_OTHER);
        $crawler = $this->client->followRedirect();
        $this->assertSelectorTextContains('.flash-success', self::$translator->trans('actionCompletedSuccessfully', [], 'messages'));

        // test domain exception
        $formFields = [
            $formName.'[email]' => 'test@example.com',
            $formName.'[plainPassword][first]' => 'password',
            $formName.'[plainPassword][second]' => 'password',
            $formName.'[currency]' => 'EUR',
            $formName.'[timezone]' => 'Europe/Madrid',
            $formName.'[agreeTerms]' => '1'
        ];
        $this->client->submit($form, $formFields);
        $this->assertRouteSame('app_register');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('.flash-error', self::$translator->trans('accountEmailExists', [], 'validators'));
    }
}