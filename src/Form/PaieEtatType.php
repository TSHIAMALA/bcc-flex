<?php

namespace App\Form;

use App\Entity\PaieEtat;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PaieEtatType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('montant_total', NumberType::class, [
                'label' => 'Montant Total',
                'required' => false,
                'scale' => 2,
                'attr' => [
                    'class' => 'form-input',
                    'placeholder' => '0.00'
                ]
            ])
            ->add('montant_paye', NumberType::class, [
                'label' => 'Montant Payé',
                'required' => false,
                'scale' => 2,
                'attr' => [
                    'class' => 'form-input',
                    'placeholder' => '0.00'
                ]
            ])
            ->add('montant_restant', NumberType::class, [
                'label' => 'Montant Restant',
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
            'data_class' => PaieEtat::class,
        ]);
    }
}
