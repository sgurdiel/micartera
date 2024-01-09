<?php

namespace xVer\MiCartera\Ui\Form;

use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\DataMapperInterface;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Translation\TranslatableMessage;
use Traversable;
use xVer\MiCartera\Application\EntityObjectRepositoryLoader;
use xVer\MiCartera\Application\Query\Account\AccountQuery;

class StockOperateImportType extends AbstractType implements DataMapperInterface
{
    private readonly string $accountIdentifier;

    public function __construct(
        TokenStorageInterface $token,
        private readonly ManagerRegistry $managerRegistry
    ) {
        /** @psalm-suppress PossiblyNullReference */
        $this->accountIdentifier = $token->getToken()->getUser()->getUserIdentifier();
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $accountQuery = new AccountQuery(
            EntityObjectRepositoryLoader::doctrine($this->managerRegistry)
        );
        $account = $accountQuery->byIdentifier($this->accountIdentifier);
        $builder
            ->add('csv', FileType::class, [
                'label' => new TranslatableMessage(
                    'csvTransactionFormat',
                    [
                        'timezone' => $account->getTimeZone()->getName(),
                        'currency' => $account->getCurrency()->getSymbol()
                    ],
                    'messages'
                ),
                'label_html' => true,
                'required' => true,
                'mapped' => false
            ])
            ->add('upload', SubmitType::class, ['label' => 'import'])
            ->setDataMapper($this);
    }

    /**
     * {@inheritDoc}
     * @psalm-suppress MoreSpecificImplementedParamType
     */
    public function mapDataToForms(mixed $viewData, Traversable $forms): void
    {
    }

    /**
     * {@inheritDoc}
     * @psalm-suppress MoreSpecificImplementedParamType
     */
    public function mapFormsToData(\Traversable $forms, mixed &$viewData): void
    {
    }
}
