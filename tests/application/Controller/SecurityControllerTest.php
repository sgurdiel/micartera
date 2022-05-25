<?php declare(strict_types=1);

namespace Tests\application\Controller;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use xVer\MiCartera\Infrastructure\Account\AccountRepositoryDoctrine;

/**
* @covers App\Controller\SecurityController
* @covers App\Form\RegistrationFormType
* @uses xVer\MiCartera\Application\Command\AddAccountCommand
* @uses xVer\MiCartera\Infrastructure\Currency\CurrencyRepositoryDoctrine
* @uses xVer\MiCartera\Domain\Account\Account
* @uses xVer\MiCartera\Domain\Currency\Currency
* @uses xVer\Bundle\DomainBundle\Domain\DomainException
* @uses xVer\Bundle\DomainBundle\Domain\TranslationVO
* @uses xVer\MiCartera\Infrastructure\Account\AccountRepositoryDoctrine
* @uses xVer\MiCartera\Infrastructure\Account\AccountRepositoryTrait
* @uses xVer\Component\PersistanceDoctrineComponent\Infrastructure\PersistanceDoctrine
*/
class SecurityControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private static AccountRepositoryDoctrine $repoAccount;

    public static function setUpBeforeClass(): void
    {
        // (1) boot the Symfony kernel
        self::bootKernel(['debug' => false]);

        // (2) use static::getContainer() to access the service container
        $container = static::getContainer();

        /** @var AccountRepositoryDoctrine */
        self::$repoAccount = $container->get(AccountRepositoryDoctrine::class);
    }

    public static function tearDownAfterClass(): void
    {
        $account = self::$repoAccount->findByIdentifier('test2@example.com');
        self::$repoAccount->emRemove($account);
        self::$repoAccount->emFlush();
    }

    public function testRegister(): void
    {
        static::ensureKernelShutdown();
        $this->client = static::createClient();

        $crawler = $this->client->request('GET', "/en_GB/register");

        // select the button
        $buttonCrawlerNode = $crawler->selectButton('cmdSubmit');

        // retrieve the Form object for the form belonging to this button
        $form = $buttonCrawlerNode->form();
        $formName = $form->getName();

        // set values on a form object
        $formFields = [
            $formName.'[email]' => 'test',
            $formName.'[plainPassword][first]' => 'password',
            $formName.'[plainPassword][second]' => 'password',
            $formName.'[currency]' => 'EUR',
            $formName.'[timezone]' => 'Europe/Madrid',
            $formName.'[agreeTerms]' => '1'
        ];

        // submit the Form object
        $this->client->submit($form, $formFields);
        $this->assertResponseIsUnprocessable();

        // set values on a form object
        $formFields = [
            $formName.'[email]' => 'test@example.com',
            $formName.'[plainPassword][first]' => 'password',
            $formName.'[plainPassword][second]' => 'password',
            $formName.'[currency]' => 'EUR',
            $formName.'[timezone]' => 'Europe/Madrid',
            $formName.'[agreeTerms]' => '1'
        ];

        // submit the Form object
        $this->client->submit($form, $formFields);
        $this->assertResponseIsUnprocessable();

        // set values on a form object
        $formFields = [
            $formName.'[email]' => 'test2@example.com',
            $formName.'[plainPassword][first]' => 'password',
            $formName.'[plainPassword][second]' => 'password',
            $formName.'[currency]' => 'EUR',
            $formName.'[timezone]' => 'Europe/Madrid',
            $formName.'[agreeTerms]' => '1'
        ];

        // submit the Form object
        $this->client->submit($form, $formFields);
        $this->assertResponseRedirects('/en_GB/login', Response::HTTP_FOUND);
    }
}