<?php

namespace WhiteOctober\QueueBundle\Command;

use Swift_Message;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Doctrine\ORM\EntityManager;
use WhiteOctober\QueueBundle\Entity\QueueEntry;

class LongRunningEntriesCommand extends ContainerAwareCommand
{
    /**
     * @var InputInterface
     */
    protected $input;

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
     * @param InputInterface  $input
     * @param OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;
        $this->output = $output;

        $longRunningEntries = $this->getLongRunningEntries();

        if (empty($longRunningEntries)) {
            $output->writeln("<info>Everything okay: No long-running processes found.</info>");

            return;
        }

        $output->writeln(sprintf(
            '<comment>%d long-running process(es) found.  E-mailing the details to %s.</comment>',
            count($longRunningEntries),
            $input->getArgument('email-to')
        ));
        $this->emailAboutLongRunningEntries($longRunningEntries);
    }

    protected function getLongRunningEntries()
    {
        /** @var EntityManager $em */
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $query = $em->createQuery(sprintf(
            'SELECT entry FROM WhiteOctober\QueueBundle\Entity\QueueEntry entry WHERE entry.startedAt < :long_ago AND entry.status = %d',
            QueueEntry::IN_PROGRESS
        ));
        $query->setParameter('long_ago', new \DateTime('-2 hours'));

        return $query->execute();
    }

    private function emailAboutLongRunningEntries($longRunningEntries)
    {
        $container = $this->getContainer();

        $message = Swift_Message::newInstance()
            ->setSubject('Long-running queue entries found.')
            ->setTo($this->input->getArgument('email-to'))
            ->setFrom($this->input->getArgument('email-from'))
        ;

        $body = sprintf(
            "One or more queue entries in the '%s' queue have been running for longer than 2 hours.  See below for the details:\n\n",
            $this->input->getArgument('application-name')
        );

        /** @var $entry QueueEntry */
        foreach ($longRunningEntries as $entry) {
            $body .= sprintf(
                "- Entry ID %s with type '%s' and data '%s' started on %s'\n",
                $entry->getId(),
                $entry->getType(),
                $entry->getData(),
                $entry->getStartedAt()->format('Y-m-d H:i:s')
            );
        }

        $body .= sprintf(
            "\nThe documentation for '%s' should have more details about what to do in this situation.  If not, you may like to consider killing the long-running process and examining the database to work out what it was trying to do.",
            $this->input->getArgument('application-name')
        );

        $message->setBody($body);

        $container->get("mailer")->send($message);
    }
}
