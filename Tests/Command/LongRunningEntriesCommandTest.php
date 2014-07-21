<?php

namespace WhiteOctober\QueueBundle\Tests\Command;

use Swift_Mime_Message;
use Symfony\Component\Console\Tester\CommandTester;
use WhiteOctober\QueueBundle\Command\LongRunningEntriesCommand;
use WhiteOctober\QueueBundle\Entity\QueueEntry;
use WhiteOctober\QueueBundle\Tests\WhiteOctoberCoreTestCase;
use Symfony\Bundle\FrameworkBundle\Console\Application;

class LongRunningEntriesCommandTest extends WhiteOctoberCoreTestCase
{
    protected $emailTo = 'mail@example.com';
    protected $emailFrom = 'from@example.com';

    public function testExecute_noData()
    {
        $commandTester = $this->runTheCommand();

        $this->assertContains('No long-running processes found', $commandTester->getDisplay());
        $this->assertEmpty($this->getEmailsFromLogger());
    }

    public function testExecute_nothingLongRunning()
    {
        $entry = new QueueEntry();
        $entry->setStatus(QueueEntry::IN_PROGRESS);
        $entry->setStartedAt(new \DateTime('-1 minute'));

        $this->_entityManager->persist($entry);
        $this->_entityManager->flush();

        $commandTester = $this->runTheCommand();

        $this->assertContains('No long-running processes found', $commandTester->getDisplay());
        $this->assertEmpty($this->getEmailsFromLogger());
    }

    public function testExecute_longAgoButCompleted()
    {
        $entry = new QueueEntry();
        $entry->setStatus(QueueEntry::COMPLETED);
        $entry->setStartedAt(new \DateTime('-1 month'));

        $this->_entityManager->persist($entry);
        $this->_entityManager->flush();

        $commandTester = $this->runTheCommand();

        $this->assertContains('No long-running processes found', $commandTester->getDisplay());
        $this->assertEmpty($this->getEmailsFromLogger());
    }

    public function testExecute_oneLongRunning()
    {
        $longRunningEntry = new QueueEntry();
        $longRunningEntry->setType('typey');
        $longRunningEntry->setData('troublesome data');
        $longRunningEntry->setStatus(QueueEntry::IN_PROGRESS);
        $longRunningEntry->setStartedAt(new \DateTime('-1 month'));
        $this->_entityManager->persist($longRunningEntry);

        $okayEntry = new QueueEntry();
        $okayEntry->setType('typetastic');
        $okayEntry->setData('lovely data');
        $okayEntry->setStatus(QueueEntry::IN_PROGRESS);
        $okayEntry->setStartedAt(new \DateTime('-1 minute'));
        $this->_entityManager->persist($okayEntry);

        $this->_entityManager->flush();

        $commandTester = $this->runTheCommand();

        $this->assertContains('1 long-running process(es) found', $commandTester->getDisplay());

        $messages = $this->getEmailsFromLogger();
        $this->assertCount(1, $messages);

        /** @var Swift_Mime_Message $message */
        $message = $messages[0];
        $this->assertSwiftMailerAddressListMatches($this->emailTo, $message->getTo());
        $this->assertSwiftMailerAddressListMatches($this->emailFrom, $message->getFrom());

        $this->assertContains('ID ' . $longRunningEntry->getId(), $message->getBody());
        $this->assertContains($longRunningEntry->getType(), $message->getBody());
        $this->assertContains($longRunningEntry->getData(), $message->getBody());

        $this->assertNotContains('ID ' . $okayEntry->getId(), $message->getBody());
        $this->assertNotContains($okayEntry->getType(), $message->getBody());
        $this->assertNotContains($okayEntry->getData(), $message->getBody());

        $this->assertContains('Super Project', $message->getBody());
    }

    public function testExecute_multipleLongRunning()
    {
        $longRunningEntry = new QueueEntry();
        $longRunningEntry->setType('typey');
        $longRunningEntry->setData('troublesome data');
        $longRunningEntry->setStatus(QueueEntry::IN_PROGRESS);
        $longRunningEntry->setStartedAt(new \DateTime('-1 month'));
        $this->_entityManager->persist($longRunningEntry);

        $longRunningEntry2 = new QueueEntry();
        $longRunningEntry2->setType('typez');
        $longRunningEntry2->setData('bad data');
        $longRunningEntry2->setStatus(QueueEntry::IN_PROGRESS);
        $longRunningEntry2->setStartedAt(new \DateTime('-1 week'));
        $this->_entityManager->persist($longRunningEntry2);

        $okayEntry = new QueueEntry();
        $okayEntry->setType('typetastic');
        $okayEntry->setData('lovely data');
        $okayEntry->setStatus(QueueEntry::IN_PROGRESS);
        $okayEntry->setStartedAt(new \DateTime('-1 minute'));
        $this->_entityManager->persist($okayEntry);

        $this->_entityManager->flush();

        $commandTester = $this->runTheCommand();

        $this->assertContains('2 long-running process(es) found', $commandTester->getDisplay());

        $messages = $this->getEmailsFromLogger();
        $this->assertCount(1, $messages);

        /** @var Swift_Mime_Message $message */
        $message = $messages[0];
        $this->assertSwiftMailerAddressListMatches($this->emailTo, $message->getTo());
        $this->assertSwiftMailerAddressListMatches($this->emailFrom, $message->getFrom());

        $this->assertContains('ID ' . $longRunningEntry->getId(), $message->getBody());
        $this->assertContains($longRunningEntry->getType(), $message->getBody());
        $this->assertContains($longRunningEntry->getData(), $message->getBody());

        $this->assertContains('ID ' . $longRunningEntry2->getId(), $message->getBody());
        $this->assertContains($longRunningEntry2->getType(), $message->getBody());
        $this->assertContains($longRunningEntry2->getData(), $message->getBody());

        $this->assertNotContains('ID ' . $okayEntry->getId(), $message->getBody());
        $this->assertNotContains($okayEntry->getType(), $message->getBody());
        $this->assertNotContains($okayEntry->getData(), $message->getBody());

        $this->assertContains('Super Project', $message->getBody());
    }

    protected function runTheCommand()
    {
        // http://www.ardianys.com/2013/04/symfony2-test-console-command-which-use.html
        $application = new Application($this->_kernel);
        $application->add(new LongRunningEntriesCommand());

        $command = $application->find('whiteoctober:queue:check-for-long-running');
        $commandTester = new CommandTester($command);

        $commandTester->execute(array(
            'command' => $command->getName(),
            'email-to' => $this->emailTo,
            'email-from' => $this->emailFrom,
            'application-name' => 'Super Project'
        ));

        return $commandTester;
    }

    /**
     * @param string $address
     * @param array  $swiftMailerAddressList The output from $message->getTo(), getFrom() etc.
     */
    protected function assertSwiftMailerAddressListMatches($address, $swiftMailerAddressList)
    {
        $this->assertCount(1, $swiftMailerAddressList);

        // It'll be of the form array('address' => 'name'), hence calling array_keys
        $actualAddress = array_keys($swiftMailerAddressList)[0];
        $this->assertSame($address, $actualAddress);
    }
}
