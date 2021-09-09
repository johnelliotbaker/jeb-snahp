<?php
namespace jeb\snahp\Apps\RequestForm\Types;

use \Symfony\Component\Form\AbstractType;
use \Symfony\Component\Form\FormBuilderInterface;
use \Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use \Symfony\Component\Validator\Constraints as Assert;

class FileHostType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $defaultChoices = [
            'Mega or Zippy' => 'mega, zippy',
            'Mega' => 'mega',
            'Zippy' => 'zippy',
        ];
        $builder
            ->add(
                'filehost',
                ChoiceType::class,
                [
                    'choices' => $defaultChoices,
                    'compound' => false,
                    'attr' => ['tabindex' => 2],
                ]
            );
        ;
    }
}
