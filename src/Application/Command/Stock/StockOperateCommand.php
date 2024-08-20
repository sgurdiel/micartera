<?php

namespace xVer\MiCartera\Application\Command\Stock;

use DateTime;
use DateTimeZone;
use Symfony\Component\Uid\Uuid;
use xVer\Bundle\DomainBundle\Application\AbstractApplication;
use xVer\Bundle\DomainBundle\Domain\DomainException;
use xVer\Bundle\DomainBundle\Domain\TranslationVO;
use xVer\MiCartera\Domain\Account\Account;
use xVer\MiCartera\Domain\MoneyVO;
use xVer\MiCartera\Domain\Stock\StockPriceVO;
use xVer\MiCartera\Domain\Account\AccountRepositoryInterface;
use xVer\MiCartera\Domain\Stock\Stock;
use xVer\MiCartera\Domain\Stock\StockRepositoryInterface;
use xVer\MiCartera\Domain\Stock\Transaction\Acquisition;
use xVer\MiCartera\Domain\Stock\Transaction\AcquisitionRepositoryInterface;
use xVer\MiCartera\Domain\Stock\Transaction\Liquidation;
use xVer\MiCartera\Domain\Stock\Transaction\LiquidationRepositoryInterface;

class StockOperateCommand extends AbstractApplication
{
    /**
     * @psalm-param numeric-string $priceValue
     * @psalm-param numeric-string $expensesValue
     */
    public function purchase(
        string $stockCode,
        DateTime $datetimeutc,
        int $amount,
        string $priceValue,
        string $expensesValue,
        string $accountIdentifier
    ): Acquisition {
        $account = $this->repoLoader->load(AccountRepositoryInterface::class)
        ->findByIdentifierOrThrowException($accountIdentifier);
        $stock = $this->repoLoader->load(StockRepositoryInterface::class)
        ->findByIdOrThrowException($stockCode)
        ->setPrice(
            new StockPriceVO(
                $priceValue,
                $account->getCurrency()
            )
        );
        return $this->newAcquisition(
            $stock,
            $datetimeutc,
            $amount,
            new MoneyVO(
                $expensesValue,
                $account->getCurrency()
            ),
            $account
        );
    }

    /**
     * @psalm-param numeric-string $priceValue
     * @psalm-param numeric-string $expensesValue
     */
    public function sell(
        string $stockCode,
        DateTime $datetimeutc,
        int $amount,
        string $priceValue,
        string $expensesValue,
        string $accountIdentifier
    ): Liquidation {
        $account = $this->repoLoader->load(AccountRepositoryInterface::class)
        ->findByIdentifierOrThrowException($accountIdentifier);
        $stock = $this->repoLoader->load(StockRepositoryInterface::class)
        ->findByIdOrThrowException($stockCode)
        ->setPrice(
            new StockPriceVO(
                $priceValue,
                $account->getCurrency()
            )
        );
        return $this->newLiquidation(
            $stock,
            $datetimeutc,
            $amount,
            new MoneyVO(
                $expensesValue,
                $account->getCurrency()
            ),
            $account
        );
    }

    public function removePurchase(string $acquisitionUuid): void
    {
        $this->repoLoader->load(
            AcquisitionRepositoryInterface::class
        )->findByIdOrThrowException(
            new UUid($acquisitionUuid)
        )->persistRemove(
            $this->repoLoader
        );
    }

    public function removeSell(string $id): void
    {
        $this->repoLoader->load(
            LiquidationRepositoryInterface::class
        )->findByIdOrThrowException(
            new UUid($id)
        )->persistRemove(
            $this->repoLoader
        );
    }

    /**
     * @psalm-param array{0: string,1: string,2: string,3: numeric-string,4: int,5: numeric-string} $line date<'Y-m-d H:i:s T'>,type,stock,price,amount,expenses
     */
    public function import(int $lineNumber, array $line, string $accountIdentifier): void
    {
        $account = $this->repoLoader->load(AccountRepositoryInterface::class)
        ->findByIdentifierOrThrowException($accountIdentifier);
        $repoStock = $this->repoLoader->load(StockRepositoryInterface::class);
        try {
            if (false === in_array($line[1], ['acquisition','liquidation'], true)) {
                throw new DomainException(
                    new TranslationVO(
                        'invalidTransactionType',
                        [
                            'type' => $line[1]
                        ],
                        TranslationVO::DOMAIN_VALIDATORS
                    ),
                    'type'
                );
            }
            $dateTime = DateTime::createFromFormat('Y-m-d H:i:s', $line[0], $account->getTimeZone());
            if (false === $dateTime) {
                throw new DomainException(
                    new TranslationVO(
                        'failedCreatingDateObjectFromString',
                        [
                            'format' => 'Y-m-d H:i:s'
                        ],
                        TranslationVO::DOMAIN_VALIDATORS
                    ),
                    'datetimeutc'
                );
            }
            if ($account->getTimeZone() !== new DateTimeZone('UTC')) {
                $dateTime->setTimezone(new DateTimeZone('UTC'));
            }
            try {
                $expenses = new MoneyVO($line[5], $account->getCurrency());
            } catch (DomainException $th) {
                throw new DomainException(
                    new TranslationVO(
                        $th->getTranslationVO()->getId(),
                        $th->getTranslationVO()->getParameters(),
                        $th->getTranslationVO()->getDomain()
                    ),
                    'expenses'
                );
            }
            $stock = $repoStock->findByIdOrThrowException($line[2])->setPrice(
                new StockPriceVO(
                    $line[3],
                    $account->getCurrency()
                )
            );
            $line[1] === 'acquisition' ?
                $this->newAcquisition(
                    $stock,
                    $dateTime,
                    $line[4],
                    $expenses,
                    $account
                )
            :
                $this->newLiquidation(
                    $stock,
                    $dateTime,
                    $line[4],
                    $expenses,
                    $account
                )
            ;
        } catch (DomainException $th) {
            throw new DomainException(
                new TranslationVO(
                    'importCsvDomainError',
                    [
                        'row' => $lineNumber,
                        'field' => $th->getObjectProperty(),
                        'error' => new TranslationVO(
                            $th->getTranslationVO()->getId(),
                            $th->getTranslationVO()->getParameters(),
                            $th->getTranslationVO()->getDomain()
                        )
                    ],
                    TranslationVO::DOMAIN_VALIDATORS
                )
            );
        }
    }

    protected function newAcquisition(
        Stock $stock,
        DateTime $dateTime,
        int $amount,
        MoneyVO $expenses,
        Account $account
    ): Acquisition {
        return new Acquisition(
            $this->repoLoader,
            $stock,
            $dateTime,
            $amount,
            $expenses,
            $account
        );
    }

    protected function newLiquidation(
        Stock $stock,
        DateTime $dateTime,
        int $amount,
        MoneyVO $expenses,
        Account $account
    ): Liquidation {
        return new Liquidation(
            $this->repoLoader,
            $stock,
            $dateTime,
            $amount,
            $expenses,
            $account
        );
    }
}
