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

    // Only one task with a given discriminator may be running at any one time.
    public function testGetOutstandingEntries_sameDiscriminator()
    {
        $statuses = array(
            QueueEntry::NOT_STARTED,
            QueueEntry::IN_PROGRESS,
        );

        foreach ($statuses as $status) {
            $entry = new QueueEntry();
            $entry->setType('aaa');
            $entry->setStatus($status);
            $entry->setDiscriminator('dis');
            $this->_entityManager->persist($entry);
        }

        $this->_entityManager->flush();

        $entries = $this->repo->getOutstandingEntries();

        $this->assertEmpty($entries);
    }

    public function testGetOutstandingEntries_discriminatorDiffers()
    {
        $statuses = array(
            'd0' => QueueEntry::NOT_STARTED,
            'd1' => QueueEntry::NOT_STARTED,
            'd2' => QueueEntry::IN_PROGRESS,
            'd3' => QueueEntry::IN_PROGRESS,
        );

        foreach ($statuses as $key => $status) {
            $entry = new QueueEntry();
            $entry->setType('aaa');
            $entry->setStatus($status);
            $entry->setDiscriminator($key);
            $this->_entityManager->persist($entry);
        }

        $this->_entityManager->flush();

        $entries = $this->repo->getOutstandingEntries();

        // everything in progress has a different discriminator to the not started ones
        $this->assertCount(2, $entries);

        /** @var QueueEntry $resultEntry */
        $resultEntry = $entries[0];
        $this->assertSame('d0', $resultEntry->getDiscriminator());

        $resultEntry = $entries[1];
        $this->assertSame('d1', $resultEntry->getDiscriminator());
    }

    public function testGetOutstandingEntries_discriminatorLimitsResults()
    {
        for ($i = 1; $i <= 5; $i++) {
            $entry = new QueueEntry();
            $entry->setType('aaa');
            $entry->setStatus(QueueEntry::NOT_STARTED);
            $entry->setDiscriminator('dis');
            $this->_entityManager->persist($entry);
        }

        // one with a different discriminator
        $differentEntry = new QueueEntry();
        $differentEntry->setType('aaa');
        $differentEntry->setStatus(QueueEntry::NOT_STARTED);
        $differentEntry->setDiscriminator('another dis');
        $this->_entityManager->persist($differentEntry);

        $this->_entityManager->flush();

        $entries = $this->repo->getOutstandingEntries();

        // even though there are multiple things not started, they almost all have the same discriminator
        $this->assertCount(2, $entries);

        /** @var QueueEntry $resultEntry */
        $resultEntry = $entries[0];
        $this->assertSame('dis', $resultEntry->getDiscriminator());

        $resultEntry = $entries[1];
        $this->assertSame('another dis', $resultEntry->getDiscriminator());
    }

    public function testGetOutstandingEntries_discriminatorWithTypeAndLimit()
    {
        for ($i = 1; $i <= 5; $i++) {
            $entry = new QueueEntry();
            $entry->setType('typey');
            $entry->setStatus(QueueEntry::NOT_STARTED);
            $entry->setDiscriminator('dis');
            $this->_entityManager->persist($entry);
        }

        // one with a different discriminator but same type
        $differentEntry1 = new QueueEntry();
        $differentEntry1->setType('typey');
        $differentEntry1->setStatus(QueueEntry::NOT_STARTED);
        $differentEntry1->setDiscriminator('another dis');
        $this->_entityManager->persist($differentEntry1);

        // one with another different discriminator and different type
        $differentEntry2 = new QueueEntry();
        $differentEntry2->setType('typeyzzz');
        $differentEntry2->setStatus(QueueEntry::NOT_STARTED);
        $differentEntry2->setDiscriminator('yet another dis');
        $this->_entityManager->persist($differentEntry2);

        $this->_entityManager->flush();

        // try with a limit
        $entries = $this->repo->getOutstandingEntries(1);
        $this->assertCount(1, $entries);

        // check that the type filter still works too
        $entries = $this->repo->getOutstandingEntries(0, 'typey');
        $this->assertCount(2, $entries);
    }
}
