<?php

namespace App\Form;

use App\Entity\Fail;
use App\Entity\Improve;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FailType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('createdAt', null, [
                'widget' => 'single_text',
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
            'data_class' => Fail::class,
        ]);
    }
}
