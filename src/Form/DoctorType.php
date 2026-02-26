<?php

namespace App\Form;

use App\Entity\Doctor;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Vich\UploaderBundle\Form\Type\VichImageType;

class DoctorType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nome',
                'attr' => ['class' => 'form-input']
            ])
            ->add('crm', TextType::class, [
                'label' => 'CRM',
                'required' => false,
                'attr' => ['class' => 'form-input']
            ])
            ->add('specialty', TextType::class, [
                'label' => 'Especialidade',
                'required' => false,
                'attr' => ['class' => 'form-input']
            ])
            ->add('imageFile', VichImageType::class, [
                'label' => 'Foto',
                'required' => false,
                'allow_delete' => true,
                'download_uri' => true,
                'image_uri' => true,
            ])
            ->add('bio', TextareaType::class, [
                'label' => 'Biografia',
                'required' => false,
                'attr' => ['rows' => 4, 'class' => 'form-textarea editor-html']
            ])
            ->add('isActive', \Symfony\Component\Form\Extension\Core\Type\CheckboxType::class, [
                'label' => 'Ativo',
                'required' => false,
                'attr' => [
                    'class' => 'peer sr-only' // Tailwind custom switch class if applicable, or generic
                ],
                'label_attr' => [
                    'class' => 'inline-flex items-center cursor-pointer'
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Doctor::class,
        ]);
    }
}
