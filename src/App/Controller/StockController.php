<?php

namespace App\Controller;

use xVer\MiCartera\Domain\Stock\Stock;
use App\Form\StockType;
use xVer\MiCartera\Infrastructure\Stock\StockRepositoryDoctrine;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use xVer\MiCartera\Application\Command\AddStockCommand;
use xVer\MiCartera\Application\Command\RemoveStockCommand;
use xVer\MiCartera\Application\Command\UpdateStockCommand;
use xVer\MiCartera\Application\Query\StockQuery;
use xVer\Bundle\DomainBundle\Domain\DomainException;
use xVer\MiCartera\Domain\Account\Account;
use xVer\MiCartera\Infrastructure\Transaction\TransactionRepositoryDoctrine;
use xVer\Symfony\Bundle\BaseAppBundle\Controller\DomainExceptionTrait;
use xVer\Symfony\Bundle\BaseAppBundle\Entity\AuthUser;

/**
 * @Route("/{_locale<%app_locales%>}/stock")
 */
class StockController extends AbstractController
{
    use DomainExceptionTrait;

    /**
     * @Route("/", name="stock_index", methods={"GET"})
     * @phpstan-param StockRepositoryDoctrine<\xVer\MiCartera\Domain\Stock\Stock> $repo
     */
    public function index(StockRepositoryDoctrine $repo, Request $request): Response
    {
        /** @var AuthUser */
        $authUser = $this->getUser();
        /** @var Account */
        $account = $authUser->getAccount();
        $command = new StockQuery();
        $queryResponse = $command->execute(
            $repo,
            $account->getCurrency(),
            10,
            (int) $request->query->get("page", 0)
        );
        return $this->render('stock/index.html.twig', ["stocks" => $queryResponse]);
    }

    /**
     * @Route("/new", name="stock_new", methods={"GET","POST"})
     * @phpstan-param StockRepositoryDoctrine<\xVer\MiCartera\Domain\Stock\Stock> $repo
     */
    public function new(Request $request, StockRepositoryDoctrine $repo, TranslatorInterface $translator): Response|RedirectResponse
    {
        $form = $this->createForm(StockType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                /** @var Stock $stock */
                $stock = $form->getData();
                $command = new AddStockCommand();
                $command->execute($repo, $stock);
                return $this->redirectToRoute('stock_index', [], Response::HTTP_SEE_OTHER);
            } catch (DomainException $de) {
                $this->getDomainExceptionFormError($form, $de, $translator);
            }
        }

        return $this->renderForm('stock/new.html.twig', [
            'form' => $form,
        ]);
    }

    /**
     * @Route("/{id}/edit", name="stock_edit", methods={"GET","POST"})
     * @phpstan-param StockRepositoryDoctrine<\xVer\MiCartera\Domain\Stock\Stock> $repo
     */
    public function edit(Request $request, Stock $stock, StockRepositoryDoctrine $repo, TranslatorInterface $translator): Response|RedirectResponse
    {
        $form = $this->createForm(StockType::class, $stock);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // No need to catch DomainException since all possible exceptions are catched by the form handler
            $command = new UpdateStockCommand();
            $command->execute($repo, $stock);
            return $this->redirectToRoute('stock_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('stock/edit.html.twig', [
            'stock' => $stock,
            'form' => $form,
        ]);
    }

    /**
     * @Route("/{id}", name="stock_show", methods={"GET"})
     */
    public function show(Stock $stock, ?Response $failedDeleteResponse = null): Response
    {
        return $this->render(
            'stock/show.html.twig',
            ['stock' => $stock],
            $failedDeleteResponse
        );
    }

    /**
     * @Route("/{id}", name="stock_delete", methods={"POST"})
     * @phpstan-param StockRepositoryDoctrine<\xVer\MiCartera\Domain\Stock\Stock> $repo
     * @phpstan-param TransactionRepositoryDoctrine<\xVer\MiCartera\Domain\Transaction\Transaction> $transRepo
     */
    public function delete(Request $request, Stock $stock, StockRepositoryDoctrine $repo, TransactionRepositoryDoctrine $transRepo, TranslatorInterface $translator): Response|RedirectResponse
    {
        if ($this->isCsrfTokenValid('delete'.$stock->getId(), (string) $request->request->get('_token'))) {
            try {
                $command = new RemoveStockCommand();
                $command->execute($repo, $stock, $transRepo);
            } catch (DomainException $de) {
                $this->addFlash('error', $this->getDomainExceptionMessage($de, $translator));
                return $this->show($stock, new Response(null, Response::HTTP_UNPROCESSABLE_ENTITY));
            }
        }

        return $this->redirectToRoute('stock_index', [], Response::HTTP_SEE_OTHER);
    }
}
