<?php

namespace App\Form;

use App\Entity\PageSeo;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PageSeoType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('homePageTitle', TextType::class, ['label' => 'Home: Título SEO', 'required' => false])
            ->add('homePageDescription', TextareaType::class, ['label' => 'Home: Descrição SEO', 'required' => false, 'attr' => ['rows' => 2]])

            ->add('aboutPageTitle', TextType::class, ['label' => 'Quem Somos: Título SEO', 'required' => false])
            ->add('aboutPageDescription', TextareaType::class, ['label' => 'Quem Somos: Descrição SEO', 'required' => false, 'attr' => ['rows' => 2]])

            ->add('servicesPageTitle', TextType::class, ['label' => 'Serviços: Título SEO', 'required' => false])
            ->add('servicesPageDescription', TextareaType::class, ['label' => 'Serviços: Descrição SEO', 'required' => false, 'attr' => ['rows' => 2]])

            ->add('newsPageTitle', TextType::class, ['label' => 'Notícias: Título SEO', 'required' => false])
            ->add('newsPageDescription', TextareaType::class, ['label' => 'Notícias: Descrição SEO', 'required' => false, 'attr' => ['rows' => 2]])

            ->add('contactPageTitle', TextType::class, ['label' => 'Contato: Título SEO', 'required' => false])
            ->add('contactPageDescription', TextareaType::class, ['label' => 'Contato: Descrição SEO', 'required' => false, 'attr' => ['rows' => 2]])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => PageSeo::class,
        ]);
    }
}
