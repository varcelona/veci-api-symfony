<?php

namespace App\Form;

use App\Entity\Image;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Vich\UploaderBundle\Form\Type\VichImageType;
use Symfony\Bridge\Doctrine\Form\Type as FormType;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\Extension\Core\Type as CoreType;
use Symfony\Component\Form\FormError;

class ImageType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $thumbnailFormat = '4x3';
        $entityType = null;
        $required = false;

        if (in_array('thumbnail_format', $options)) {
            $thumbnailFormat = $options['thumbnail_format'];
        }

        if (in_array('entity_type', $options)) {
            $entityType = $options['entity_type'];
        }

        if (in_array('required', $options)) {
            $required = $options['required'];
        }

        $builder
            ->add('imageFile', VichImageType::class, [
                'label' => false,
                'required' => false,
                'allow_delete' => false,
                'download_uri' => false,
                'image_uri' => true,
                'attr' => [
                    'thumbnail_format' => $thumbnailFormat
                ]
            ])
            ->add('entityType', CoreType\HiddenType::class, [
                'label' => false,
                'required' => true,
                'data' => $entityType
            ]);
        ;

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) use ($required, $thumbnailFormat): void {
            $form = $event->getForm();
            $entity = $event->getData();

            if ($required) {
                if (!$entity || !$entity->getImage() || !$entity->getImage()->getSize()) {
                    $form
                        ->add('imageFile', VichImageType::class, [
                            'label' => false,
                            'required' => true,
                            'allow_delete' => false,
                            'download_uri' => false,
                            'attr' => [
                                'thumbnail_format' => $thumbnailFormat
                            ]
                        ]);
                }
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Image::class,
            'entity_type' => null,
            'thumbnail_format' => '4x3'
        ]);
    }
}
