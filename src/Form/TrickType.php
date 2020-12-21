<?php

namespace App\Form;

use App\Entity\Group;
use App\Entity\Trick;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TrickType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('picture', FileType::class, [
                'required' => false,
                'data_class' => null
            ])
            ->add('title', TextType::class, ['required' => false])
            ->add('content', TextareaType::class, ['required' => false])
            ->add('isPublished', CheckboxType::class, ['required' => false])
            ->add('group', ChoiceType::class, [
                'choices' => [
                    new Group('Groupe 1'),
                    new Group('Groupe 2'),
                    new Group('Groupe 3'),
                    new Group('Groupe 4'),
                ],
                'expanded' => true,
                'multiple' => false,
                'choice_value' => 'label',
                'choice_label' => function(?Group $group) {
                    return $group ? strtoupper($group->getLabel()) : '';
                    },
                    'choice_attr' => function(?Group $group) {
                        return $group ? ['class' => 'category_'.strtolower($group->getLabel())] : [];
                    },

                ]);

        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Trick::class,
        ]);
    }
}
