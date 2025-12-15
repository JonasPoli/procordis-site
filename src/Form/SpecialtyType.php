<?php

namespace App\Form;

use App\Entity\Specialty;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SpecialtyType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nome da Especialidade',
                'attr' => ['class' => 'form-input']
            ])
            ->add('svgIcon', TextareaType::class, [
                'label' => 'Ícone SVG (Código Completo)',
                'help' => 'Cole o código <svg>...</svg> aqui',
                'required' => false,
                'attr' => ['rows' => 4, 'class' => 'form-textarea']
            ])
            ->add('sortOrder', IntegerType::class, [
                'label' => 'Ordem de Exibição',
                'required' => false,
                'attr' => ['class' => 'form-input']
            ])
            ->add('active', CheckboxType::class, [
                'label' => 'Ativo',
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Specialty::class,
        ]);
    }
}
