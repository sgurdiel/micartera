<?php

namespace App\Controller;

use App\Form\RegistrationFormType;
use DateTimeZone;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Contracts\Translation\TranslatorInterface;
use xVer\MiCartera\Application\Command\AddAccountCommand;
use xVer\Bundle\DomainBundle\Domain\DomainException;
use xVer\MiCartera\Domain\Account\Account;
use xVer\MiCartera\Domain\Currency\Currency;
use xVer\MiCartera\Infrastructure\Account\AccountRepositoryDoctrine;
use xVer\MiCartera\Infrastructure\Currency\CurrencyRepositoryDoctrine;
use xVer\Symfony\Bundle\BaseAppBundle\Controller\DomainExceptionTrait;
use xVer\Symfony\Bundle\BaseAppBundle\Entity\AuthUser;

class SecurityController extends AbstractController
{
    use DomainExceptionTrait;

    /**
     * @Route("/", name="main_index", methods={"GET"})
     */
    public function index(): Response
    {
        return $this->redirectToRoute('portfolio_index');
    }

    /**
     * @Route("/{_locale<%app_locales%>}/login", name="app_login")
     */
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('portfolio_index');
        }

        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('@BaseApp/security/login.html.twig', ['last_username' => $lastUsername, 'error' => $error]);
    }

    /**
     * @Route("/{_locale<%app_locales%>}/logout", name="app_logout")
     * This method can be blank - it will be intercepted by the logout key on your firewall.
     */
    public function logout(): void
    {
    }

    /**
     * @Route("/{_locale<%app_locales%>}/register", name="app_register")
     * @phpstan-param AccountRepositoryDoctrine<\xVer\MiCartera\Domain\Account\Account> $accountRepo
     * @phpstan-param CurrencyRepositoryDoctrine<\xVer\MiCartera\Domain\Currency\Currency> $repoCurrency
     */
    public function register(
        Request $request, 
        AccountRepositoryDoctrine $accountRepo, 
        CurrencyRepositoryDoctrine $repoCurrency, 
        TranslatorInterface $translator,
        UserPasswordHasherInterface $passwordHasher
    ): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('portfolio_index');
        }

        $form = $this->createForm(RegistrationFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                /** @var Currency */
                $currency = $form->get('currency')->getData();
                /** @var DateTimeZone */
                $timezone = $form->get('timezone')->getData();
                $account = new Account(
                    (string) $form->get('email')->getData(),
                    '',
                    $currency,
                    $timezone
                );
                $hashedPassword = $passwordHasher->hashPassword(
                    new AuthUser($account),
                    (string) $form->get('plainPassword')->getData()
                );
                $account->setPassword($hashedPassword);
                $command = new AddAccountCommand();
                $command->execute($accountRepo, $repoCurrency, $account);
                return $this->redirectToRoute('app_login');
            } catch (DomainException $de) {
                $this->getDomainExceptionFormError($form, $de, $translator);
            }
        }

        return $this->renderForm('@BaseApp/security/register.html.twig', [
            'form' => $form,
        ]);
    }

    /**
     * @Route("/{_locale<%app_locales%>}/terms-conditions", name="terms_conditions", methods={"GET"})
     */
    public function termsConditions(): Response
    {
        return $this->render('security/terms.html.twig');
    }
}
