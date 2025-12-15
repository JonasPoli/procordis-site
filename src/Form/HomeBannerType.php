<?php

namespace App\Form;

use App\Entity\HomeBanner;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Vich\UploaderBundle\Form\Type\VichImageType;

class HomeBannerType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Título',
                'attr' => ['class' => 'form-input']
            ])
            ->add('subtitle', TextType::class, [
                'label' => 'Subtítulo',
                'required' => false,
                'attr' => ['class' => 'form-input']
            ])
            ->add('imageFile', VichImageType::class, [
                'label' => 'Imagem do Banner',
                'required' => false,
                'allow_delete' => true,
                'download_uri' => true,
                'image_uri' => true,
            ])
            ->add('btn1Text', TextType::class, [
                'label' => 'Botão 1 - Texto',
                'required' => false,
                'attr' => ['class' => 'form-input']
            ])
            ->add('btn1Link', TextType::class, [
                'label' => 'Botão 1 - Link',
                'required' => false,
                'attr' => ['class' => 'form-input']
            ])
            ->add('btn2Text', TextType::class, [
                'label' => 'Botão 2 - Texto',
                'required' => false,
                'attr' => ['class' => 'form-input']
            ])
            ->add('btn2Link', TextType::class, [
                'label' => 'Botão 2 - Link',
                'required' => false,
                'attr' => ['class' => 'form-input']
            ])
            ->add('sortOrder', IntegerType::class, [
                'label' => 'Ordem',
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
            'data_class' => HomeBanner::class,
        ]);
    }
}
