<?php

namespace App\Form;

use xVer\MiCartera\Domain\Stock\Stock;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\DataMapperInterface;
use Symfony\Component\Form\Exception\UnexpectedTypeException;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use xVer\MiCartera\Domain\Account\Account;
use xVer\Bundle\DomainBundle\Domain\DomainException;
use xVer\MiCartera\Domain\Stock\StockPriceVO;
use xVer\Symfony\Bundle\BaseAppBundle\Entity\AuthUser;

class StockType extends AbstractType implements DataMapperInterface
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
        if (array_key_exists("data", $options)) { //When editting only name and price are allowed
            $builder
                ->add('code', null, [
                    'disabled' => true,
                    'label' => 'code',
                ])
                ->add('name', null, [
                    'label' => 'name',
                ])
                ->add('price', NumberType::class, [
                    'scale' => 4,
                    'rounding_mode' => \NumberFormatter::ROUND_HALFUP,
                    'html5' => true,
                    'attr' => ['step' => 0.0001],
                    'label' => 'price',
                ])
                ->setDataMapper($this);
        } else {
            $builder
                ->add('code', null, [
                    'label' => 'code',
                ])
                ->add('name', null, [
                    'label' => 'name',
                ])
                ->add('price', NumberType::class, [
                    'scale' => 4,
                    'rounding_mode' => \NumberFormatter::ROUND_HALFUP,
                    'html5' => true,
                    'attr' => ['step' => 0.0001],
                    'label' => 'price',
                ])
                ->setDataMapper($this);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Stock::class,
            'empty_data' => function (FormInterface $form) {
                try {
                    /**
                     * @var string
                     * @psalm-var numeric-string
                     */
                    $priceValue = $form->get('price')->getData();
                    $price = new StockPriceVO($priceValue, $this->account->getCurrency());
                    return new Stock(
                        (string) $form->get('code')->getData(),
                        (string) $form->get('name')->getData(),
                        $price
                    );
                } catch (DomainException $th) {
                    $objectProperty = $th->getObjectProperty();
                    $aux = (null === $objectProperty ? $form : $form->get($objectProperty));
                    $aux->addError(
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
        if (!$viewData instanceof Stock) {
            throw new UnexpectedTypeException($viewData, Stock::class);
        }

        $forms = \iterator_to_array($forms);
        /** @var FormInterface[] $forms */

        // initialize form field values
        $forms['code']->setData($viewData->getId());
        $forms['name']->setData($viewData->getName());
        $forms['price']->setData($viewData->getPrice()->getValue());
    }

    /**
     * {@inheritDoc}
     */
    public function mapFormsToData($forms, &$viewData): void
    {
        // there is no data yet, so nothing to prepopulate
        if (!$viewData instanceof Stock || !$forms instanceof \Traversable) {
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
            $viewData->setName((string) $forms['name']->getData());
            $viewData->setPrice($price);
        } catch (DomainException $th) {
            $objectProperty = $th->getObjectProperty();
            if (null !== $objectProperty) {
                $forms[$objectProperty]->addError(
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
}
