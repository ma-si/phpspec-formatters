<?php

/**
 * PhpSpec Formatters
 *
 * @copyright Copyright (c) 2017-2019 DIGITAL WOLVES LTD (https://digitalwolves.ltd) All rights reserved.
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 */

namespace Aist\PhpSpec\Formatters\Formatter;

use PhpSpec\Event\SuiteEvent;
use PhpSpec\Event\SpecificationEvent;
use PhpSpec\Event\ExampleEvent;
use PhpSpec\Formatter\ConsoleFormatter;

final class MdFormatter extends ConsoleFormatter
{
    private $icons = [
        ExampleEvent::PASSED => '💚',
        ExampleEvent::PENDING => '🔶',
        ExampleEvent::SKIPPED => '🔵',
        ExampleEvent::FAILED => '🔴',
        ExampleEvent::BROKEN => '🛑',
    ];

    /** @inheritdoc */
    public function beforeSuite(SuiteEvent $event)
    {
        $this->getIO()->writeln($this->formatHeader(1, 'Test suite: PhpSpec'), 0);
    }

    /** @inheritdoc */
    public function beforeSpecification(SpecificationEvent $event)
    {
        $this->getIO()->writeln();
        $this->getIO()->writeln($this->formatHeader(2, $event->getSpecification()->getTitle()), 0);
    }

    /** @inheritdoc */
    public function afterExample(ExampleEvent $event)
    {
        $io = $this->getIO();
        $line  = $event->getExample()->getFunctionReflection()->getStartLine();
        $depth = 2;
        $title = ucfirst(preg_replace('/^it /', '', $event->getExample()->getTitle()));
        $icon = $this->icons[$event->getResult()];

        $io->write(sprintf('%s %s %s', $this->formatCheckbox(true), $title, $icon), $depth);

        $this->printSlowTime($event);
        $io->writeln();
        $this->printException($event);
    }

    /** @inheritdoc */
    public function afterSuite(SuiteEvent $event)
    {
        $io = $this->getIO();
        $io->writeln();

        foreach ([
                     'failed' => $this->getStatisticsCollector()->getFailedEvents(),
                     'broken' => $this->getStatisticsCollector()->getBrokenEvents(),
                     'skipped' => $this->getStatisticsCollector()->getSkippedEvents(),
                 ] as $status => $events) {
            if (! count($events)) {
                continue;
            }

            $io->writeln(sprintf("<%s>----  %s examples</%s>\n", $status, $status, $status));
            foreach ($events as $failEvent) {
                $io->writeln(sprintf(
                    '%s',
                    str_replace('\\', DIRECTORY_SEPARATOR, $failEvent->getSpecification()->getTitle())
                ), 8);
                $this->afterExample($failEvent);
                $io->writeln();
            }
        }

        $io->writeln();
        $io->writeln(sprintf("%d specs  ", $this->getStatisticsCollector()->getTotalSpecs()));

        $counts = [];
        foreach ($this->getStatisticsCollector()->getCountsHash() as $type => $count) {
            if ($count) {
                $counts[] = sprintf('<%s>%d %s</%s>', $type, $count, $type, $type);
            }
        }

        $io->write(sprintf("%d examples ", $this->getStatisticsCollector()->getEventsCount()));
        if (\count($counts)) {
            $io->write(sprintf("(%s)  ", implode(', ', $counts)));
        }

        $io->writeln();
        $io->writeln(sprintf("%sms", round($event->getTime() * 1000)));
    }

    /**
     * @param ExampleEvent $event
     */
    protected function printSlowTime(ExampleEvent $event) : void
    {
        $io = $this->getIO();
        $ms = $event->getTime() * 1000;
        if ($ms > 100) {
            $io->write(sprintf(' (%s %sms)', $this->icons[ExampleEvent::FAILED], round($ms)));
        } elseif ($ms > 50) {
            $io->write(sprintf(' (%s %sms)', $this->icons[ExampleEvent::PENDING], round($ms)));
        }
    }

    /** @inheritdoc */
    protected function printException(ExampleEvent $event, $depth = null) : void
    {
        $io = $this->getIO();

        if (null === $exception = $event->getException()) {
            return;
        }

        $depth = $depth ?: 8;
        $message = $this->getPresenter()->presentException($exception, $io->isVerbose());

        $io->writeln(sprintf('%s %s', $this->icons[$event->getResult()], lcfirst($message)), $depth);
    }

    /**
     * @param bool $checked
     * @return string
     */
    private function formatCheckbox(bool $checked = false) : string
    {
        return sprintf('- [%s]', $checked ? 'x' : ' ');
    }

    /**
     * @param int $header Values 1-6
     * @param string $text
     * @return string
     */
    private function formatHeader(int $header, string $text) : string
    {
        return sprintf('%s %s', str_repeat('#', $header), $text);
    }

    /**
     * @param string $text
     * @return string
     */
    private function formatList(string $text) : string
    {
        return sprintf('- %s', $text);
    }
}
