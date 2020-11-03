<?php
namespace jeb\snahp\Apps\RequestForm\Types;

use \Symfony\Component\Form\AbstractType;
use \Symfony\Component\Form\Extension\Core\Type as Type;
use \Symfony\Component\Form\FormBuilderInterface;
use \Symfony\Component\Validator\Constraints as Assert;
use jeb\snahp\Apps\RequestForm\Models\Game;

class GameType extends AbstractType
{
    public static $alias = 'game';
    const CLASSNAME = Game::class;

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('filehost', FileHostType::class)
            ->add(
                'platform',
                Type\TextType::class,
                [
                    'attr' => [
                        'class' => 'inputbox autowidth',
                        'size' => 16,
                        'tabindex' => 2,
                    ],
                    'constraints' => new Assert\Length(['min' => 2, 'max' => 16]),
                    'help' => 'Title will be tagged automatically',
                ]
            )
            ->add(
                'requestType',
                Type\HiddenType::class,
                [ 'attr' => ['value' => self::$alias] ]
            );
    }
}
