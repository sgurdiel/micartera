<?php

namespace xVer\MiCartera\Ui\Form;

use DateTimeZone;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Form\DataMapperInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TimezoneType;
use Traversable;
use xVer\MiCartera\Application\EntityObjectRepositoryLoader;
use xVer\MiCartera\Application\Query\Currency\CurrencyQuery;
use xVer\Symfony\Bundle\BaseAppBundle\Ui\Form\RegistrationFormType as FormRegistrationFormType;

class RegistrationFormType extends FormRegistrationFormType implements DataMapperInterface
{
    public function __construct(
        private readonly ManagerRegistry $managerRegistry
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
    public function mapDataToForms(mixed $viewData, Traversable $forms): void
    {
    }

    /**
     * {@inheritDoc}
     * @psalm-suppress MoreSpecificImplementedParamType
     */
    public function mapFormsToData(Traversable $forms, mixed &$viewData): void
    {
    }
}
