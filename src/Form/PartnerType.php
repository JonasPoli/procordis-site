<?php

namespace App\Form;

use App\Entity\Partner;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Vich\UploaderBundle\Form\Type\VichImageType;

class PartnerType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Nome / Título do Parceiro',
                'attr' => ['placeholder' => 'Ex: METROMED'],
            ])
            ->add('link', TextType::class, [
                'label' => 'Link do Site (opcional)',
                'required' => false,
                'attr' => ['placeholder' => 'https://exemplo.com.br (deixe em branco se não houver link)'],
            ])
            ->add('imageFile', VichImageType::class, [
                'label' => 'Logo do Parceiro',
                'required' => false,
                'allow_delete' => true,
                'download_uri' => false,
                'image_uri' => true,
                'asset_helper' => true,
            ])
            ->add('sortOrder', IntegerType::class, [
                'label' => 'Ordem de Exibição',
                'required' => false,
                'data' => $builder->getData() ? $builder->getData()->getSortOrder() ?? 0 : 0,
            ])
            ->add('active', CheckboxType::class, [
                'label' => 'Ativo no site',
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Partner::class,
        ]);
    }
}
