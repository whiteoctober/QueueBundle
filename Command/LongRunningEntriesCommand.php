<?php

namespace WhiteOctober\QueueBundle\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Doctrine\ORM\EntityManager;
use WhiteOctober\QueueBundle\Entity\QueueEntry;

class LongRunningEntriesCommand extends WhiteOctoberCommandBase
{
    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * @var OutputInterface
     */
    protected $output;

    /**
     * Command configuration
     */
    protected function configure()
    {
        $this
            ->setName("whiteoctober:queue:check-for-long-running")
            ->setDescription("Check for long running entries, email if any found")
            ->addArgument("email-to", InputArgument::REQUIRED, "Who to email if long-running entries found.")
            ->addArgument("email-from", InputArgument::REQUIRED, "The from address to use for the email.")
            ->addArgument("application-name", InputArgument::REQUIRED, "The name of the application in which you're using this queue.")
        ;
    }

    /**
     * Main task execution method
     *
     * @param InputInterface   $input
     * @param OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
    }
}
