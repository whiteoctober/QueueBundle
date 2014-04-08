<?php

namespace WhiteOctober\QueueBundle\Command;

use Symfony\Component\Console\Input\InputInterface,
    Symfony\Component\Console\Input\InputOption,
    Symfony\Component\Console\Output\OutputInterface;

use Doctrine\ORM\EntityManager;

use WhiteOctober\QueueBundle\Entity\QueueEntry;

/**
 * Queue processor
 */
class ProcessQueueCommand extends WhiteOctoberCommandBase
{
    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * @var array
     */
    protected $errors = array();

    /**
     * @var OutputInterface
     */
    protected $output;

    /**
     * Command configuration
     */
    protected function configure()
    {
        $this->
            setName("whiteoctober:queue:process")->
            setDescription("Processes the queue")->
            addOption("limit", null, InputOption::VALUE_REQUIRED, "Limit the number of queue items to process", null)->
            addOption("entry-type", null, InputOption::VALUE_REQUIRED, "A specific queue entry type to process", null)
        ;
    }

    /**
     * Main task execution method
     *
     * @param \Symfony\Component\Console\Input\InputInterface   $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->em = $this->getContainer()->get("doctrine.orm.entity_manager");
        $this->output = $output;

        // Get queue entries that need processing, in order of creation date
        $limit = abs($input->getOption("limit"));
        $entryType = $input->getOption("entry-type");
        $entries = $this->em->getRepository("WhiteOctoberQueueBundle:QueueEntry")->getOutstandingEntries($limit, $entryType);
        $output->writeln("<info>" . count($entries) . " entries to process</info>");
        foreach ($entries as $entry) {
            $output->writeln("<comment>" . $entry->getId() . ": " . $entry->getType() . "</comment>");
            $this->processEntry($entry);
        }
        $output->writeln("<info>Done</info>");
    }

    /**
     * Processes a queue entry
     *
     * @param QueueEntry $entry
     */
    protected function processEntry(QueueEntry $entry)
    {
        $doNotFinish = false;
        $entry->start();
        $this->em->persist($entry);
        $this->em->flush();
        $collector = $this->getContainer()->get("whiteoctober.queue.collector");
        foreach ($collector->getProcessors() as $processor) {
            if ($processor->getType() != $entry->getType()) {
                continue;
            }

            try {
                $processor->setData(unserialize($entry->getData()));
                $processor->process($this->output);
            } catch (\Exception $e) {
                $doNotFinish = true;
                // Log it as not-completed
                $this->addCommandError(array(
                    "id"    => $entry->getId(),
                    "error" => $e->getMessage(),
                    "line"  => $e->getLine(),
                    "file"  => $e->getFile(),
                ));
            }
        }
        if ($doNotFinish) {
            $entry->hasErrored();
        } else {
            $entry->finish();
        }
        $this->em->persist($entry);
        $this->em->flush();
    }
}
