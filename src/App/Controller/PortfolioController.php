<?php

namespace App\Controller;

use xVer\MiCartera\Infrastructure\Transaction\TransactionRepositoryDoctrine;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use xVer\MiCartera\Application\Query\PortfolioQuery;
use xVer\MiCartera\Domain\Account\Account;
use xVer\Symfony\Bundle\BaseAppBundle\Controller\DomainExceptionTrait;
use xVer\Symfony\Bundle\BaseAppBundle\Entity\AuthUser;

/**
 * @Route("/{_locale<%app_locales%>}/portfolio")
 */
class PortfolioController extends AbstractController
{
    use DomainExceptionTrait;

    /**
     * @Route("/", name="portfolio_index", methods={"GET"})
     * @phpstan-param TransactionRepositoryDoctrine<\xVer\MiCartera\Domain\Transaction\Transaction> $repo
     */
    public function index(TransactionRepositoryDoctrine $repo): Response
    {
        /** @var AuthUser */
        $authUser = $this->getUser();
        /** @var Account */
        $account = $authUser->getAccount();
        $query = new PortfolioQuery();
        $portfolioDTO = $query->execute($repo, $account);
        return $this->render('portfolio/index.html.twig', ['portfolio' => $portfolioDTO]);
    }
}
