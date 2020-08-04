<?php
namespace jeb\snahp\Apps\ClassLoader;

require 'ext/jeb/snahp/core/Rest/RedBeanSetup.php';

use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

use jeb\snahp\core\Rest\RedBeanSetup;
use \R as R;

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
    }
}
