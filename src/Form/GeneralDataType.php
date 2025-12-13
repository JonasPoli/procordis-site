<?php

namespace App\Form;

use App\Entity\GeneralData;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class GeneralDataType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('address', TextType::class, [
                'label' => 'Endereço Completo',
                'required' => false,
                'attr' => ['class' => 'form-input']
            ])
            ->add('phone', TextType::class, [
                'label' => 'Telefone Principal',
                'required' => false,
                'attr' => ['class' => 'form-input']
            ])
            ->add('whatsapp', TextType::class, [
                'label' => 'WhatsApp (Link ou Número)',
                'required' => false,
                'attr' => ['class' => 'form-input']
            ])
            ->add('email', EmailType::class, [
                'label' => 'E-mail de Contato',
                'required' => false,
                'attr' => ['class' => 'form-input']
            ])
            ->add('facebook', TextType::class, [
                'label' => 'Link do Facebook',
                'required' => false,
                'attr' => ['class' => 'form-input']
            ])
            ->add('instagram', TextType::class, [
                'label' => 'Link do Instagram',
                'required' => false,
                'attr' => ['class' => 'form-input']
            ])
            ->add('mapEmbedCode', TextareaType::class, [
                'label' => 'Código Embed do Google Maps (Iframe)',
                'required' => false,
                'attr' => ['rows' => 4, 'class' => 'form-textarea', 'placeholder' => '<iframe src="..."></iframe>']
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => GeneralData::class,
        ]);
    }
}
