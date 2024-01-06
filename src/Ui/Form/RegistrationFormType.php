<?php

namespace xVer\MiCartera\Ui\Form;

use DateTimeZone;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Form\DataMapperInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TimezoneType;
use Symfony\Component\Form\Test\FormInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use xVer\MiCartera\Application\Command\Account\AccountCommand;
use xVer\MiCartera\Application\EntityObjectRepositoryLoader;
use xVer\MiCartera\Application\Query\Currency\CurrencyQuery;
use xVer\Symfony\Bundle\BaseAppBundle\Ui\Entity\AuthUser;
use xVer\Symfony\Bundle\BaseAppBundle\Ui\Controller\ExceptionTranslatorTrait;
use xVer\Symfony\Bundle\BaseAppBundle\Ui\Form\RegistrationFormType as FormRegistrationFormType;

class RegistrationFormType extends FormRegistrationFormType implements DataMapperInterface
{
    use ExceptionTranslatorTrait;

    public function __construct(
        private readonly ManagerRegistry $managerRegistry,
        private readonly TranslatorInterface $translator,
        private readonly UserPasswordHasherInterface $passwordHasher
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $currenciesQuery = new CurrencyQuery(
            EntityObjectRepositoryLoader::doctrine($this->managerRegistry)
        );
        $currencies = $currenciesQuery->all()->getCollection()->toArray();
        $builder
            ->add('currency', ChoiceType::class, [
                'choices' => $currencies,
                'label' => 'currency',
                'choice_value' => 'iso3',
                'choice_label' => 'iso3',
                'choice_translation_domain' => false,
            ])
            ->add('timezone', TimezoneType::class, [
                'label' => 'timezone',
                'input' => 'datetimezone',
                'data' => new DateTimeZone(date_default_timezone_get())
            ])
            ->setDataMapper($this);
        parent::buildForm($builder, $options);
    }

    /**
     * {@inheritDoc}
     * @psalm-suppress MoreSpecificImplementedParamType
     */
    public function mapDataToForms(mixed $viewData, \Traversable $forms): void
    {
    }

    /**
     * {@inheritDoc}
     * @psalm-suppress MoreSpecificImplementedParamType
     */
    public function mapFormsToData(\Traversable $forms, mixed &$viewData): void
    {
        $forms = \iterator_to_array($forms);
        /** @var FormInterface[] $forms */

        try {
            $command = new AccountCommand(
                EntityObjectRepositoryLoader::doctrine($this->managerRegistry)
            );
            $roles = ['ROLE_USER'];
            $hashedPassword = $this->passwordHasher->hashPassword(
                new AuthUser((string) $forms['email']->getData(), $roles, ''),
                (string) $forms['plainPassword']->getData()
            );
            /**
             * @psalm-suppress MixedAssignment
             * @psalm-suppress MixedMethodCall
             */
            $currencyIso3 = $forms['currency']->getData()->getIso3();
            /** @psalm-var DateTimeZone */
            $timezone = $forms['timezone']->getData();
            $command->create(
                (string) $forms['email']->getData(),
                $hashedPassword,
                (string) $currencyIso3,
                $timezone,
                $roles,
                (bool) $forms['agreeTerms']->getData()
            );
        } catch (\DomainException $de) {
            throw $this->getTranslatedException($de, $this->translator);
        }
    }
}
