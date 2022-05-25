<?php

namespace xVer\MiCartera\Ui\Form;

use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\DataMapperInterface;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Test\FormInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Contracts\Translation\TranslatorInterface;
use xVer\MiCartera\Application\Command\Stock\StockOperateCommand;
use xVer\MiCartera\Application\EntityObjectRepositoryLoader;
use xVer\MiCartera\Application\Query\Account\AccountQuery;
use xVer\Symfony\Bundle\BaseAppBundle\Ui\Controller\ExceptionTranslatorTrait;

class StockOperateType extends AbstractType implements DataMapperInterface
{
    use ExceptionTranslatorTrait;

    private readonly string $accountIdentifier;

    public function __construct(
        TokenStorageInterface $token,
        private readonly ManagerRegistry $managerRegistry,
        private readonly ContainerBagInterface $params,
        private readonly TranslatorInterface $translator
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
        $submitLabel = (
            isset($options['data']['type']) && $options['data']['type'] === 0
            ? 'purchase'
            : 'sell'
        );
        $builder
            ->add('datetime', DateTimeType::class, [
                'years' => range((int) date('Y') - 10, (int) date('Y')),
                'label' => new TranslatableMessage(
                    'dateWithTZ',
                    ['timezone' => $account->getTimeZone()->getName()]
                ),
                'input' => 'datetime',
                'with_seconds' => true,
                'widget' => 'single_text',
                'model_timezone' => $this->params->get('app.timezone'),
                'view_timezone' => $account->getTimeZone()->getName(),
            ])
            ->add('amount', null, [
                'label' => 'amount',
            ])
            ->add('price', NumberType::class, [
                'scale' => 4,
                'rounding_mode' => \NumberFormatter::ROUND_HALFUP,
                'html5' => true,
                'attr' => ['step' => 0.0001],
                'label' => new TranslatableMessage(
                    'priceWithCurrencySymbol',
                    ['symbol' => $account->getCurrency()->getSymbol()]
                ),
            ])
            ->add('expenses', NumberType::class, [
                'scale' => $account->getCurrency()->getDecimals(),
                'rounding_mode' => \NumberFormatter::ROUND_HALFUP,
                'html5' => true,
                'attr' => ['step' => '1e-'.$account->getCurrency()->getDecimals()],
                'label' => new TranslatableMessage(
                    'expensesWithCurrencySymbol',
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
     * @psalm-param array $viewData,
     * @psalm-suppress MoreSpecificImplementedParamType
     */
    public function mapDataToForms(mixed $viewData, \Traversable $forms): void
    {
        $forms = \iterator_to_array($forms);
        /** @var FormInterface[] $forms */

        // initialize form field values
        $forms['refererPage']->setData(
            $viewData['refererPage']
        );
    }

    /**
     * {@inheritDoc}
     * @psalm-param array $viewData
     * @psalm-suppress MoreSpecificImplementedParamType
     */
    public function mapFormsToData(\Traversable $forms, mixed &$viewData): void
    {
        // @codeCoverageIgnoreStart
        // there is no data yet, so nothing to prepopulate
        if (!$forms instanceof \Traversable) {
            return;
        }
        // @codeCoverageIgnoreEnd

        $forms = \iterator_to_array($forms);
        /** @var FormInterface[] $forms */

        try {
            $repoLoader = EntityObjectRepositoryLoader::doctrine($this->managerRegistry);
            $command = new StockOperateCommand(
                $repoLoader
            );
            /** @psalm-var numeric-string */
            $price = $forms['price']->getData();
            /** @psalm-var numeric-string */
            $expenses = $forms['expenses']->getData();
            /** @psalm-var \DateTime */
            $dateTime = $forms['datetime']->getData();
            if ((int) $viewData['type'] === 0) {
                $command->purchase(
                    // (string) $stockId,
                    (string) $viewData['stock'],
                    $dateTime,
                    (int) $forms['amount']->getData(),
                    $price,
                    $expenses,
                    $this->accountIdentifier
                );
            } else {
                $command->sell(
                    // (string) $stockId,
                    (string) $viewData['stock'],
                    $dateTime,
                    (int) $forms['amount']->getData(),
                    $price,
                    $expenses,
                    $this->accountIdentifier
                );
            }
        } catch (\DomainException $de) {
            throw $this->getTranslatedException($de, $this->translator);
        }
    }
}
