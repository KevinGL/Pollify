<?php

namespace App\Form;

use App\Entity\Poll;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Constraints as Assert;

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
            'mapped' => false,
            "constraints" => new Assert\Callback(["callback" => static function (string $data, ExecutionContextInterface $context)
            {
                $nbItems = count(explode("\r\n", $data));

                if($nbItems < 2)
                {
                    $context->buildViolation("Merci de définir minimum 2 choix par sondage")
                    ->addViolation();
                }

                if($nbItems > 10)
                {
                    $context->buildViolation("Merci de définir maximum 10 choix par sondage")
                    ->addViolation();
                }
            }])
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
