<?php
namespace jeb\snahp\Apps\ClassLoader;

try {
    include_once '/var/www/forum/ext/jeb/snahp/core/Rest/RedBeanSetup.php';
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
    }/*}}}*/

    public static function getSubscribedEvents()/*{{{*/
    {
        return [
            'core.common' => [
                ['loadClasses', 1],
            ],
        ];
    }/*}}}*/

    public function loadClasses($event)
    {
        $this->connectDatabase(true);
        include_once '/var/www/forum/ext/jeb/snahp/Apps/ClassLoader/functions.php';
        include_once '/var/www/forum/ext/jeb/snahp/core/errors.php';
    }
}
