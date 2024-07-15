<?php

namespace xVer\MiCartera\Application\Query\Stock\Portfolio;

use xVer\Bundle\DomainBundle\Application\AbstractApplication;
use xVer\MiCartera\Domain\Account\AccountRepositoryInterface;
use xVer\MiCartera\Domain\Stock\Portfolio\SummaryVO;
use xVer\MiCartera\Domain\Stock\StockRepositoryInterface;
use xVer\MiCartera\Domain\Stock\Transaction\AcquisitionRepositoryInterface;

class PortfolioQuery extends AbstractApplication
{
    public function getPortfolio(
        string $accountIdentifier,
        int $limit = 0,
        int $page = 0,
    ): PortfolioDTO {
        $account = $this->repoLoader->load(AccountRepositoryInterface::class)
        ->findByIdentifierOrThrowException($accountIdentifier);
        $repoTransaction = $this->repoLoader->load(AcquisitionRepositoryInterface::class);
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

    public function getStockPortfolioSummary(
        string $accountIdentifier,
        string $stockCode
    ): SummaryVO {
        $account = $this->repoLoader->load(AccountRepositoryInterface::class)
        ->findByIdentifierOrThrowException($accountIdentifier);
        $stock = $this->repoLoader->load(StockRepositoryInterface::class)
        ->findById($stockCode);
        $repoTransaction = $this->repoLoader->load(AcquisitionRepositoryInterface::class);
        return $repoTransaction->portfolioSummary($account, $stock);
    }
}
