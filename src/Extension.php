<?php

/**
 * PhpSpec Formatters
 *
 * @copyright Copyright (c) 2017-2019 DIGITAL WOLVES LTD (https://digitalwolves.ltd) All rights reserved.
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 */

namespace Aist\PhpSpec\Formatters;

use Aist\PhpSpec\Formatters\Formatter\MdFormatter;
use PhpSpec\Extension as PhpSpecExtension;
use PhpSpec\ServiceContainer;

/**
 * Extension
 */
class Extension implements PhpSpecExtension
{
    /**
     * {@inheritdoc}
     */
    public function load(ServiceContainer $container, array $params)
    {
        $this->addFormatter($container, 'md', MdFormatter::class);
    }

    /**
     * Add a formatter to the service container
     *
     * @param ServiceContainer $container
     * @param string           $name
     * @param string           $class
     */
    protected function addFormatter(ServiceContainer $container, $name, $class)
    {
        $container->define('formatter.formatters.aist.' . $name, function ($c) use ($class) {
            /** @var ServiceContainer $c */
            return new $class(
                $c->get('formatter.presenter'),
                $c->get('console.io'),
                $c->get('event_dispatcher.listeners.stats')
            );
        });
    }
}
