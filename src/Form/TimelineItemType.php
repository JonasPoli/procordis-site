<?php

namespace App\Form;

use App\Entity\TimelineItem;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Vich\UploaderBundle\Form\Type\VichImageType;

class TimelineItemType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('year', TextType::class, [
                'label' => 'Ano',
                'attr' => ['class' => 'form-input']
            ])
            ->add('title', TextType::class, [
                'label' => 'Título',
                'attr' => ['class' => 'form-input']
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Descrição',
                'required' => false,
                'attr' => ['class' => 'form-textarea editor-html', 'rows' => 4]
            ])
            ->add('imageFile', VichImageType::class, [
                'label' => 'Imagem',
                'required' => false,
                'allow_delete' => true,
                'download_uri' => true,
                'image_uri' => true,
            ])
            ->add('sortOrder', IntegerType::class, [
                'label' => 'Ordem',
                'attr' => ['class' => 'form-input']
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => TimelineItem::class,
        ]);
    }
}
