<?php

namespace App\Form;

use App\Entity\Publisher;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PublisherType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('address', TextType::class, [
                'label'    => 'Address',
                'required' => true,
            ])
            ->add('url', UrlType::class, [
                'label'    => 'Publisher url',
                'required' => true,
            ])
            ->add('number', TextType::class, [
                'label'    => 'Dedicated number',
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Publisher::class,
        ]);
    }
}
