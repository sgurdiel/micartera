<?php

namespace xVer\MiCartera\Domain\Exchange;

use InvalidArgumentException;
use Throwable;
use xVer\Bundle\DomainBundle\Domain\DomainException;
use xVer\Bundle\DomainBundle\Domain\EntityObjectInterface;
use xVer\Bundle\DomainBundle\Domain\EntityObjectRepositoryLoaderInterface;
use xVer\Bundle\DomainBundle\Domain\TranslationVO;

class Exchange implements EntityObjectInterface
{
    final public const MAX_CODE_LENGTH = 12;
    final public const MIN_CODE_LENGTH = 3;
    final public const MAX_NAME_LENGTH = 255;
    final public const MIN_NAME_LENGTH = 1;
    private string $code;
    private string $name;

    public function __construct(
        readonly EntityObjectRepositoryLoaderInterface $repoLoader,
        string $code,
        string $name,
    ) {
        $this->setCode($code);
        $this->setName($name);
        $this->persistCreate($repoLoader);
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function sameId(EntityObjectInterface $otherEntityObject): bool
    {
        if (!$otherEntityObject instanceof Exchange) {
            throw new InvalidArgumentException();
        }
        return 0 === strcmp($this->getCode(), $otherEntityObject->getCode());
    }

    private function setCode(string $code): self
    {
        $length = mb_strlen($code);
        if ($length > self::MAX_CODE_LENGTH || $length < self::MIN_CODE_LENGTH) {
            throw new DomainException(
                new TranslationVO(
                    'stringLength',
                    ['minimum' => self::MIN_CODE_LENGTH, 'maximum' => self::MAX_CODE_LENGTH],
                    TranslationVO::DOMAIN_VALIDATORS
                ),
                'code'
            );
        }
        $this->code = mb_strtoupper($code);

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    final public function setName(string $name): self
    {
        $length = mb_strlen($name);
        if ($length > self::MAX_NAME_LENGTH || $length <  self::MIN_NAME_LENGTH) {
            throw new DomainException(
                new TranslationVO(
                    'stringLength',
                    ['minimum' => self::MIN_NAME_LENGTH, 'maximum' => self::MAX_NAME_LENGTH],
                    TranslationVO::DOMAIN_VALIDATORS
                ),
                'name'
            );
        }
        $this->name = $name;

        return $this;
    }

    private function persistCreate(
        EntityObjectRepositoryLoaderInterface $repoLoader
    ): void {
        $repoExchange = $repoLoader->load(ExchangeRepositoryInterface::class);
        if (null !== $repoExchange->findById($this->getCode())
        ) {
            throw new DomainException(
                new TranslationVO(
                    'ExchangeExists',
                    [],
                    TranslationVO::DOMAIN_VALIDATORS
                ),
                'code'
            );
        }
        try {
            $repoExchange->persist($this);
            $repoExchange->flush();
        } catch (Throwable) {
            throw new DomainException(
                new TranslationVO(
                    'actionFailed',
                    [],
                    TranslationVO::DOMAIN_MESSAGES
                )
            );
        }
    }
}
