<?php

declare(strict_types=1);

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Form;

use App\Entity\LeaseRequest;
use App\Form\Type\DateTimePickerType;
use App\Form\Type\TagsInputType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\CallbackTransformer;

/**
 * Defines the form used to create and manipulate blog posts.
 *
 * @author Wouter van Harten <wouter@woutervanharten.nl>
 */
class LeaseRequestEditType extends AbstractType {
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void {
        $timeOptions = [
            'label' => 'label.start_time',
            'choices' => LeaseRequest::KEYTIMES,
        ];
        if (!$options['editKeyTimes']) {
            $timeOptions['disabled'] = true;
        }

        $transformer = new CallbackTransformer(
                function ($timeAsDateTime) {
                    if (is_null($timeAsDateTime)) {
                        return null;
                    }
                    return $timeAsDateTime->format('H:i');
                },
                function ($timeAsText) {
                    if (is_null($timeAsText)) {
                        return null;
                    }
                    return \DateTime::createFromFormat('H:i', $timeAsText);
                }
            );

        $builder
            ->add('title', null, [
                'attr' => ['autofocus' => true],
                'label' => 'label.title',
                'disabled' => true,
            ])
            ->add('summary', TextareaType::class, [
                'label' => 'label.summary',
                'disabled' => true,
            ])
            ->add('association_type', ChoiceType::class, [
                'label' => 'label.association_type',
                'choices' => LeaseRequest::ASSOCIATION_TYPES,
                'required' => true,
                'disabled' => true,
            ])
            ->add('start_date', DateType::class, [
                'label' => 'label.start_date',
                'required' => true,
                'widget' => 'single_text',
                'years' => array(date('Y'), date('Y') + 1),
                'model_timezone' => 'Europe/Amsterdam',
                'disabled' => true,
            ])
            ->add('end_date', DateType::class, [
                'label' => 'label.end_date',
                'required' => true,
                'widget' => 'single_text',
                'years' => array(date('Y'), date('Y') + 1),
                'model_timezone' => 'Europe/Amsterdam',
                'disabled' => true,
            ]);
        if ($options['editKeyTimes']) {
            $builder
                ->add('key_deliver', ChoiceType::class, $timeOptions)
                ->add('key_return', ChoiceType::class, $timeOptions);
        } else {
            $builder->add('key_deliver', TextType::class, [
                    'label' => 'label.start_time',
                    'disabled' => true,
                ])
                ->add('key_return', TextType::class, [
                    'label' => 'label.end_time',
                    'disabled' => true,
                ]);
        }
        $builder
            ->add('num_attendants', IntegerType::class, [
                'label' => 'label.num_attendants',
                'required' => true,
                'disabled' => true,
            ])
            ->add('price', MoneyType::class, [
                'label' => 'label.price',
                'required' => false,
                'disabled' => true,
            ]);
        if (!$options['signed_uploaded']) {
            $builder->add('contract_signed', FileType::class, [
                    'label' => 'label.upload_signed',
                    'attr' => array('class' => 'well'),
                    'data_class' => null,
                    'required' => false,
                ]);
        }
        $builder->add('submit', SubmitType::class, array(
                'attr' => array('class' => 'btn btn-primary'),
            ));
        $builder->get('key_deliver')
           ->addModelTransformer($transformer);
        $builder->get('key_return')
          ->addModelTransformer($transformer);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver): void {
        $resolver->setDefaults([
            'data_class' => LeaseRequest::class,
            'signed_uploaded' => false,
            'editKeyTimes' => false,
        ]);
    }
}
