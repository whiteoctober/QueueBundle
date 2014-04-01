<?php

namespace WhiteOctober\QueueBundle\Command;

use Symfony\Component\Console\Input\InputArgument,
    Symfony\Component\Console\Input\InputInterface,
    Symfony\Component\Console\Output\OutputInterface;
use WhiteOctober\QueueBundle\Entity\QueueEntry;

class ExpireQueueEntriesCommand extends WhiteOctoberCommandBase
{
    /**
     * @var \Doctrine\ORM\EntityManager
     */
    protected $em;

    /**
     * @var \WhiteOctober\AuditLoggerBundle\Service\AuditLoggerService
     */
    protected $auditLogger;

    /**
     * Configures the console commnad
     *
     * @return void
     */
    protected function configure()
    {
        $this
            ->setName("whiteoctober:expire-queue-entries")
            ->setDescription("Expires queue entries that are more than N weeks old")
            ->addArgument("weeks", InputArgument::OPTIONAL, "Number of weeks of messages to keep", 2)
        ;
    }

    /**
     * Main command execution.
     *
     *
     * @param  \Symfony\Component\Console\Input\InputInterface   $input
     * @param  \Symfony\Component\Console\Output\OutputInterface $output
     * @return void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $weeks = $input->getArgument("weeks");
        $this->em = $this->getContainer()->get("doctrine.orm.entity_manager");
        $this->auditLogger = $this->getContainer()->get("wo_auditlogger");

        $output->writeln("expiring queue entries...");
        $this->expireQueueEntriesByWeeks($weeks);
        $output->writeln("done");
        $this->auditLogger->log("queue.expired", "Cleared queue entries older than $weeks weeks");
    }

    /**
     * Clears old queue entries from $weeksCount weeks ago or older
     *
     * @param integer $weeksCount number of weeks of queue to keep
     */
    protected function expireQueueEntriesByWeeks($weeksCount)
    {
        $today = date("Y-m-d 00:00:00", strtotime("-{$weeksCount} weeks"));
        $dql  = "delete from WhiteOctoberQueueBundle:QueueEntry q where ";
        $dql .= "q.createdAt < :maxtime ";
        $dql .= "AND q.status = :status";
        $this->em->
            createQuery($dql)->
            setParameter("maxtime", $today)->
            setParameter("status", QueueEntry::COMPLETED)->
            execute()
        ;
    }
}
