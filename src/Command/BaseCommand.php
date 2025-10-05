<?php

declare(strict_types=1);

namespace App\Command;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * BaseCommand.
 *
 * Base commands
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class BaseCommand
{
    /**
     * BaseCommand constructor.
     */
    public function __construct(protected readonly KernelInterface $kernel)
    {
    }

    protected function execute(array $params): string
    {
        try {
            $application = new Application($this->kernel);
            $application->setAutoExit(false);
            $input = new ArrayInput($params);
            $output = new BufferedOutput();
            $application->run($input, $output);
            return $output->fetch();
        } catch (\Exception $exception) {
            return $exception->getMessage().' - '.$exception->getTraceAsString();
        }
    }
}
