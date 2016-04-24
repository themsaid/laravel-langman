<?php

namespace Themsaid\Langman;

use App;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Console\AppNamespaceDetectorTrait;
use ReflectionClass;

trait DispatcherTrait
{
    use AppNamespaceDetectorTrait;

    /**
     * Laravel container class.
     *
     * @var \Illuminate\Contracts\Container\Container
     */
    protected $app;

    /**
     * Command events dispatcher.
     */
    public function eventDispatch()
    {
        $this->app = App::make(Container::class);

        $eventClass = $this->getEvent();

        // Firing find event
        if ($this->app['events']->hasListeners($eventClass)) {
            $refClass = new ReflectionClass($eventClass);
            $this->app['events']->fire($refClass->newInstanceArgs(func_get_args()));

            if (config('langman.show_events')) {
                $this->line("\n".'> '.class_basename($eventClass).' events trigged...'."\n");
            }
        }
    }

    /**
     * Get event full path.
     *
     * @return string
     */
    protected function getEvent()
    {
        return $this->getAppNamespace().'Events\\'.$this->getEventName().'Completed';
    }

    /**
     * Get event class base name.
     *
     * @return string
     */
    protected function getEventName()
    {
        return class_basename($this);
    }
}
