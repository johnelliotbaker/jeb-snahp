<?php
namespace jeb\snahp\Apps\RequestForm\Types;

use \Symfony\Component\Form\AbstractType;
use \Symfony\Component\Form\FormBuilderInterface;
use \Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use \Symfony\Component\Validator\Constraints as Assert;

class VideoCodecType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)/*{{{*/
    {
        $defaultChoices = [
            '264' => '264',
            '265' => '265',
        ];
        $builder
            ->add(
                'videoCodec',
                ChoiceType::class,
                [
                    'choices' => $defaultChoices,
                    'data' => '265',
                    'attr' => ['tabindex' => 2],
                    'compound' => false,
                ]
            );
        ;
    }/*}}}*/
}
