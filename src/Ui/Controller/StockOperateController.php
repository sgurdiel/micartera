<?php

namespace xVer\MiCartera\Ui\Controller;

use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use xVer\MiCartera\Application\Command\Stock\StockOperateCommand;
use xVer\MiCartera\Application\EntityObjectRepositoryLoader;
use xVer\MiCartera\Ui\Form\StockOperateImportType;
use xVer\MiCartera\Ui\Form\StockOperateType;
use xVer\Symfony\Bundle\BaseAppBundle\Ui\Controller\ExceptionTranslatorTrait;

#[Route('/{_locale<%app.locales%>}/stockoperate', name: 'stockoperate_')]
class StockOperateController extends AbstractController
{
    use ExceptionTranslatorTrait;

    #[Route('/{type}/{stock}', name: 'new', methods: ['GET','POST'])]
    public function operate(Request $request, TranslatorInterface $translator): Response
    {
        $formData = [
            'type' => match((string) $request->attributes->get('type')) {
                'purchase' => 0,
                'sell' => 1
            },
            'stock' => $request->attributes->get('stock')
        ];
        if ($request->isMethod('GET')) {
            $formData['refererPage'] = $request->headers->get('referer');
        } else {
            $formData['refererPage'] = (string) $request->request->all('stock_operate')['refererPage'];
        }
        $form = $this->createForm(StockOperateType::class, $formData);
        try {
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                $this->addFlash('success', $translator->trans("actionCompletedSuccessfully"));
                return
                    $form->get('refererPage')->getData()
                    ? $this->redirect((string) $form->get('refererPage')->getData(), Response::HTTP_SEE_OTHER)
                    : $this->redirectToRoute('portfolio_index', [], Response::HTTP_SEE_OTHER);
            }
        } catch (\DomainException $de) {
            $this->addFlash('error', $de->getMessage());
        }
        return $this->render('stock/form.html.twig', [
            'form' => $form,
            'title' => $request->attributes->get('type')
        ]);
    }

    #[Route('/{type}', name: 'delete', methods: ['DELETE'])]
    public function operationdelete(
        Request $request,
        TranslatorInterface $translator,
        ManagerRegistry $managerRegistry
    ): Response {
        $type = match((string) $request->attributes->get('type')) {
            'purchase' => 0,
            'sell' => 1
        };
        /** @psalm-var string */
        $id = $request->request->get('id');
        $route = $type === 0 ? 'portfolio_index' : 'accounting_index';
        if (false === $this->isCsrfTokenValid('delete' . $id, (string) $request->request->get('_token'))) {
            $this->addFlash('error', $translator->trans('invalidFormToken', [], 'validators'));
        } else {
            try {
                $command = new StockOperateCommand(
                    EntityObjectRepositoryLoader::doctrine($managerRegistry)
                );
                if ($type === 0) {
                    $command->removePurchase($id);
                } else {
                    $command->removeSell($id);
                }
                $this->addFlash('success', $translator->trans('actionCompletedSuccessfully'));
                return $this->redirectToRoute($route, [], Response::HTTP_SEE_OTHER);
            } catch (\DomainException $de) {
                $this->addFlash('error', $this->getTranslatedException($de, $translator)->getMessage());
            }
        }
        return
            $request->headers->get('referer')
            ? $this->redirect((string) $request->headers->get('referer'), Response::HTTP_SEE_OTHER)
            : $this->redirectToRoute($route, [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/import', name: 'import', methods: ['GET','POST'])]
    public function fromcsv(
        Request $request,
        TranslatorInterface $translator,
    ): Response {
        $form = $this->createForm(StockOperateImportType::class);
        try {
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                $this->addFlash('success', $translator->trans("actionCompletedSuccessfully"));
                return $this->redirectToRoute('stock_list', [], Response::HTTP_SEE_OTHER);
            }
        } catch (\DomainException $de) {
            $this->addFlash('error', $de->getMessage());
        }
        return $this->render('stock/form.html.twig', [
            'form' => $form,
            'title' => 'operationsbatchimport'
        ]);
    }
}
