<?php

namespace Valantic\DataQualityBundle\Command;

use Pimcore\Console\AbstractCommand;
use Pimcore\Model\DataObject\Customer;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Yaml\Yaml;

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
        $this->dump("Hello from " . __CLASS__);
        $this->validateCustomer(Customer::getById(2), $output);
    }

    protected function validateCustomer(Customer $obj, $output)
    {
        $config = Yaml::parseFile('/var/www/html/var/config/valantic_dataquality_config.yml')['Customer'];
        $builder = Validation::createValidatorBuilder();
        $validator = $builder->getValidator();
        foreach ($config as $field => $rules) {
            $output->write($field . ': ');
            $constraints = [];
            foreach ($rules as $constraintName => $args) {
                $constraintClassName = 'Symfony\Component\Validator\Constraints\\' . $constraintName;
                $constraints[] = new $constraintClassName(...([$args ?? null]));
            }
            $violations = $validator->validate($obj->get($field), $constraints);

            $output->writeLn(count($violations) ? 'FAILED' : 'PASSED');
            foreach ($violations as $violation){
                dump($violation->getMessage());
            }
        }
    }
}
