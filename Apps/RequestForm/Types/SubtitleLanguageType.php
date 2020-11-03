<?php
namespace jeb\snahp\Apps\RequestForm\Types;

use \Symfony\Component\Form\AbstractType;
use \Symfony\Component\Form\FormBuilderInterface;
use \Symfony\Component\Form\Extension\Core\Type\TextType;
use \Symfony\Component\Validator\Constraints as Assert;

class SubtitleLanguageType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)/*{{{*/
    {
        $builder
            ->add(
                'subtitle',
                TextType::class,
                [
                    'label' => 'Subtitle',
                    'attr' => ['tabindex' => 2, 'class' => 'inputbox autowidth', 'size' => 35],
                    'label' => 'Subtitle Language',
                    'help' => 'If you require subtitle, please specify language.',
                    'compound' => false,
                    'required' => false,
                ]
            );
        ;
    }/*}}}*/
}
