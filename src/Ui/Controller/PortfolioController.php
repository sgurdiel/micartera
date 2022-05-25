<?php

namespace xVer\MiCartera\Ui\Controller;

use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use xVer\MiCartera\Application\EntityObjectRepositoryLoader;
use xVer\MiCartera\Application\Query\Portfolio\PortfolioQuery;

#[Route('/{_locale<%app.locales%>}/portfolio')]
class PortfolioController extends AbstractController
{
    #[Route('', name: 'portfolio_index', methods: ['GET'])]
    public function index(Request $request, ManagerRegistry $managerRegistry): Response
    {
        /** @psalm-suppress PossiblyNullReference */
        $userIdentifier = $this->getUser()->getUserIdentifier();
        $query = new PortfolioQuery(EntityObjectRepositoryLoader::doctrine($managerRegistry));
        $portfolioDTO = $query->getPortfolio(
            $userIdentifier,
            10,
            (int) $request->query->get('page', 0)
        );
        return $this->render('portfolio/index.html.twig', ['portfolio' => $portfolioDTO]);
    }
}
