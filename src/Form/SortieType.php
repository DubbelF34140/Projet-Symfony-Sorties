<?php
namespace App\Form;

use App\Entity\Etat;
use App\Entity\Lieu;
use App\Entity\Sortie;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SortieType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom')
            ->add('dateHeureDebut', DateTimeType::class, [
                'widget' => 'single_text', // Utilisation correcte du widget pour les dates
                'html5' => true,
                'attr' => ['class' => 'datetimepicker'],
            ])
            ->add('dateLimiteInscription', DateTimeType::class, [
                'widget' => 'single_text', // Utilisation correcte du widget pour les dates
                'html5' => true,
                'attr' => ['class' => 'datepicker'],
            ])
            ->add('nbInscriptionMax', IntegerType::class) // IntegerType pour nombre de places
            ->add('duree', IntegerType::class) // IntegerType pour la durée
            ->add('infosSortie', TextareaType::class)
            ->add('etat', EntityType::class, [
                'class' => Etat::class, // Correction: "Etat" avec majuscule
                'choice_label' => 'nom', // Assurez-vous que le champ 'nom' existe dans votre entité Etat
            ])
            ->add('lieu', EntityType::class, [
                'class' => Lieu::class, // Correction: "Lieu" avec majuscule
                'choice_label' => 'nom', // Assurez-vous que le champ 'nom' existe dans votre entité Lieu
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Sortie::class,
        ]);
    }
}

