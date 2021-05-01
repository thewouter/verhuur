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

use App\Entity\FrontMessage;
use App\Form\Type\DateTimePickerType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

/**
 * Defines the form used to edit an FrontMessage.
 *
 * @author Wouter van Harten <wouter@woutervanharten.nl>
 */
class FrontMessageType extends AbstractType {
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void {
        $builder
            ->add('title', TextType::class, array(
                'label' => 'label.title',
                'required' => true,
            ))
            ->add('body', TextareaType::class, array(
                'label' => 'label.body',
                'required' => true,
            ))
            ->add('start_date', DateTimePickerType::class, array(
                'label' => 'label.start_date_message',
                'required' => true,
            ))
            ->add('end_date', DateTimePickerType::class, array(
                'label' => 'label.end_date_message',
                'required' => true,
            ))
            ->add('submit', SubmitType::class, array(
                 'label' => 'label.submit',
                 'attr' => array('class' => 'btn btn-primary'),
             ));
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver): void {
        $resolver->setDefaults([
            'data_class' => FrontMessage::class,
        ]);
    }
}
