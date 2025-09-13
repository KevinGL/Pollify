<?php

namespace App\Form;

use App\Entity\Poll;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PollType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title')
            ->add('description')
            ->add('beginAt')
            ->add('endAt');

        if ($options['is_admin'])
        {
            $builder->add('user', EntityType::class,
            [
                "class" => User::class,
                "choice_label" => "username",
                'placeholder' => 'Sélectionner un utilisateur',
            ]);
        }

        $builder->add('choices', TextareaType::class,
        [
            'mapped' => false
        ]);

        $builder->add("save", SubmitType::class);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Poll::class,
            "is_admin" => false
        ]);
    }
}
