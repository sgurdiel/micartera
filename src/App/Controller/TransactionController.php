<?php

namespace App\Controller;

use xVer\MiCartera\Domain\Transaction\Transaction;
use App\Form\TransactionType;
use xVer\MiCartera\Infrastructure\Transaction\TransactionRepositoryDoctrine;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use xVer\MiCartera\Application\Command\AddTransactionCommand;
use xVer\MiCartera\Application\Command\RemoveTransactionCommand;
use xVer\MiCartera\Application\Command\UpdateTransactionCommand;
use xVer\MiCartera\Application\Query\TransactionQuery;
use xVer\Bundle\DomainBundle\Domain\DomainException;
use xVer\MiCartera\Domain\MoneyVO;
use xVer\MiCartera\Domain\Stock\StockPriceVO;
use xVer\Bundle\DomainBundle\Domain\TranslationVO;
use xVer\MiCartera\Domain\Account\Account;
use xVer\MiCartera\Infrastructure\AccountingMovement\AccountingMovementRepositoryDoctrine;
use xVer\MiCartera\Infrastructure\Stock\StockRepositoryDoctrine;
use xVer\Symfony\Bundle\BaseAppBundle\Controller\DomainExceptionTrait;
use xVer\Symfony\Bundle\BaseAppBundle\Entity\AuthUser;

/**
 * @Route("/{_locale<%app_locales%>}/transaction")
 */
class TransactionController extends AbstractController
{
    use DomainExceptionTrait;

    /**
     * @Route("/", name="transaction_index", methods={"GET"})
     * @phpstan-param TransactionRepositoryDoctrine<\xVer\MiCartera\Domain\Transaction\Transaction> $repo
     */
    public function index(Request $request, TransactionRepositoryDoctrine $repo): Response
    {
        /** @var AuthUser */
        $authUser = $this->getUser();
        /** @var Account */
        $account = $authUser->getAccount();
        $command = new TransactionQuery();
        $queryVO = $command->execute(
            $repo,
            $account,
            'datetimeutc',
            'DESC',
            20,
            (int) $request->query->get("page", 0)
        );
        return $this->render('transaction/index.html.twig', ["transactions" => $queryVO]);
    }

    /**
     * @Route("/new", name="transaction_new", methods={"GET","POST"})
     * @phpstan-param TransactionRepositoryDoctrine<\xVer\MiCartera\Domain\Transaction\Transaction> $repo
     * @phpstan-param AccountingMovementRepositoryDoctrine<\xVer\MiCartera\Domain\AccountingMovement\AccountingMovement> $accountingMovementRepo
     */
    public function new(Request $request, TransactionRepositoryDoctrine $repo, TranslatorInterface $translator, AccountingMovementRepositoryDoctrine $accountingMovementRepo): Response
    {
        $form = $this->createForm(TransactionType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                /** @var Transaction */
                $transaction = $form->getData();
                $command = new AddTransactionCommand();
                $command->execute($repo, $transaction, $accountingMovementRepo);
                return $this->redirectToRoute('transaction_index', [], Response::HTTP_SEE_OTHER);
            } catch (DomainException $de) {
                $this->getDomainExceptionFormError($form, $de, $translator);
            }
        }

        return $this->renderForm('transaction/new.html.twig', [
            'form' => $form,
        ]);
    }

    /**
     * @Route("/fromcsv", name="transaction_fromcsv", methods={"GET","POST"})
     * @phpstan-param StockRepositoryDoctrine<\xVer\MiCartera\Domain\Stock\Stock> $repoStock
     * @phpstan-param TransactionRepositoryDoctrine<\xVer\MiCartera\Domain\Transaction\Transaction> $repo
     * @phpstan-param AccountingMovementRepositoryDoctrine<\xVer\MiCartera\Domain\AccountingMovement\AccountingMovement> $repoAccountingMovement
     */
    public function fromcsv(Request $request, StockRepositoryDoctrine $repoStock, TransactionRepositoryDoctrine $repo, TranslatorInterface $translator, AccountingMovementRepositoryDoctrine $repoAccountingMovement): Response
    {
        $form = $this->createFormBuilder(null)
            ->add('csv', FileType::class, [
                'label' => 'csvTransactionFormat',
                'required' => true,
                'mapped' => false
            ])
            ->add('upload', SubmitType::class, ['label' => 'upload'])
            ->getForm();
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
                    /** @var AuthUser */
                    $authUser = $this->getUser();
                    /** @var Account */
                    $account = $authUser->getAccount();
                    $numRow = 1;
                    while (false !== ($row = fgetcsv($fp))) {
                        $numCols = count($row);
                        if (6 != $numCols) {
                            throw new DomainException(new TranslationVO('transactionCsvInvalidRowCount', ['row' => $numRow], TranslationVO::DOMAIN_VALIDATORS));
                        }
                        // date('Y-m-d H:i:s'),type,stock,price,amount,expenses
                        if (is_null($stock = $repoStock->findById((string) $row[2]))) {
                            throw new DomainException(new TranslationVO('transactionCsvNonExistentStock', ['row' => $numRow], TranslationVO::DOMAIN_VALIDATORS));
                        }
                        try {
                            /**
                             * @var string
                             * @psalm-var numeric-string
                             */
                            $priceStr = (string) $row[3];
                            $stock->setPrice(new StockPriceVO($priceStr, $account->getCurrency()));
                            $dateTime = \DateTime::createFromFormat('Y-m-d H:i:s', $row[0], $account->getTimeZone());
                            if (false === $dateTime) {
                                throw new DomainException(
                                    new TranslationVO(
                                        'transactionCsvInvalidRow',
                                        ['row' => $numRow, 'error' => 'date'],
                                        TranslationVO::DOMAIN_VALIDATORS
                                    )
                                );
                            }
                            if ($account->getTimeZone() !== new \DateTimeZone('UTC')) {
                                $dateTime->setTimezone(new \DateTimeZone('UTC'));
                            }
                            /**
                             * @var string
                             * @psalm-var numeric-string
                             */
                            $expensesStr = (string) $row[5];
                            $expenses = new MoneyVO($expensesStr, $account->getCurrency());
                            $transaction = new Transaction(
                                (int) $row[1],
                                $stock,
                                $dateTime,
                                (int) $row[4],
                                $expenses,
                                $account
                            );
                            $command = new AddTransactionCommand();
                            $command->execute($repo, $transaction, $repoAccountingMovement);
                        } catch (DomainException $th) {
                            throw new DomainException(
                                new TranslationVO(
                                    'transactionCsvInvalidRow',
                                    ['row' => $numRow, 'field' => $th->getObjectProperty(), 'error' => $this->getDomainExceptionMessage($th, $translator)],
                                    TranslationVO::DOMAIN_VALIDATORS
                                ),
                                'csv'
                            );
                        }
                        $numRow++;
                    }
                    fclose($fp);
                    return $this->redirectToRoute('transaction_index', [], Response::HTTP_SEE_OTHER);
                } else {
                    throw new DomainException(new TranslationVO('transactionCsvInvalidFile', [], TranslationVO::DOMAIN_VALIDATORS));
                }
            } catch (DomainException $de) {
                $this->getDomainExceptionFormError($form, $de, $translator);
            }
        }

        return $this->renderForm('transaction/fromcsv.html.twig', [
            'form' => $form,
        ]);
    }

    /**
     * @Route("/{id}/edit", name="transaction_edit", methods={"GET","POST"})
     * @phpstan-param TransactionRepositoryDoctrine<\xVer\MiCartera\Domain\Transaction\Transaction> $repo
     */
    public function edit(Request $request, Transaction $transaction, TransactionRepositoryDoctrine $repo, TranslatorInterface $translator): Response
    {
        $form = $this->createForm(TransactionType::class, $transaction);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // No need to catch DomainException since all possible exceptions are catched by the form handler
            $command = new UpdateTransactionCommand();
            $command->execute($repo, $transaction);
            return $this->redirectToRoute('transaction_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('transaction/edit.html.twig', [
            'transaction' => $transaction,
            'form' => $form,
        ]);
    }

    /**
     * @Route("/{id}", name="transaction_show", methods={"GET"})
     */
    public function show(Transaction $transaction, ?Response $failedDeleteResponse = null): Response
    {
        return $this->render(
            'transaction/show.html.twig',
            ['transaction' => $transaction],
            $failedDeleteResponse
        );
    }

    /**
     * @Route("/{id}", name="transaction_delete", methods={"POST"})
     * @phpstan-param TransactionRepositoryDoctrine<\xVer\MiCartera\Domain\Transaction\Transaction> $repo
     * @phpstan-param AccountingMovementRepositoryDoctrine<\xVer\MiCartera\Domain\AccountingMovement\AccountingMovement> $accountingMovementRepo
     */
    public function delete(Request $request, Transaction $transaction, TransactionRepositoryDoctrine $repo, TranslatorInterface $translator, AccountingMovementRepositoryDoctrine $accountingMovementRepo): Response
    {
        if ($this->isCsrfTokenValid('delete'.$transaction->getId(), (string) $request->request->get('_token'))) {
            try {
                $command = new RemoveTransactionCommand();
                $command->execute($repo, $transaction, $accountingMovementRepo);
            } catch (DomainException $de) {
                $this->addFlash('error', $this->getDomainExceptionMessage($de, $translator));
                return $this->show($transaction, new Response(null, Response::HTTP_UNPROCESSABLE_ENTITY));
            }
        }

        return $this->redirectToRoute('transaction_index', [], Response::HTTP_SEE_OTHER);
    }
}
