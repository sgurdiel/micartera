<?php

namespace xVer\MiCartera\Application\Query\Portfolio;

use xVer\Bundle\DomainBundle\Application\AbstractApplication;
use xVer\MiCartera\Domain\Account\AccountRepositoryInterface;
use xVer\MiCartera\Domain\Stock\Transaction\AdquisitionRepositoryInterface;

class PortfolioQuery extends AbstractApplication
{
    public function getPortfolio(
        string $accountIdentifier,
        int $limit = 0,
        int $page = 0,
    ): PortfolioDTO {
        $account = $this->repoLoader->load(AccountRepositoryInterface::class)
        ->findByIdentifierOrThrowException($accountIdentifier);
        $repoTransaction = $this->repoLoader->load(AdquisitionRepositoryInterface::class);
        return new PortfolioDTO(
            $account,
            $repoTransaction->findByAccountWithAmountOutstanding(
                $account,
                'ASC',
                'datetimeutc',
                ($limit ? $limit + 1 : 0),
                ($limit ? $page * $limit : 0),
            ),
            $repoTransaction->portfolioSummary($account),
            $limit,
            $page
        );
    }
}
