<?php

namespace xVer\MiCartera\Ui\Controller;

use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use xVer\MiCartera\Application\Command\Stock\StockCommand;
use xVer\MiCartera\Application\EntityObjectRepositoryLoader;
use xVer\MiCartera\Application\Query\Stock\Portfolio\PortfolioQuery;
use xVer\MiCartera\Application\Query\Stock\StockQuery;
use xVer\MiCartera\Ui\Form\StockType;
use xVer\Symfony\Bundle\BaseAppBundle\Ui\Controller\ExceptionTranslatorTrait;

#[Route('/{_locale<%app.locales%>}/stock', name: 'stock_')]
class StockController extends AbstractController
{
    use ExceptionTranslatorTrait;

    #[Route('', name: 'list', methods: ['GET'])]
    public function index(Request $request, ManagerRegistry $managerRegistry): Response
    {
        /** @psalm-suppress PossiblyNullReference */
        $userIdentifier = $this->getUser()->getUserIdentifier();
        $query = new StockQuery(EntityObjectRepositoryLoader::doctrine($managerRegistry));
        $queryResponse = $query->byAccountsCurrency(
            $userIdentifier,
            10,
            (int) $request->query->get('page', 0)
        );
        return $this->render(
            'stock/index.html.twig',
            [
                'stocks' => $queryResponse,
                'currencySymbol' => $query->currencySymbol
            ]
        );
    }

    #[Route('/form-new', name: 'new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        TranslatorInterface $translator,
        ManagerRegistry $managerRegistry
    ): Response|RedirectResponse {
        $form = $this->createForm(StockType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $command = new StockCommand(
                    EntityObjectRepositoryLoader::doctrine($managerRegistry)
                );
                /** @psalm-var numeric-string */
                $price = $form->get('price')->getData();
                /** @psalm-suppress PossiblyNullReference */
                $userIdentifier = $this->getUser()->getUserIdentifier();
                $command->create(
                    (string) $form->get('code')->getData(),
                    (string) $form->get('name')->getData(),
                    $price,
                    $userIdentifier
                );
                $this->addFlash('success', $translator->trans("actionCompletedSuccessfully"));
                return $this->redirectToRoute('stock_list', [], Response::HTTP_SEE_OTHER);
            } catch (\DomainException $de) {
                $this->addFlash('error', $this->getTranslatedException($de, $translator)->getMessage());
            }
        }
        return $this->render('stock/form.html.twig', [
            'form' => $form,
            'title' => 'newStock'
        ]);
    }

    #[Route('/{id}', name: 'update', methods: ['GET', 'POST'])]
    public function update(
        Request $request,
        TranslatorInterface $translator,
        ManagerRegistry $managerRegistry
    ): Response|RedirectResponse {
        $request->isMethod('GET') ?
            $formData = [
                'code' => $request->attributes->get('id'),
                'refererPage' => $request->headers->get('referer')
            ]
        :
            $formData = [
                'code' => $request->attributes->get('id'),
                'refererPage' => $request->request->all('stock')['refererPage']
            ]
        ;
        $form = $this->createForm(StockType::class, $formData);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $command = new StockCommand(
                    EntityObjectRepositoryLoader::doctrine($managerRegistry)
                );
                /** @psalm-var numeric-string */
                $price = $form->get('price')->getData();
                $command->update((string) $formData['code'], (string) $form->get('name')->getData(), $price);
                $this->addFlash('success', $translator->trans("actionCompletedSuccessfully"));
                return
                    $form->get('refererPage')->getData()
                    ? $this->redirect((string) $form->get('refererPage')->getData(), Response::HTTP_SEE_OTHER)
                    : $this->redirectToRoute('stock_list', [], Response::HTTP_SEE_OTHER);
            } catch (\DomainException $de) {
                $this->addFlash('error', $this->getTranslatedException($de, $translator)->getMessage());
            }
        }

        /** @psalm-suppress PossiblyNullReference */
        $userIdentifier = $this->getUser()->getUserIdentifier();
        $query = new PortfolioQuery(EntityObjectRepositoryLoader::doctrine($managerRegistry));
        $summaryVO = $query->getStockPortfolioSummary(
            $userIdentifier,
            (string) $formData['code']
        );

        return $this->render('stock/form.html.twig', [
            'form' => $form,
            'title' => $translator->trans('editStock'),
            'summary' => $summaryVO
        ]);
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(
        Request $request,
        ManagerRegistry $managerRegistry,
        TranslatorInterface $translator
    ): Response|RedirectResponse {
        /** @psalm-var string */
        $id = $request->attributes->get('id');
        if (false === $this->isCsrfTokenValid('delete'.$id, (string) $request->request->get('_token'))) {
            $this->addFlash('error', $translator->trans('invalidFormToken', [], 'validators'));
        } else {
            try {
                $command = new StockCommand(EntityObjectRepositoryLoader::doctrine($managerRegistry));
                $command->delete($id);
                $this->addFlash('success', $translator->trans('actionCompletedSuccessfully'));
                return $this->redirectToRoute('stock_list', [], Response::HTTP_SEE_OTHER);
            } catch (\DomainException $de) {
                $this->addFlash('error', $this->getTranslatedException($de, $translator)->getMessage());
            }
        }
        return
            $request->headers->get('referer') != ''
            ? $this->redirect((string) $request->headers->get('referer'), Response::HTTP_SEE_OTHER)
            : $this->redirectToRoute('stock_list', [], Response::HTTP_SEE_OTHER);
    }
}
