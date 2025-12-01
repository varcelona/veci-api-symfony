<?php

namespace App\Form;

use App\Entity\UserProfile;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints;
use Symfony\Component\Form\Extension\Core\Type as CoreType;

class UserProfileType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('companyRole', CoreType\TextType::class, [
                'attr' => [
                    'placeholder' => 'form.placeholder.role_company',
                    'class' => 'form-control-lg shadow-none'
                ],
                'label_attr' => [
                    'class' => 'd-flex align-items-center fs-6 fw-semibold mb-2'
                ],
                'label' => 'form.label.role_company',
                'required' => false
            ])
            ->add('firstName', CoreType\TextType::class, [
                'attr' => [
                    'placeholder' => 'form.placeholder.first_name',
                    'autofocus' => 'autofocus',
                    'class' => 'form-control-lg shadow-none'
                ],
                'label_attr' => [
                    'class' => 'd-flex align-items-center fs-6 fw-semibold mb-2'
                ],
                'label' => 'form.label.first_name',
                'required' => true,
                'constraints' => [
                    new Constraints\NotBlank([
                        'message' => 'Please enter the user first name',
                    ]),
                ],
            ])
            ->add('lastName', CoreType\TextType::class, [
                'attr' => [
                    'placeholder' => 'form.placeholder.last_name',
                    'class' => 'form-control-lg shadow-none'
                ],
                'label_attr' => [
                    'class' => 'd-flex align-items-center fs-6 fw-semibold mb-2'
                ],
                'label' => 'form.label.last_name',
                'required' => true,
                'constraints' => [
                    new Constraints\NotBlank([
                        'message' => 'Please enter the user last name',
                    ]),
                ],
            ])
            ->add('displayName', CoreType\TextType::class, [
                'attr' => [
                    'placeholder' => 'form.placeholder.display_name',
                    'class' => 'form-control-lg shadow-none'
                ],
                'label_attr' => [
                    'class' => 'd-flex align-items-center fs-6 fw-semibold mb-2'
                ],
                'label' => 'form.label.display_name',
                'required' => false
            ])
            ->add('description', CoreType\TextareaType::class, [
                'attr' => [
                    'placeholder' => 'form.placeholder.description',
                    'class' => 'form-control-lg shadow-none',
                    'data-ckeditor5-target' => 'ckeditor5Textarea',
                    'data-config-name' => 'basic'
                ],
                'label_attr' => [
                    'class' => 'd-flex align-items-center fs-6 fw-semibold mb-2'
                ],
                'label' => 'form.label.description',
                'required' => false
            ])
            ->add('image', ImageType::class, [
                'label' => 'form.label.image',
                'required' => false,
                'entity_type' => 'user'
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => UserProfile::class
        ]);
    }
}
