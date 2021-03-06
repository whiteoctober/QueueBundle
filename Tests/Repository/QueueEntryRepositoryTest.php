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

    protected function persistSomeQueueEntriesForTestRemoveBy()
    {
        $statuses = array(
            QueueEntry::NOT_STARTED,
            QueueEntry::IN_PROGRESS,
            QueueEntry::COMPLETED,
            QueueEntry::NOT_STARTED,
            QueueEntry::NOT_STARTED,
        );

        foreach ($statuses as $status) {
            $entry = new QueueEntry();
            $entry->setType('aaa');
            $entry->setStatus($status);
            $entry->setData(serialize('data'));
            $this->_entityManager->persist($entry);
        }
        $this->_entityManager->flush();
    }

    public function testRemoveBy_noMatchType()
    {
        $this->persistSomeQueueEntriesForTestRemoveBy();
        $this->repo->removeBy(array('type' => 'bbb', 'data' => serialize('data')));

        $entries = $this->repo->findAll();
        $this->assertCount(5, $entries);
    }

    public function testRemoveBy_noMatchData()
    {
        $this->persistSomeQueueEntriesForTestRemoveBy();
        $this->repo->removeBy(array('type' => 'aaa', 'data' => serialize('blah')));

        $entries = $this->repo->findAll();
        $this->assertCount(5, $entries);
    }

    public function testRemoveBy_noMatchEither()
    {
        $this->persistSomeQueueEntriesForTestRemoveBy();
        $this->repo->removeBy(array('type' => 'bbb', 'data' => serialize('blah')));

        $entries = $this->repo->findAll();
        $this->assertCount(5, $entries);
    }

    public function testRemoveBy_noMatchStatus()
    {
        $this->persistSomeQueueEntriesForTestRemoveBy();
        $this->repo->removeBy(array('type' => 'aaa', 'data' => serialize('data'), 'status' => QueueEntry::ERROR));

        $entries = $this->repo->findAll();
        $this->assertCount(5, $entries);
    }

    public function testRemoveBy_matchOneType()
    {
        // add entries

        $typeAndData = array(
            'a' => 'data1',
            'b' => 'data2',
            'c' => 'data3',
            'd' => 'data4',
            'e' => 'data5',
        );

        foreach ($typeAndData as $type => $data) {
            $entry = new QueueEntry();
            $entry->setType($type);
            $entry->setStatus(QueueEntry::NOT_STARTED);
            $entry->setData(serialize($data));
            $this->_entityManager->persist($entry);
        }
        $this->_entityManager->flush();

        // call the remove function

        $this->repo->removeBy(array('type' => 'c'));

        // check the results

        $entries = $this->repo->findAll();
        $this->assertCount(4, $entries);

        // now check that the right one was removed

        /** @var QueueEntry $entry */
        foreach ($entries as $entry) {
            $this->assertFalse($entry->getType() == 'c');
        }
    }

    public function testRemoveBy_matchOneData()
    {
        // add entries

        $typeAndData = array(
            'a' => 'data1',
            'b' => 'data2',
            'c' => 'data3',
            'd' => 'data4',
            'e' => 'data5',
        );

        foreach ($typeAndData as $type => $data) {
            $entry = new QueueEntry();
            $entry->setType($type);
            $entry->setStatus(QueueEntry::NOT_STARTED);
            $entry->setData(serialize($data));
            $this->_entityManager->persist($entry);
        }
        $this->_entityManager->flush();

        // call the remove function

        $this->repo->removeBy(array('data' => serialize('data3')));

        // check the results

        $entries = $this->repo->findAll();
        $this->assertCount(4, $entries);

        // now check that the right one was removed

        /** @var QueueEntry $entry */
        foreach ($entries as $entry) {
            $this->assertFalse($entry->getData() == serialize('data3'));
        }
    }

    public function testRemoveBy_matchOneTypeData()
    {
        // add entries

        $typeAndData = array(
            'a' => 'data1',
            'b' => 'data2',
            'c' => 'data3',
            'd' => 'data4',
            'e' => 'data5',
        );

        foreach ($typeAndData as $type => $data) {
            $entry = new QueueEntry();
            $entry->setType($type);
            $entry->setStatus(QueueEntry::NOT_STARTED);
            $entry->setData(serialize($data));
            $this->_entityManager->persist($entry);
        }
        $this->_entityManager->flush();

        // call the remove function

        $this->repo->removeBy(array('type' => 'c', 'data' => serialize('data3')));

        // check the results

        $entries = $this->repo->findAll();
        $this->assertCount(4, $entries);

        // now check that the right one was removed

        /** @var QueueEntry $entry */
        foreach ($entries as $entry) {
            $this->assertFalse($entry->getType() == 'c' && $entry->getData() == serialize('data3'));
        }
    }

    public function testRemoveBy_matchOneTypeDataStatus()
    {
        // add entries

        $typeAndData = array(
            'a' => 'data1',
            'b' => 'data2',
            'c' => 'data3',
            'd' => 'data4',
            'e' => 'data5',
        );

        $statuses = array(
            QueueEntry::NOT_STARTED,
            QueueEntry::IN_PROGRESS,
            QueueEntry::NOT_STARTED,
            QueueEntry::NOT_STARTED,
            QueueEntry::COMPLETED,
        );

        $i = 0;
        foreach ($typeAndData as $type => $data) {
            $entry = new QueueEntry();
            $entry->setType($type);
            $entry->setStatus($statuses[$i]);
            $entry->setData(serialize($data));
            $this->_entityManager->persist($entry);
            $i++;
        }
        $this->_entityManager->flush();

        // call the remove function

        $this->repo->removeBy(array('type' => 'c', 'data' => serialize('data3'), 'status' => QueueEntry::NOT_STARTED));

        // check the results

        $entries = $this->repo->findAll();
        $this->assertCount(4, $entries);

        // now check that the right one was removed

        /** @var QueueEntry $entry */
        foreach ($entries as $entry) {
            $this->assertFalse($entry->getType() == 'c' && $entry->getData() == serialize('data3'));
        }
    }

    public function testRemoveBy_matchAll()
    {
        $this->persistSomeQueueEntriesForTestRemoveBy();
        $this->repo->removeBy(array('type' => 'aaa', 'data' => serialize('data')));

        $entries = $this->repo->findAll();
        $this->assertEmpty($entries);
    }

    public function testRemoveBy_matchAllConsiderStatus()
    {
        for ($i = 1; $i <= 5; $i++) {
            $entry = new QueueEntry();
            $entry->setType('aaa');
            $entry->setStatus(QueueEntry::NOT_STARTED);
            $entry->setData(serialize('bbb'));
            $this->_entityManager->persist($entry);
        }
        $this->_entityManager->flush();

        $this->repo->removeBy(array('type' => 'aaa', 'data' => serialize('bbb'), 'status' => QueueEntry::NOT_STARTED));

        $entries = $this->repo->findAll();
        $this->assertEmpty($entries);
    }
}
