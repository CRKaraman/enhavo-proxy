<?php
/**
 * Created by PhpStorm.
 * User: m
 * Date: 17.03.17
 * Time: 12:26
 */

namespace ProjectBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class VarnishCreateSecretCommand extends ContainerAwareCommand
{
    use ContainerAwareTrait;

    protected function configure()
    {
        $this
            ->setName('varnish:create:secret')
            ->setDescription('create varnish secret file');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->getContainer()->get('project.varnish.manger')->createSecretFile();
        $output->writeln('varnish secret saved');
    }
}