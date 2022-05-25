<?php

namespace xVer\MiCartera\Ui\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Contracts\Translation\TranslatorInterface;
use xVer\MiCartera\Ui\Form\RegistrationFormType;
use xVer\Symfony\Bundle\BaseAppBundle\Ui\Controller\ExceptionTranslatorTrait;

class SecurityController extends AbstractController
{
    use ExceptionTranslatorTrait;

    #[Route("/", name: "main_index", methods: ["GET"])]
    public function index(): Response
    {
        return $this->redirectToRoute('portfolio_index');
    }

    #[Route("/{_locale<%app.locales%>}/login", name: "app_login")]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('portfolio_index');
        }

        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render(
            '@BaseApp/security/login.html.twig',
            [
                'last_username' => $lastUsername,
                'error' => $error,
                'formFooterLinks' => [
                    ['href' => $this->generateUrl('app_register'), 'text' => 'signUp']
                ]
            ],
            (is_null($error) ? null : new Response('', 401))
        );
    }

    /**
     * This method can be blank - it will be intercepted by the logout key on your firewall.
     * @codeCoverageIgnore
     */
    #[Route("/{_locale<%app.locales%>}/logout", name: "app_logout")]
    public function logout(): void
    {
    }

    #[Route("/{_locale<%app.locales%>}/register", name: "app_register")]
    public function register(
        Request $request,
        TranslatorInterface $translator
    ): Response {
        if ($this->getUser()) {
            return $this->redirectToRoute('portfolio_index');
        }

        $options = [
            'agreeTerms_label' => $translator->trans(
                'link_terms',
                [
                    'link_open' => sprintf(
                        '<a href="%s" target="_blank" class="termsLink">',
                        $this->generateUrl('terms_conditions')
                    ),
                    'link_close' => '</a>'
                ]
            )
        ];
        $form = $this->createForm(RegistrationFormType::class, [], $options);
        try {
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                $this->addFlash('success', $translator->trans("actionCompletedSuccessfully"));
                return $this->redirectToRoute('app_login', [], Response::HTTP_SEE_OTHER);
            }
        } catch (\DomainException $de) {
            $this->addFlash('error', $this->getTranslatedException($de, $translator)->getMessage());
        }
        return $this->render('@BaseApp/form/reusable_form.html.twig', [
            'form' => $form,
            'formTitle' => 'signUp',
            'formSubmit' => 'signUp',
            'formFooterLinks' => [
                ['href' => $this->generateUrl('app_login'), 'text' => 'signIn']
            ]
        ]);
    }

    #[Route("/{_locale<%app.locales%>}/terms-conditions", name: "terms_conditions", methods: ["GET"])]
    public function termsConditions(): Response
    {
        return $this->render('security/terms.html.twig');
    }
}
