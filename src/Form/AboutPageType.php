<?php

namespace App\Form;

use App\Entity\AboutPage;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Vich\UploaderBundle\Form\Type\VichImageType;

class AboutPageType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('homeTitle', TextType::class, [
                'label' => 'Título na Home',
                'required' => false,
                'attr' => ['class' => 'form-input']
            ])
            ->add('homeSummary', TextareaType::class, [
                'label' => 'Resumo na Home',
                'required' => false,
                'attr' => ['class' => 'form-textarea editor-html', 'rows' => 4]
            ])
            ->add('homeImageFile', VichImageType::class, [
                'label' => 'Imagem na Home',
                'required' => false,
                'allow_delete' => true,
                'download_uri' => true,
                'image_uri' => true,
            ])
            ->add('mainTitle', TextType::class, [
                'label' => 'Título Principal (Página Quem Somos)',
                'required' => false,
                'attr' => ['class' => 'form-input']
            ])
            ->add('mainContent', TextareaType::class, [
                'label' => 'Conteúdo Principal (HTML)',
                'required' => false,
                'attr' => ['class' => 'form-textarea editor-html', 'rows' => 10]
            ])
            ->add('vision', TextareaType::class, [
                'label' => 'Visão',
                'required' => false,
                'attr' => ['class' => 'form-textarea editor-html', 'rows' => 4]
            ])
            ->add('mission', TextareaType::class, [
                'label' => 'Missão',
                'required' => false,
                'attr' => ['class' => 'form-textarea editor-html', 'rows' => 4]
            ])
            ->add('ourValues', TextareaType::class, [
                'label' => 'Valores',
                'required' => false,
                'attr' => ['class' => 'form-textarea editor-html', 'rows' => 4]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => AboutPage::class,
        ]);
    }
}
