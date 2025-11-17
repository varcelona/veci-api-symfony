<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Translation\TranslatableMessage;


class ResetPasswordRequestFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class, [
                'attr' => ['autocomplete' => 'email'],
                'label' => 'form.label.email',
                'attr' => [
                    'placeholder' => 'form.placeholder.email',
                    'class' => 'form-control-lg form-control-solid shadow-none',
                    'autofocus' => 'autofocus',
                    'tabindex' => 1,
                ],
                'help' => new TranslatableMessage('ui.reset_password'),
                'constraints' => [
                    new NotBlank([
                        'message' => 'Please enter your email',
                    ]),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([]);
    }
}
