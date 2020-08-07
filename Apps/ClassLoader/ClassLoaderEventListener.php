<?php
namespace jeb\snahp\Apps\ClassLoader;

try {
    include_once 'ext/jeb/snahp/core/Rest/RedBeanSetup.php';
} catch (Exception $e) {
}

use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

use jeb\snahp\core\Rest\RedBeanSetup;

class ClassLoaderEventListener implements EventSubscriberInterface
{
    use RedBeanSetup;

    public function __construct(/*{{{*/
    ) {
        $this->connectDatabase(false);
    }/*}}}*/

    public static function getSubscribedEvents()/*{{{*/
    {
        return [
            'core.common' => [
                ['loadClasses', 100],
            ],
        ];
    }/*}}}*/

    public function loadClasses($event)
    {
        include_once 'ext/jeb/snahp/Apps/ClassLoader/functions.php';
    }
}
