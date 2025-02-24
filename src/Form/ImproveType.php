<?php

namespace App\Form;

use App\Entity\Improve;
use App\Entity\ImproveGroup;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ImproveType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Názov',
            ])
            ->add('createdAt', DateType::class, [
                'label' => 'Začiatok',
            ])
            ->add('onlyWorkingDays',CheckboxType::class, [
                'label' => 'Iba pracovné dni',
                'required' => false,
            ])
            ->add('improveGroup',EntityType::class, [
                'class' => ImproveGroup::class,
                'label' => 'Skupina',
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Odoslať',
                'attr' => [
                    'class' => 'btn btn-success',
                ],
            ]);

    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Improve::class,
        ]);
    }
}
