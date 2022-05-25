<?php

namespace App\Form;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\FormBuilderInterface;
use xVer\MiCartera\Domain\Currency\Currency;
use Symfony\Component\Form\Extension\Core\Type\TimezoneType;
use xVer\Symfony\Bundle\BaseAppBundle\Form\RegistrationFormType as FormRegistrationFormType;

class RegistrationFormType extends FormRegistrationFormType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('currency', EntityType::class, [
                'class' => Currency::class,
                'label' => 'currency',
                'choice_label' => 'iso3',
                'choice_translation_domain' => false,
            ])
            ->add('timezone', TimezoneType::class, [
                'label' => 'timezone',
                'input' => 'datetimezone',
                'data' => new \DateTimeZone(date_default_timezone_get())
            ]);
        parent::buildForm($builder, $options);
    }
}
