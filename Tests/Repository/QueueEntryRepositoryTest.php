<?php

namespace WhiteOctober\QueueBundle\Tests\Repository;

use WhiteOctober\QueueBundle\Entity\QueueEntry;
use WhiteOctober\QueueBundle\Repository\QueueEntryRepository;
use WhiteOctober\QueueBundle\Tests\WhiteOctoberCoreTestCase;

class QueueEntryRepositoryTest extends WhiteOctoberCoreTestCase
{
    /** @var $repo QueueEntryRepository */
    protected $repo;

    public function setUp($env = "test")
    {
        parent::setUp($env);

        $this->repo = $this->_entityManager->getRepository('WhiteOctoberQueueBundle:QueueEntry');
    }

    public function testGetOutstandingEntries_emptyDB()
    {
        $entries = $this->repo->getOutstandingEntries();

        $this->assertEmpty($entries);
    }

    public function testGetOutstandingEntries_nothingRelevant()
    {
        // only entities with a status of NOT_STARTED are retrieved
        // so none of these should be

        $otherStatuses = array(
            QueueEntry::IN_PROGRESS,
            QueueEntry::COMPLETED,
            QueueEntry::ERROR,
        );

        foreach ($otherStatuses as $status) {
            $entry = new QueueEntry();
            $entry->setType('abc');
            $entry->setStatus($status);

            $this->_entityManager->persist($entry);
        }
        $this->_entityManager->flush();

        $entries = $this->repo->getOutstandingEntries();

        $this->assertEmpty($entries);
    }

    public function testGetOutstandingEntries_oneRelevant()
    {
        $notStarted = new QueueEntry();
        $notStarted->setType('abc');
        $notStarted->setStatus(QueueEntry::NOT_STARTED);
        $this->_entityManager->persist($notStarted);

        $otherStatuses = array(
            QueueEntry::IN_PROGRESS,
            QueueEntry::COMPLETED,
            QueueEntry::ERROR,
        );

        foreach ($otherStatuses as $status) {
            $entry = new QueueEntry();
            $entry->setType('def');
            $entry->setStatus($status);

            $this->_entityManager->persist($entry);
        }

        $this->_entityManager->flush();

        $entries = $this->repo->getOutstandingEntries();

        $this->assertCount(1, $entries);

        /** @var QueueEntry $resultEntry */
        $resultEntry = $entries[0];
        $this->assertSame('abc', $resultEntry->getType());
    }

    public function testGetOutstandingEntries_allRelevant()
    {
        for ($i = 1; $i <= 5; $i++) {
            $entry = new QueueEntry();
            $entry->setType('aaa');
            $entry->setStatus(QueueEntry::NOT_STARTED);
            $this->_entityManager->persist($entry);
        }
        $this->_entityManager->flush();

        $entries = $this->repo->getOutstandingEntries();

        $this->assertCount(5, $entries);
    }

    public function testGetOutstandingEntries_allRelevantLimit()
    {
        for ($i = 1; $i <= 5; $i++) {
            $entry = new QueueEntry();
            $entry->setType('aaa');
            $entry->setStatus(QueueEntry::NOT_STARTED);
            $this->_entityManager->persist($entry);
        }
        $this->_entityManager->flush();

        $entries = $this->repo->getOutstandingEntries(3);

        $this->assertCount(3, $entries);
    }

    public function testGetOutstandingEntries_considerTypeNothingRelevant()
    {
        for ($i = 1; $i <= 5; $i++) {
            $entry = new QueueEntry();
            $entry->setType('abc');
            $entry->setStatus(QueueEntry::NOT_STARTED);

            $this->_entityManager->persist($entry);
        }
        $this->_entityManager->flush();

        $entries = $this->repo->getOutstandingEntries(0, 'nothing has this type');

        $this->assertEmpty($entries);
    }

    public function testGetOutstandingEntries_considerTypeOneRelevant()
    {
        $abcEntry = new QueueEntry();
        $abcEntry->setType('abc');
        $abcEntry->setStatus(QueueEntry::NOT_STARTED);
        $this->_entityManager->persist($abcEntry);

        $defEntry = new QueueEntry();
        $defEntry->setType('def');
        $defEntry->setStatus(QueueEntry::NOT_STARTED);
        $this->_entityManager->persist($defEntry);

        $this->_entityManager->flush();

        $entries = $this->repo->getOutstandingEntries(0, 'abc');

        $this->assertCount(1, $entries);

        /** @var QueueEntry $resultEntry */
        $resultEntry = $entries[0];
        $this->assertSame('abc', $resultEntry->getType());
    }

    public function testGetOutstandingEntries_considerTypeAllRelevant()
    {
        for ($i = 1; $i <= 5; $i++) {
            $entry = new QueueEntry();
            $entry->setType('aaa');
            $entry->setStatus(QueueEntry::NOT_STARTED);
            $this->_entityManager->persist($entry);
        }
        $this->_entityManager->flush();

        $entries = $this->repo->getOutstandingEntries(0, 'aaa');

        $this->assertCount(5, $entries);
    }

    public function testGetOutstandingEntries_considerTypeAllRelevantLimit()
    {
        for ($i = 1; $i <= 5; $i++) {
            $entry = new QueueEntry();
            $entry->setType('aaa');
            $entry->setStatus(QueueEntry::NOT_STARTED);
            $this->_entityManager->persist($entry);
        }
        $this->_entityManager->flush();

        $entries = $this->repo->getOutstandingEntries(3, 'aaa');

        $this->assertCount(3, $entries);
    }
}
