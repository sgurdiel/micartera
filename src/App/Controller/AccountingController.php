<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use xVer\MiCartera\Application\Query\AccountingQuery;
use xVer\MiCartera\Domain\Account\Account;
use xVer\MiCartera\Infrastructure\AccountingMovement\AccountingMovementRepositoryDoctrine;
use xVer\Symfony\Bundle\BaseAppBundle\Controller\DomainExceptionTrait;
use xVer\Symfony\Bundle\BaseAppBundle\Entity\AuthUser;

/**
 * @Route("/{_locale<%app_locales%>}/accounting")
 */
class AccountingController extends AbstractController
{
    use DomainExceptionTrait;

    /**
     * @Route("/", name="accounting_index", methods={"GET"})
     * @param AccountingMovementRepositoryDoctrine<\xVer\MiCartera\Domain\AccountingMovement\AccountingMovement> $repo
     */
    public function index(Request $request, AccountingMovementRepositoryDoctrine $repo): Response
    {
        /** @var AuthUser */
        $authUser = $this->getUser();
        /** @var Account */
        $account = $authUser->getAccount();
        $dateTime = new \DateTime('now', $account->getTimeZone());
        $query = new AccountingQuery();
        $accountingDTO = $query->execute(
            $repo,
            $account,
            (int) $request->query->get("year", $dateTime->format('Y'))
        );
        return $this->render('accounting/index.html.twig', ["accounting" => $accountingDTO]);
    }
}
