<?php

namespace App\Form;

use App\Entity\Banques;
use App\Entity\TransactionsUsd;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TransactionsUsdType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('banque', EntityType::class, [
                'class' => Banques::class,
                'choice_label' => 'nom',
                'label' => 'Banque',
                'attr' => ['class' => 'form-select']
            ])
            ->add('type_transaction', ChoiceType::class, [
                'choices' => [
                    'Achat' => 'ACHAT',
                    'Vente' => 'VENTE',
                ],
                'label' => 'Type',
                'attr' => ['class' => 'form-select']
            ])
            ->add('cours', NumberType::class, [
                'label' => 'Cours',
                'scale' => 4,
                'required' => false,
                'attr' => ['class' => 'form-input', 'placeholder' => '0.0000']
            ])
            ->add('volume_usd', NumberType::class, [
                'label' => 'Volume USD',
                'scale' => 2,
                'required' => false,
                'attr' => ['class' => 'form-input', 'placeholder' => '0.00']
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => TransactionsUsd::class,
        ]);
    }
}
