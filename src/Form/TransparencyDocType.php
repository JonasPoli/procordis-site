<?php

namespace App\Form;

use App\Entity\Transparency;
use App\Entity\TransparencyDoc;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Vich\UploaderBundle\Form\Type\VichFileType;

class TransparencyDocType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('transparency', EntityType::class, [
                'class' => Transparency::class,
                'choice_label' => 'title',
                'label' => 'Categoria de Transparência',
                'placeholder' => 'Selecione uma categoria...',
                'attr' => ['class' => 'form-select']
            ])
            ->add('title', TextType::class, [
                'label' => 'Título do Documento',
                'attr' => ['class' => 'form-input']
            ])
            ->add('year', IntegerType::class, [
                'label' => 'Ano de Referência',
                'required' => false,
                'attr' => ['class' => 'form-input']
            ])
            ->add('month', ChoiceType::class, [
                'label' => 'Mês de Referência',
                'required' => false,
                'choices' => array_flip([
                    1 => 'Janeiro',
                    2 => 'Fevereiro',
                    3 => 'Março',
                    4 => 'Abril',
                    5 => 'Maio',
                    6 => 'Junho',
                    7 => 'Julho',
                    8 => 'Agosto',
                    9 => 'Setembro',
                    10 => 'Outubro',
                    11 => 'Novembro',
                    12 => 'Dezembro'
                ]),
                'attr' => ['class' => 'form-select']
            ])
            ->add('file', VichFileType::class, [
                'label' => 'Arquivo (PDF/DOC)',
                'required' => false,
                'allow_delete' => true,
                'download_uri' => true,
                'asset_helper' => true,
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Descrição',
                'required' => false,
                'attr' => ['rows' => 2, 'class' => 'form-textarea editor-html']
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => TransparencyDoc::class,
        ]);
    }
}
