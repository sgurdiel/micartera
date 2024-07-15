<?php

namespace xVer\MiCartera\Application\Query\Stock\Accounting;

use DateTime;
use xVer\Bundle\DomainBundle\Application\AbstractApplication;
use xVer\MiCartera\Domain\Account\AccountRepositoryInterface;
use xVer\MiCartera\Domain\Stock\Accounting\MovementRepositoryInterface;

class AccountingQuery extends AbstractApplication
{
    public function byAccountYear(
        string $accountIdentifier,
        ?int $displayedYear = null,
        int $limit = 0,
        int $page = 0,
    ): AccountingDTO {
        $account = $this->repoLoader->load(AccountRepositoryInterface::class)->findByIdentifierOrThrowException($accountIdentifier);
        $repoAccountingMovement = $this->repoLoader->load(MovementRepositoryInterface::class);
        $displayedYear = (
            is_null($displayedYear)
            ? (int) (new DateTime('now', $account->getTimeZone()))->format('Y')
            : $displayedYear
        );
        return new AccountingDTO(
            $account,
            $repoAccountingMovement->findByAccountAndYear(
                $account,
                $displayedYear,
                ($limit ? $limit + 1 : null),
                ($limit ? $page * $limit : 0)
            ),
            $displayedYear,
            $repoAccountingMovement->accountingSummaryByAccount($account, $displayedYear),
            $limit,
            $page
        );
    }
}
