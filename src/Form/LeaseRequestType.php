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
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\CallbackTransformer;

/**
 * Defines the form used to create leaseRequests.
 *
 * @author Wouter van Harten <wouter@woutervanharten.nl>
 */
class LeaseRequestType extends AbstractType {
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void {
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
        $nextWeek = new \Datetime();
        $nextWeek->add(new \DateInterval('P7D'));
        $builder
            ->add('summary', TextareaType::class, [
                'label' => 'label.plans.question',
                'attr' => array('rows' => '7'),
            ])
            ->add('association_type', ChoiceType::class, [
                'label' => 'label.association_type',
                'choices' => LeaseRequest::ASSOCIATION_TYPES,
                'required' => true,
            ])
            ->add('association', TextType::class, [
                'label' => 'label.association',
                'required' => true,
            ])
            ->add('start_date', DateType::class, [
                'label' => 'label.start_date',
                'required' => true,
                'widget' => 'single_text',
                'years' => array(date('Y'), date('Y') + 1),
                'model_timezone' => 'Europe/Amsterdam',
                'attr' => array(
                    'min' => $nextWeek->format('Y-m-d'),
                    'onchange' => 'handler(event)',
                )
            ])
            ->add('end_date', DateType::class, [
                'label' => 'label.end_date',
                'required' => true,
                'widget' => 'single_text',
                'years' => array(date('Y'), date('Y') + 1),
                'model_timezone' => 'Europe/Amsterdam',
                'attr' => array(
                    'min' => (new \DateTime())->format('Y-m-d'),
                )
            ])
            ->add('key_deliver', ChoiceType::class, [
                'label' => 'label.start_time',
                'choices' => LeaseRequest::KEYTIMES,
            ])
            ->add('key_return', ChoiceType::class, [
                'label' => 'label.end_time',
                'choices' => LeaseRequest::KEYTIMES,
            ])
            ->add('num_attendants', IntegerType::class, [
                'label' => 'label.num_attendants',
                'required' => true,
            ])
            ->add('checked_calendar', CheckboxType::class, array(
                    'data_class' => null,
                    'required' => true,
                    'mapped' => false,
                    'label' => ' ',
                ))
            ->add('submit', SubmitType::class, array(
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
            'label' => "",
        ]);
    }
}
