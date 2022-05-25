<?php

namespace App\Form;

use xVer\MiCartera\Domain\Transaction\Transaction;
use xVer\MiCartera\Domain\Stock\Stock;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\DataMapperInterface;
use Symfony\Component\Form\Exception\UnexpectedTypeException;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Contracts\Translation\TranslatorInterface;
use xVer\MiCartera\Domain\Account\Account;
use xVer\Bundle\DomainBundle\Domain\DomainException;
use xVer\MiCartera\Domain\MoneyVO;
use xVer\MiCartera\Domain\Stock\StockPriceVO;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use xVer\MiCartera\Infrastructure\Stock\StockRepositoryDoctrine;
use xVer\Symfony\Bundle\BaseAppBundle\Entity\AuthUser;

class TransactionType extends AbstractType implements DataMapperInterface
{
    private Account $account;

    public function __construct(TokenStorageInterface $token, private TranslatorInterface $translator)
    {
        /** @var TokenInterface */
        $tokenInterface = $token->getToken();

        /** @var AuthUser */
        $authUser = $tokenInterface->getUser();
        /** @var Account */
        $this->account = $authUser->getAccount();
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        if (array_key_exists("data", $options)) { //When editting
            $builder
                ->add('price', NumberType::class, [
                    'scale' => 4,
                    'rounding_mode' => \NumberFormatter::ROUND_HALFUP,
                    'html5' => true,
                    'attr' => ['step' => 0.0001],
                    'label' => 'price',
                ])
                ->add('expenses', NumberType::class, [
                    'scale' => $this->account->getCurrency()->getDecimals(),
                    'rounding_mode' => \NumberFormatter::ROUND_HALFUP,
                    'html5' => true,
                    'attr' => ['step' => '1e-'.$this->account->getCurrency()->getDecimals()],
                    'label' => 'expenses',
                ])
                ->setDataMapper($this);
        } else {
            $builder
                ->add('type', ChoiceType::class, [
                    'choices' => [
                        'buy' => Transaction::TYPE_BUY,
                        'sell' => Transaction::TYPE_SELL,
                    ],
                    'label' => 'type',
                ])
                ->add('stock', EntityType::class, [
                    'class' => Stock::class,
                    'query_builder' => function (StockRepositoryDoctrine $er) {
                        return $er->queryBuilderForTransactionForm($this->account->getCurrency());
                    },
                    'label' => new TranslatableMessage('stocks', ['stocks' => 1]),
                    'choice_label' => 'id',
                    'placeholder' => 'chooseAnOption',
                ])
                ->add('datetime', DateTimeType::class, [
                    'years' => range((int) date('Y') - 10, (int) date('Y')),
                    'label' => 'date',
                    'input' => 'datetime',
                    'with_seconds' => true,
                    'widget' => 'single_text',
                    'model_timezone' => 'UTC',
                    'view_timezone' => 'UTC',
                ])
                ->add('amount', null, [
                    'label' => 'amount',
                ])
                ->add('price', NumberType::class, [
                    'scale' => StockPriceVO::DECIMALS,
                    'rounding_mode' => \NumberFormatter::ROUND_HALFUP,
                    'html5' => true,
                    'attr' => ['step' => 0.0001],
                    'label' => 'price',
                ])
                ->add('expenses', NumberType::class, [
                    'scale' => $this->account->getCurrency()->getDecimals(),
                    'rounding_mode' => \NumberFormatter::ROUND_HALFUP,
                    'html5' => true,
                    'attr' => ['step' => '1e-'.$this->account->getCurrency()->getDecimals()],
                    'label' => 'expenses',
                ])
                ->setDataMapper($this);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Transaction::class,
            'empty_data' => function (FormInterface $form) {
                try {
                    /**
                     * @var string
                     * @psalm-var numeric-string
                     */
                    $expensesValue = $form->get('expenses')->getData();
                    $expenses = new MoneyVO($expensesValue, $this->account->getCurrency());
                } catch (DomainException $th) {
                    $form->get('expenses')->addError(
                        new FormError(
                            $this->translator->trans(
                                $th->getTranslationVO()->getId(),
                                $th->getTranslationVO()->getParameters(),
                                $th->getTranslationVO()->getDomain()
                            )
                        )
                    );
                    return null;
                }
                try {
                    /** @var Stock */
                    $stock = $form->get('stock')->getData();
                    /** @var \DateTime */
                    $aux = $form->get('datetime')->getData();
                    $datetimeutc = new \DateTime($aux->format('Y-m-d H:i:s'), $this->account->getTimeZone());
                    $datetimeutc->setTimezone(new \DateTimeZone('UTC'));
                    return new Transaction(
                        (int) $form->get('type')->getData(),
                        $stock,
                        $datetimeutc,
                        (int) $form->get('amount')->getData(),
                        $expenses,
                        $this->account
                    );
                } catch (DomainException $th) {
                    $objectProperty = $th->getObjectProperty();
                    (null === $objectProperty ? $form : $form->get($objectProperty))->addError(
                        new FormError(
                            $this->translator->trans(
                                $th->getTranslationVO()->getId(),
                                $th->getTranslationVO()->getParameters(),
                                $th->getTranslationVO()->getDomain()
                            )
                        )
                    );
                }
            }
        ]);
    }

    /**
     * {@inheritDoc}
     */
    public function mapDataToForms($viewData, $forms): void
    {
        // there is no data yet, so nothing to prepopulate
        if (null === $viewData || !$forms instanceof \Traversable) {
            return;
        }

        // invalid data type
        if (!$viewData instanceof Transaction) {
            throw new UnexpectedTypeException($viewData, Transaction::class);
        }

        $forms = \iterator_to_array($forms);
        /** @var FormInterface[] $forms */

        // initialize form field values
        $forms['price']->setData($viewData->getPrice()->getValue());
        $forms['expenses']->setData($viewData->getExpenses()->getValue());
    }

    public function mapFormsToData($forms, &$viewData): void
    {
        if (!$viewData instanceof Transaction || !$forms instanceof \Traversable) {
            return;
        }

        $forms = \iterator_to_array($forms);
        /** @var FormInterface[] $forms */

        try {
            /**
             * @var string
             * @psalm-var numeric-string
             */
            $priceValue = $forms['price']->getData();
            $price = new StockPriceVO($priceValue, $viewData->getCurrency());
            $viewData->setPrice($price);
        } catch (DomainException $th) {
            $forms['price']->addError(
                new FormError(
                    $this->translator->trans(
                        $th->getTranslationVO()->getId(),
                        $th->getTranslationVO()->getParameters(),
                        $th->getTranslationVO()->getDomain()
                    )
                )
            );
        }
        try {
            /**
             * @var string
             * @psalm-var numeric-string
             */
            $expensesValue = $forms['expenses']->getData();
            $expenses = new MoneyVO($expensesValue, $viewData->getCurrency());
            $viewData->setExpenses(($expenses));
        } catch (DomainException $th) {
            $forms['expenses']->addError(
                new FormError(
                    $this->translator->trans(
                        $th->getTranslationVO()->getId(),
                        $th->getTranslationVO()->getParameters(),
                        $th->getTranslationVO()->getDomain()
                    )
                )
            );
        }
    }
}
