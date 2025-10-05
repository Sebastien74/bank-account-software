<?php

declare(strict_types=1);

namespace App\Command;

/**
 * DoctrineCommand.
 *
 * To execute doctrine commands
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class DoctrineCommand extends BaseCommand
{
    /**
     * Execute doctrine:schema:update.
     */
    public function update(): string
    {
        return $this->execute([
            'command' => 'doctrine:schema:update',
            '--force' => true,
            '--complete' => true,
        ]);
    }
}
