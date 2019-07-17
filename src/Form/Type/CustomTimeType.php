<?php

declare(strict_types=1);

namespace App\Form\Type;

use App\Entity\Time;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

/**
 * Defines the form used to create and manipulate App\Entity\Time.
 *
 * @author Wouter van Harten <wouter@woutervanharten.nl>
 */
class CustomTimeType extends AbstractType {
    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder
            ->add('hour', ChoiceType::class, [
                'choices' => $options['hour_options'],
                'label' => false,
            ])
            ->add('minute', ChoiceType::class, [
                'choices' => $options['minute_options'],
                'label' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver) {
        $resolver->setDefaults([
            'data_class' => Time::class,
            'minute_options' => range(0, 59),
            'hour_options' => range(0, 23),
        ]);
    }
}
