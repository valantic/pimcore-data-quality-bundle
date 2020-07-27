<?php

namespace Valantic\DataQualityBundle\Command;

use Pimcore\Console\AbstractCommand;
use Pimcore\Model\DataObject\Customer;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @codeCoverageIgnore
 */
class TinkerCommand extends AbstractCommand
{

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this
            ->setName('pimcore:data-quality:tinker')
            ->setDescription('Developer playground for the ValanticDataQualityBundle');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->dump("Hello from " . __CLASS__);

        return 0;
    }
}
