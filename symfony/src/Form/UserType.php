<?php

namespace App\Form;

use App\Entity\User;
use App\Form\UserProfileType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Validator\Constraints;
use Symfony\Component\Form\Extension\Core\Type as CoreType;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Bundle\SecurityBundle\Security;


class UserType extends AbstractType
{
    public function __construct(private Security $security) {}

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', CoreType\EmailType::class, [
                'required' => true,
                'attr' => [
                    'placeholder' => 'form.placeholder.email',
                    'class' => 'form-control-lg shadow-none'
                ],
                'label_attr' => [
                    'class' => 'd-flex align-items-center fs-6 fw-semibold mb-2'
                ],
                'label' => 'form.label.email',
                'required' => true,
                'constraints' => [
                    new Constraints\NotBlank([
                        'message' => 'Please enter your email',
                    ]),
                    new Constraints\Email([
                        'message' => 'The email {{ value }} is not a valid email',
                    ])
                ],
            ])
            ->add('plainPassword', CoreType\RepeatedType::class, [
                'type' => CoreType\PasswordType::class,
                'label' => 'form.label.password',
                'first_options' => [
                    'attr' => [
                        'autocomplete' => 'new-password',
                        'placeholder' => 'form.placeholder.new_password',
                        'class' => 'form-control-lg shadow-none'
                    ],
                    'toggle' => true,
                    'hidden_label' => 'form.label.hide_password',
                    'visible_label' => 'form.label.show_password',
                    'label_attr' => [
                        'class' => 'd-flex align-items-center fs-6 fw-semibold mb-2'
                    ],
                    'label' => 'form.label.new_password',
                    'hash_property_path' => 'password'
                ],
                'second_options' => [
                    'attr' => [
                        'placeholder' => 'form.placeholder.repeat_password',
                        'class' => 'form-control-lg shadow-none'
                    ],
                    'toggle' => true,
                    'hidden_label' => 'form.label.hide_password',
                    'visible_label' => 'form.label.show_password',
                    'label_attr' => [
                        'class' => 'd-flex align-items-center fs-6 fw-semibold mb-2'
                    ],
                    'label' => 'form.label.repeat_password'
                ],
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new Constraints\Length([
                        'min' => 8,
                        'minMessage' => 'Your password should be at least {{ limit }} characters',
                        // max length allowed by Symfony for security reasons
                        'max' => 4096,
                    ]),
                ]
            ])
            ->add('enabled', CoreType\CheckboxType::class, [
                'label' => 'form.label.enabled',
                'attr' => [
                    'class' => 'form-check-input'
                ],
                'label_attr' => [
                    'class' => 'fw-semibold fs-6'
                ],
                'required' => false
            ])
            ->add('profile', UserProfileType::class, [
                'label' => false,
                'required' => true
            ])
        ;

        // Determinar choices según rol del current user (aquí lo definimos en buildForm)
        $availableRoles = [];
        if ($this->security->isGranted('ROLE_ADMIN')) {
            $availableRoles = [
                User::ROLE_ADMIN    => User::ROLE_ADMIN,
                User::ROLE_MERCHANT => User::ROLE_MERCHANT,
                User::ROLE_CUSTOMER => User::ROLE_CUSTOMER,
            ];
        } elseif ($this->security->isGranted('ROLE_MERCHANT')) {
            $availableRoles = [
                'Customer' => User::ROLE_CUSTOMER,
            ];
        } else {
            // Por defecto nadie ve selector; lo hacemos hidden -> customer
            $availableRoles = [
                'Customer' => User::ROLE_CUSTOMER,
            ];
        }

        // Añadimos el campo roles en el builder (aquí SI podemos añadir el transformer)
        $builder->add('roles', CoreType\ChoiceType::class, [
            'choices' => $availableRoles,
            'required' => true,
            'placeholder' => 'form.placeholder.role',
            // single select (representamos internamente como array con transformer)
        ]);

        $builder->get('roles')->addModelTransformer(new CallbackTransformer(
            fn($rolesAsArray) => is_array($rolesAsArray) && count($rolesAsArray) ? $rolesAsArray[0] : null,
            fn($rolesAsString) => $rolesAsString ? [$rolesAsString] : []
        ));


        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event): void {
            $form = $event->getForm();
            $entity = $event->getData();

            if (!$entity || null === $entity->getId()) {
                $form
                    ->add('plainPassword', CoreType\RepeatedType::class, [
                        'type' => CoreType\PasswordType::class,
                        'label' => 'form.label.password',
                        'first_options' => [
                            'attr' => [
                                'autocomplete' => 'new-password',
                                'placeholder' => 'form.placeholder.new_password',
                                'class' => 'form-control-lg shadow-none'
                            ],
                            'toggle' => true,
                            'hidden_label' => 'form.label.hide_password',
                            'visible_label' => 'form.label.show_password',
                            'label_attr' => [
                                'class' => 'd-flex align-items-center fs-6 fw-semibold mb-2'
                            ],
                            'label' => 'form.label.new_password',
                            'hash_property_path' => 'password',
                            'required' => true
                        ],
                        'second_options' => [
                            'attr' => [
                                'placeholder' => 'form.placeholder.repeat_password',
                                'class' => 'form-control-lg shadow-none'
                            ],
                            'toggle' => true,
                            'hidden_label' => 'form.label.hide_password',
                            'visible_label' => 'form.label.show_password',
                            'label_attr' => [
                                'class' => 'd-flex align-items-center fs-6 fw-semibold mb-2'
                            ],
                            'label' => 'form.label.repeat_password',
                            'required' => true
                        ],
                        'mapped' => false,
                        'required' => true,
                        'constraints' => [
                            new Constraints\Length([
                                'min' => 8,
                                'minMessage' => 'Your password should be at least {{ limit }} characters',
                                // max length allowed by Symfony for security reasons
                                'max' => 4096,
                            ]),
                            new Constraints\NotBlank([
                                'message' => 'Please enter a valid password',
                            ]),
                        ],
                    ])
                ;
            }
        });

        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event): void {
            $data = $event->getData();
            if (!$data || !\is_array($data)) {
                return;
            }

            // Si el creador es MERCHANT, forzamos ROLE_CUSTOMER
            if ($this->security->isGranted('ROLE_MERCHANT')) {
                $data['roles'] = User::ROLE_CUSTOMER;
                $event->setData($data);
                return;
            }

            // Si no es ADMIN y enviaron roles no permitidos, forzamos CUSTOMER
            if (!$this->security->isGranted('ROLE_ADMIN')) {
                $data['roles'] = User::ROLE_CUSTOMER;
                $event->setData($data);
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class
        ]);
    }
}
