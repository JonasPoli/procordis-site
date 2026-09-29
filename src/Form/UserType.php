<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Cadastro/edição de usuários do painel (somente administradores).
 *
 * O tipo de acesso e a senha não são mapeados diretamente na entidade:
 * o controller aplica o tipo com User::setAccessType() e faz o hash da senha.
 */
class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $passwordConstraints = [
            new Length(min: 8, max: 4096, minMessage: 'A senha deve ter pelo menos {{ limit }} caracteres.'),
        ];

        if ($options['require_password']) {
            $passwordConstraints[] = new NotBlank(message: 'Informe uma senha.');
        }

        $builder
            ->add('email', EmailType::class, [
                'label' => 'E-mail (usado para entrar no painel)',
                'empty_data' => '',
                'attr' => ['autocomplete' => 'off'],
            ])
            ->add('accessType', ChoiceType::class, [
                'label' => 'Tipo de acesso',
                'mapped' => false,
                'choices' => User::ACCESS_TYPES,
                'expanded' => true,
                'data' => $options['access_type'],
                'disabled' => $options['lock_access_type'],
                'constraints' => [new NotBlank(message: 'Escolha o tipo de acesso.')],
                'help' => $options['lock_access_type']
                    ? 'Você não pode alterar o seu próprio tipo de acesso.'
                    : 'Administrador: acesso total ao painel. Jornalista: apenas notícias, categorias de notícias e galeria.',
            ])
            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'mapped' => false,
                'required' => $options['require_password'],
                'invalid_message' => 'As senhas informadas não conferem.',
                'first_options' => [
                    'label' => $options['require_password'] ? 'Senha' : 'Nova senha',
                    'help' => $options['require_password']
                        ? 'Mínimo de 8 caracteres.'
                        : 'Deixe em branco para manter a senha atual.',
                    'attr' => ['autocomplete' => 'new-password'],
                ],
                'second_options' => [
                    'label' => 'Confirme a senha',
                    'attr' => ['autocomplete' => 'new-password'],
                ],
                'constraints' => $passwordConstraints,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'require_password' => true,
            'access_type' => User::ROLE_JORNALISTA,
            'lock_access_type' => false,
        ]);

        $resolver->setAllowedTypes('require_password', 'bool');
        $resolver->setAllowedTypes('access_type', ['string', 'null']);
        $resolver->setAllowedTypes('lock_access_type', 'bool');
    }
}
