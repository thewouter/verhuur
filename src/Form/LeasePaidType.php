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
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\CallbackTransformer;

/**
 * Defines the form used to create and manipulate blog posts.
 *
 * @author Wouter van Harten <wouter@woutervanharten.nl>
 */
class LeasePaidType extends AbstractType {
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void {
        $builder
            ->add('paid', MoneyType::class, [
                'label' => false,
                'required' => false,
            ])
            ->add('deposit_retour', CheckBoxType::class, [
                'label' => false,
                'required' => false,
            ]);
        $builder->get('deposit_retour')
                ->addModelTransformer(new CallbackTransformer(
                    function ($boolean) {
                        return (boolean) $boolean;
                    },
                    function ($int) {
                        return (int) $int;
                    }
            ))
        ;
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
