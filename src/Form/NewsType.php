<?php

namespace App\Form;

use App\Entity\News;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Vich\UploaderBundle\Form\Type\VichImageType;

class NewsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Título',
                'attr' => ['class' => 'form-input']
            ])
            ->add('slug', TextType::class, [
                'label' => 'Slug (URL)',
                'help' => 'Deixe vazio para gerar automaticamente (se implementado) ou preencha manual.',
                'attr' => ['class' => 'form-input']
            ])
            ->add('publishedAt', DateType::class, [
                'widget' => 'single_text',
                'label' => 'Data de Publicação',
                'input' => 'datetime_immutable',
                'attr' => ['class' => 'form-input']
            ])
            ->add('summary', TextareaType::class, [
                'label' => 'Resumo',
                'attr' => ['rows' => 3, 'class' => 'form-textarea']
            ])
            ->add('content', TextareaType::class, [
                'label' => 'Conteúdo Completo (HTML)',
                'attr' => ['rows' => 10, 'class' => 'form-textarea']
            ])
            ->add('imageFile', VichImageType::class, [
                'label' => 'Imagem de Capa',
                'required' => false,
                'allow_delete' => true,
                'download_uri' => true,
                'image_uri' => true,
                'asset_helper' => true,
            ])
            ->add('seoTitle', TextType::class, [
                'label' => 'Título SEO',
                'required' => false,
                'attr' => ['class' => 'form-input']
            ])
            ->add('seoDescription', TextareaType::class, [
                'label' => 'Descrição SEO',
                'required' => false,
                'attr' => ['rows' => 2, 'class' => 'form-textarea']
            ])
            ->add('imageAlt', TextType::class, [
                'label' => 'Texto Alternativo da Imagem (Alt)',
                'required' => false,
                'attr' => ['class' => 'form-input']
            ])
            ->add('canonicalUrl', TextType::class, [
                'label' => 'URL Canônica',
                'required' => false,
                'attr' => ['class' => 'form-input']
            ])
            ->add('isNoIndex', CheckboxType::class, [
                'label' => 'NoIndex (Ocultar do Google)',
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => News::class,
        ]);
    }
}
