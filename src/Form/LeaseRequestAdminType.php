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
use App\Entity\Time;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use App\Form\Type\CustomTimeType;
use Symfony\Component\Form\CallbackTransformer;

/**
 * Defines the form used to create and manipulate blog posts.
 *
 * @author Wouter van Harten <wouter@woutervanharten.nl>
 */
class LeaseRequestAdminType extends AbstractType {
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void {
        $timeOptions = [
            'label' => 'label.start_time',
            'hour_options' => array_combine(array_merge(array_map('strval', range(9, 22)), ['label.not_known.short']), array_merge(array_map('strval', range(9, 22)), [null])),
            'minute_options' => array_combine(array_merge(array_map('strval', range(0, 49, 15)), ['label.not_known.short']), array_merge(array_map('strval', range(0, 49, 15)), [null])),
        ];
        $transformer = new CallbackTransformer(
                function ($timeAsDateTime) {
                    if (is_null($timeAsDateTime)) {
                        return null;
                    }
                    $time = new Time();
                    $time->setHour($timeAsDateTime->format('H'));
                    $time->setMinute($timeAsDateTime->format('i'));
                    return $time;
                },
                function ($timeAsCustom) {
                    if (is_null($timeAsCustom->getHour()) || is_null($timeAsCustom->getMinute())) {
                        return null;
                    }
                    return \DateTime::createFromFormat('H:i', $timeAsCustom->getHour() . ':' . sprintf('%02d', $timeAsCustom->getMinute()));
                }
            );

        $builder
            ->add('title', null, [
                'attr' => ['autofocus' => true],
                'label' => 'label.title',
            ])
            ->add('summary', TextareaType::class, [
                'label' => 'label.summary',
            ])
            ->add('association_type', ChoiceType::class, [
                'label' => 'label.association_type',
                'choices' => LeaseRequest::ASSOCIATION_TYPES,
                'required' => true,
            ])
            ->add('association', null, [
                'label' => 'label.association',
                'required' => true,
            ])
            ->add('start_date', DateType::class, [
                'label' => 'label.start_date',
                'required' => true,
                'widget' => 'single_text',
                'years' => array(date('Y'), date('Y') + 1),
                'model_timezone' => 'Europe/Amsterdam',
            ])
            ->add('end_date', DateType::class, [
                'label' => 'label.end_date',
                'required' => true,
                'widget' => 'single_text',
                'years' => array(date('Y'), date('Y') + 1),
                'model_timezone' => 'Europe/Amsterdam',
            ])
            ->add('key_deliver', CustomTimeType::class, $timeOptions)
            ->add('key_return', CustomTimeType::class, $timeOptions)
            ->add('num_attendants', IntegerType::class, [
                'label' => 'label.num_attendants',
                'required' => true,
            ])
            ->add('price', MoneyType::class, [
                'label' => 'label.price',
                'required' => false,
            ])
            ->add('paid', MoneyType::class, [
                'label' => 'label.paid',
                'required' => false,
            ])
            ->add('status', ChoiceType::class, [
                'label' => 'label.status',
                'choices' => array_flip(LeaseRequest::STATUSES),
                'required' => true,
            ]);
        if (!$options['signed_uploaded']) {
            $builder->add('contract_signed', FileType::class, [
                    'label' => 'label.upload_signed',
                    'attr' => array('class' => 'well'),
                    'data_class' => null,
                    'required' => false,
                ]);
        } else {
            $builder->add('remove_signed_contract', SubmitType::class, array(
                    'attr' => array(
                        'class' => 'btn btn-primary', ),
                        'label' => 'contract_signed_remove',
                ));
        }
        $builder->add('submit', SubmitType::class, array(
                 'attr' => array(
                     'class' => 'btn btn-primary', ),
                 'label' => 'action.edit',
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
        ]);
    }
}
