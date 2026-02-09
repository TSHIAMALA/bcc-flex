<?php

namespace App\Form;

use App\Entity\EncoursBcc;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EncoursBccType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('encours_ot_bcc', NumberType::class, [
                'label' => 'Encours OT BCC',
                'required' => false,
                'scale' => 2,
                'attr' => [
                    'class' => 'form-input',
                    'placeholder' => '0.00'
                ]
            ])
            ->add('encours_b_bcc', NumberType::class, [
                'label' => 'Encours B BCC',
                'required' => false,
                'scale' => 2,
                'attr' => [
                    'class' => 'form-input',
                    'placeholder' => '0.00'
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => EncoursBcc::class,
        ]);
    }
}
