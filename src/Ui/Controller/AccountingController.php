<?php

namespace xVer\MiCartera\Ui\Controller;

use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use xVer\MiCartera\Application\EntityObjectRepositoryLoader;
use xVer\MiCartera\Application\Query\Accounting\AccountingQuery;

#[Route('/{_locale<%app.locales%>}/accounting')]
class AccountingController extends AbstractController
{
    #[Route('', name: 'accounting_index', methods: ['GET'])]
    public function index(Request $request, ManagerRegistry $managerRegistry): Response
    {
        /** @psalm-suppress PossiblyNullReference */
        $userIdentifier = $this->getUser()->getUserIdentifier();
        $query = new AccountingQuery(EntityObjectRepositoryLoader::doctrine($managerRegistry));
        $accountingDTO = $query->byAccountYear(
            $userIdentifier,
            is_null($request->query->get('year')) === false ? (int) $request->query->get('year') : null,
            20,
            (int) $request->query->get('page', 0)
        );
        return $this->render('accounting/index.html.twig', ['accounting' => $accountingDTO]);
    }
}
