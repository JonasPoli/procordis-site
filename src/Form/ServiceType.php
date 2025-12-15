<?php

namespace App\Form;

use App\Entity\Service;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Vich\UploaderBundle\Form\Type\VichImageType;

class ServiceType extends AbstractType
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
                'required' => false,
                'attr' => ['class' => 'form-input']
            ])
            ->add('icon', TextType::class, [
                'label' => 'Ícone (Classes CSS ou Link SVG)',
                'required' => false,
                'attr' => ['class' => 'form-input']
            ])
            ->add('imageFile', VichImageType::class, [
                'label' => 'Imagem Destacada',
                'required' => false,
                'allow_delete' => true,
                'download_uri' => true,
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Descrição Curta',
                'attr' => ['rows' => 3, 'class' => 'form-textarea editor-html']
            ])
            ->add('content', TextareaType::class, [
                'label' => 'Conteúdo Completo (HTML)',
                'attr' => ['rows' => 10, 'class' => 'form-textarea editor-html']
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Service::class,
        ]);
    }
}
