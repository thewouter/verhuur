<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Task;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TaskType extends AbstractType {
    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder
            ->add('requests', CollectionType::class, [
                'entry_type' => LeasePaidType::class,
                'entry_options' => ['label' => false],
            ])
            ->add('submit', SubmitType::class, array(
                'label' => 'label.edit',
                'attr' => array('class' => 'btn btn-primary'),
            ));
    }

    public function configureOptions(OptionsResolver $resolver) {
        $resolver->setDefaults([
            'data_class' => Task::class,
        ]);
    }
}
