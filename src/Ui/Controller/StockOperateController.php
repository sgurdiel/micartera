<?php

namespace xVer\MiCartera\Ui\Controller;

use DateTime;
use Doctrine\Persistence\ManagerRegistry;
use DomainException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
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
    public function operate(
        Request $request,
        TranslatorInterface $translator,
        ManagerRegistry $managerRegistry
    ): Response {
        $formData = [
            'type' => match((string) $request->attributes->get('type')) {
                'purchase' => 0,
                'sell' => 1
            },
            'stock' => $request->attributes->get('stock')
        ];
        $request->isMethod('GET') ?
            $formData['refererPage'] = $request->headers->get('referer')
        :
            $formData['refererPage'] = (string) $request->request->all('stock_operate')['refererPage']
        ;
        $form = $this->createForm(StockOperateType::class, $formData);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $repoLoader = EntityObjectRepositoryLoader::doctrine($managerRegistry);
                $command = new StockOperateCommand(
                    $repoLoader
                );
                /** @psalm-var numeric-string */
                $price = $form->get('price')->getData();
                /** @psalm-var numeric-string */
                $expenses = $form->get('expenses')->getData();
                /** @psalm-var DateTime */
                $dateTime = $form->get('datetime')->getData();
                /** @psalm-suppress PossiblyNullReference */
                $userIdentifier = $this->getUser()->getUserIdentifier();
                $formData['type'] === 0 ?
                    $command->purchase(
                        (string) $request->attributes->get('stock'),
                        $dateTime,
                        (int) $form->get('amount')->getData(),
                        $price,
                        $expenses,
                        $userIdentifier
                    )
                :
                    $command->sell(
                        (string) $request->attributes->get('stock'),
                        $dateTime,
                        (int) $form->get('amount')->getData(),
                        $price,
                        $expenses,
                        $userIdentifier
                    )
                ;
                $this->addFlash('success', $translator->trans("actionCompletedSuccessfully"));
                return
                    $form->get('refererPage')->getData()
                    ? $this->redirect((string) $form->get('refererPage')->getData(), Response::HTTP_SEE_OTHER)
                    : $this->redirectToRoute('stockportfolio_index', [], Response::HTTP_SEE_OTHER);
            } catch (\DomainException $de) {
                $this->addFlash('error', $this->getTranslatedException($de, $translator)->getMessage());
            }
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
        $route = $type === 0 ? 'stockportfolio_index' : 'stockaccounting_index';
        if (false === $this->isCsrfTokenValid('delete' . $id, (string) $request->request->get('_token'))) {
            $this->addFlash('error', $translator->trans('invalidFormToken', [], 'validators'));
        } else {
            try {
                $command = new StockOperateCommand(
                    EntityObjectRepositoryLoader::doctrine($managerRegistry)
                );
                $type === 0 ?
                    $command->removePurchase($id)
                :
                    $command->removeSell($id)
                ;
                $this->addFlash('success', $translator->trans('actionCompletedSuccessfully'));
                return $this->redirectToRoute($route, [], Response::HTTP_SEE_OTHER);
            } catch (\DomainException $de) {
                $this->addFlash('error', $this->getTranslatedException($de, $translator)->getMessage());
            }
        }
        return
            $request->headers->get('referer') != ''
            ? $this->redirect((string) $request->headers->get('referer'), Response::HTTP_SEE_OTHER)
            : $this->redirectToRoute($route, [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/import', name: 'import', methods: ['GET','POST'])]
    public function fromcsv(
        Request $request,
        TranslatorInterface $translator,
        ManagerRegistry $managerRegistry
    ): Response {
        $form = $this->createForm(StockOperateImportType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            try {
                /** @var UploadedFile */
                $file = $form->get('csv')->getData();
                /**
                 * @psalm-suppress RedundantConditionGivenDocblockType
                 */
                if (
                    $file instanceof UploadedFile
                    && UPLOAD_ERR_OK === $file->getError()
                    && $file->isValid()
                    && $file->isFile()
                    && in_array($file->getMimeType(), ['text/csv','text/plain','application/csv'])
                    && false !== ($fp = fopen($file->getRealPath(), 'r'))
                ) {
                    /** @psalm-suppress PossiblyNullReference */
                    $userIdentifier = $this->getUser()->getUserIdentifier();
                    $command = new StockOperateCommand(
                        EntityObjectRepositoryLoader::doctrine($managerRegistry)
                    );
                    $lineNumber = 1;
                    while (false !== ($line = fgetcsv($fp))) {
                        $numCols = count($line);
                        if (6 != $numCols) {
                            throw new DomainException(
                                $translator->trans(
                                    'csvInvalidColumnCount',
                                    [
                                        'row' => $lineNumber,
                                        'expected' => 6,
                                        'got' => $numCols
                                    ],
                                    'validators'
                                )
                            );
                        }
                        /**
                         * @psalm-var array{
                         *  0: string,1: string,2: string,3: numeric-string,4: int,5: numeric-string
                         * } $line
                         */
                        $command->import($lineNumber, $line, $userIdentifier);
                        $lineNumber++;
                    }
                    fclose($fp);
                } else {
                    throw new DomainException($translator->trans('invalidUploadedFile', [], 'validators'));
                }
                $this->addFlash('success', $translator->trans("actionCompletedSuccessfully"));
                return $this->redirectToRoute('stock_list', [], Response::HTTP_SEE_OTHER);
            } catch (\DomainException $de) {
                $this->addFlash('error', $this->getTranslatedException($de, $translator)->getMessage());
            }
        }
        return $this->render('stock/form.html.twig', [
            'form' => $form,
            'title' => 'operationsbatchimport'
        ]);
    }
}
