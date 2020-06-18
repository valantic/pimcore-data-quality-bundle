<?php

namespace Valantic\DataQualityBundle\Command;

use Pimcore\Console\AbstractCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class TinkerCommand extends AbstractCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('pimcore:data-quality:tinker')
            ->setDescription('Developer playground for the ValanticDataQualityBundle');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->dump("Hello from ".__CLASS__);
    }
}
