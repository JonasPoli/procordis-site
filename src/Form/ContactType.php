<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\NotBlank;

class ContactType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Seu Nome',
                'attr' => ['placeholder' => 'Digite seu nome completo'],
                'constraints' => [new NotBlank(['message' => 'Por favor, digite seu nome.'])],
            ])
            ->add('email', EmailType::class, [
                'label' => 'Seu E-mail',
                'attr' => ['placeholder' => 'exemplo@email.com'],
                'constraints' => [
                    new NotBlank(['message' => 'Por favor, digite seu e-mail.']),
                    new Email(['message' => 'Por favor, digite um e-mail válido.']),
                ],
            ])
            ->add('subject', TextType::class, [
                'label' => 'Assunto',
                'attr' => ['placeholder' => 'Sobre o que deseja falar?'],
                'constraints' => [new NotBlank(['message' => 'Por favor, digite o assunto.'])],
            ])
            ->add('message', TextareaType::class, [
                'label' => 'Mensagem',
                'attr' => ['rows' => 5, 'placeholder' => 'Digite sua mensagem aqui...'],
                'constraints' => [new NotBlank(['message' => 'Por favor, digite sua mensagem.'])],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([]);
    }
}
