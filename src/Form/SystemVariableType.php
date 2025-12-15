<?php

namespace App\Form;

use App\Entity\SystemVariable;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SystemVariableType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('variableKey', TextType::class, [
                'label' => 'Chave da Variável',
                'attr' => ['class' => 'form-input', 'readonly' => true] // Often keys shouldn't change
            ])
            ->add('variableValue', TextareaType::class, [
                'label' => 'Valor',
                'attr' => ['class' => 'form-textarea', 'rows' => 3]
            ])
            ->add('description', TextType::class, [
                'label' => 'Descrição',
                'required' => false,
                'attr' => ['class' => 'form-input']
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => SystemVariable::class,
        ]);
    }
}
