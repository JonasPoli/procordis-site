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
use Vich\UploaderBundle\Form\Type\VichImageType;

class SpecialtyType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nome da Especialidade',
                'attr' => ['class' => 'form-input']
            ])
            ->add('slug', TextType::class, [
                'label' => 'Slug (URL)',
                'required' => false,
                'help' => 'Deixe vazio para gerar automaticamente.',
                'attr' => ['class' => 'form-input']
            ])
            ->add('usedToDiagnose', TextType::class, [
                'label' => 'Usado para Diagnosticar',
                'required' => false,
                'help' => 'Exemplo: "Alterações estruturais e funcionais do coração"',
                'attr' => ['class' => 'form-input']
            ])
            ->add('shortDescription', TextareaType::class, [
                'label' => 'Descrição Curta (para Cards)',
                'required' => false,
                'attr' => ['rows' => 3, 'class' => 'form-textarea']
            ])
            ->add('fullText', TextareaType::class, [
                'label' => 'Descrição Completa (HTML)',
                'required' => false,
                'attr' => ['rows' => 10, 'class' => 'form-textarea editor-html']
            ])
            ->add('svgIcon', TextareaType::class, [
                'label' => 'Ícone SVG (Código Completo)',
                'help' => 'Cole o código <svg>...</svg> aqui',
                'required' => false,
                'attr' => ['rows' => 4, 'class' => 'form-textarea']
            ])
            ->add('imageFile', VichImageType::class, [
                'label' => 'Imagem Ilustrativa',
                'required' => false,
                'allow_delete' => true,
                'download_uri' => true,
                'image_uri' => true,
                'asset_helper' => true,
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
