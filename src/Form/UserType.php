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

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

/**
 * Defines the form used to edit an user.
 *
 * @author Romain Monteil <monteil.romain@gmail.com>
 */
class UserType extends AbstractType {
    /**
     * {@inheritdoc}
     */
     public function buildForm(FormBuilderInterface $builder, array $options): void {
         if (!$options['google_additional_info']){
             $builder
                 ->add('username', TextType::class, [
                     'label' => 'label.username',
                 ])
                 ->add('fullName', TextType::class, [
                     'label' => 'label.fullname',
                 ]);
             }
         $builder
             ->add('phone', TextType::class, [
                 'label' => 'label.phone',
             ])
             ->add('address', TextType::class, [
                 'label' => 'label.address',
             ]);
         if (!$options['google_additional_info']){
             $builder
                 ->add('email', EmailType::class, [
                     'label' => 'label.email',
                 ]);
         }

         if ($options['password'] && !$options['google_additional_info']) {
             $builder
                 ->add('password', PasswordType::class, [
                     'label' => 'label.password',
                 ]);
         }
         $builder->add('submit', SubmitType::class, array(
                 'label' => 'label.submit',
                 'attr' => array('class' => 'btn btn-primary'),
             ));
     }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver): void {
        $resolver->setDefaults([
            'data_class' => User::class,
            'password' => true,
            'google_additional_info' => false,
        ]);
    }
}
