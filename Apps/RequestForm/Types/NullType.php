<?php
namespace jeb\snahp\Apps\RequestForm\Types;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use jeb\snahp\Apps\RequestForm\Models\NullRequest;

class NullType extends AbstractType
{
    public static $alias = "null";
    const CLASSNAME = NullRequest::class;

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
    }
}
