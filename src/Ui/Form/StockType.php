<?php

namespace xVer\MiCartera\Ui\Form;

use Doctrine\Persistence\ManagerRegistry;
use NumberFormatter;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\DataMapperInterface;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Translation\TranslatableMessage;
use Traversable;
use xVer\MiCartera\Application\EntityObjectRepositoryLoader;
use xVer\MiCartera\Application\Query\Account\AccountQuery;
use xVer\MiCartera\Application\Query\Stock\StockQuery;

class StockType extends AbstractType implements DataMapperInterface
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
        if (array_key_exists('data', $options)) { //When editting stock, code is readonly
            $builder
                ->add('code', null, [
                    'disabled' => true,
                    'label' => 'code',
                ]);
            $submitLabel = 'update';
        } else {
            $builder
                ->add('code', null, [
                    'label' => 'code',
                ]);
            $submitLabel = 'createNew';
        }
        $accountQuery = new AccountQuery(
            EntityObjectRepositoryLoader::doctrine($this->managerRegistry)
        );
        $account = $accountQuery->byIdentifier($this->accountIdentifier);
        $builder
            ->add('name', TextType::class, [
                'label' => 'name',
            ])
            ->add('price', NumberType::class, [
                'scale' => 4,
                'rounding_mode' => NumberFormatter::ROUND_HALFUP,
                'html5' => true,
                'attr' => ['step' => 0.0001],
                'label' => new TranslatableMessage(
                    'priceWithCurrencySymbol',
                    ['symbol' => $account->getCurrency()->getSymbol()]
                ),
            ])
            ->add('refererPage', HiddenType::class)
            ->add('cmdSubmit', SubmitType::class, [
                'label' => new TranslatableMessage($submitLabel)
            ])
            ->setDataMapper($this);
    }

    /**
     * {@inheritDoc}
     * @psalm-param array|null $viewData,
     * @psalm-suppress MoreSpecificImplementedParamType
     */
    public function mapDataToForms(mixed $viewData, Traversable $forms): void
    {
        // there is no data yet, so nothing to prepopulate
        if (null === $viewData || !$forms instanceof Traversable) {
            return;
        }

        $forms = iterator_to_array($forms);
        /** @var FormInterface[] $forms */

        // initialize form field values
        $forms['code']->setData($viewData['code']);
        if (!isset($viewData['updatePost'])) {
            $query = new StockQuery(EntityObjectRepositoryLoader::doctrine($this->managerRegistry));
            $stock = $query->byCode((string) $viewData['code']);
            $forms['name']->setData($stock->getName());
            $forms['price']->setData($stock->getPrice()->getValue());
            $forms['refererPage']->setData($viewData['refererPage']);
        }
    }

    /**
     * {@inheritDoc}
     * @psalm-param array $viewData
     * @psalm-suppress MoreSpecificImplementedParamType
     */
    public function mapFormsToData(\Traversable $forms, mixed &$viewData): void
    {
    }
}
