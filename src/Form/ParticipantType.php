<?php

namespace App\Form;

use App\Entity\Campus;
use App\Entity\Participant;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ParticipantType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email')
            ->add('pseudo')
            ->add('nom')
            ->add('prenom')
            ->add('telephone')
            ->add('campus', EntityType::class, [
                'class' => Campus::class,
                'choice_label' => 'nom',
            ])
            ->add('password', PasswordType::class, [
                'label' => 'Mot de passe', // Changement du label
                'mapped' => false, // Ne pas mapper ce champ à l'entité
                'required' => false, // Pas obligatoire
            ])
            ->add('confirm_password', PasswordType::class, [
                'label' => 'Confirmation du mot de passe', // Label pour le champ de confirmation
                'mapped' => false, // Ne pas mapper ce champ à l'entité
                'required' => false, // Pas obligatoire
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Participant::class,
        ]);
    }
}
