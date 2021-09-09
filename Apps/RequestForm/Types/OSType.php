<?php
namespace jeb\snahp\Apps\RequestForm\Types;

use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Validator\Constraints as Assert;

class OSType extends ChoiceType
{
    public function configureOptions(OptionsResolver $resolver)
    {
        $defaultChoices = [
            "PC" => "pc",
            "Mac" => "mac",
            "Android" => "android",
            "iOS" => "ios",
            "Linux" => "linux",
            "Other" => "other",
        ];
        $resolver->setDefaults([
            "label" => "Operating System",
            "help" => "If other, please specify the OS in the comment section",
            "constraints" => [
                new Assert\Choice(["choices" => $defaultChoices]),
            ],
            "choices" => $defaultChoices,
            "required" => true,
            "compound" => false,
        ]);
    }
}
