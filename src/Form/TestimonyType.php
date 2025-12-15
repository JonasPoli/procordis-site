<?php

namespace App\Form;

use App\Entity\Testimony;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TestimonyType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('authorName', TextType::class, [
                'label' => 'Nome do Autor',
                'attr' => ['class' => 'form-input']
            ])
            ->add('authorRole', TextType::class, [
                'label' => 'Detalhes (ex: Local Guide)',
                'required' => false,
                'attr' => ['class' => 'form-input']
            ])
            ->add('rating', ChoiceType::class, [
                'label' => 'Avaliação (Estrelas)',
                'choices' => [
                    '5 Estrelas' => 5,
                    '4 Estrelas' => 4,
                    '3 Estrelas' => 3,
                    '2 Estrelas' => 2,
                    '1 Estrela' => 1,
                ],
                'attr' => ['class' => 'form-select']
            ])
            ->add('text', TextareaType::class, [
                'label' => 'Depoimento',
                'attr' => ['rows' => 5, 'class' => 'form-textarea']
            ])
            ->add('createdAt', DateType::class, [
                'label' => 'Data da Avaliação',
                'widget' => 'single_text',
                'input'  => 'datetime_immutable',
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
            'data_class' => Testimony::class,
        ]);
    }
}
