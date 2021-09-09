<?php
namespace jeb\snahp\Apps\RequestForm\Types;

use \Symfony\Component\Form\AbstractType;
use \Symfony\Component\Form\FormBuilderInterface;
use \Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use \Symfony\Component\Validator\Constraints as Assert;

class VideoResolutionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $defaultChoices = [
            'SD/480' => '480',
            '720' => '720',
            '1080' => '1080',
            '4K' => '4K',
        ];
        $builder
            ->add(
                'videoResolution',
                ChoiceType::class,
                [
                    'choices' => $defaultChoices,
                    'data' => '1080',
                    'compound' => false,
                    'attr' => ['tabindex' => 2],
                ]
            );
        ;
    }
}
