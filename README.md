QueueBundle
===========

# Installing

Install via composer:

    // composer.json

    "repositories": [
        {
            "type": "vcs",
            "url": "http://github.com/whiteoctober/QueueBundle"
        }
    ],

    "require": {
        "whiteoctober/queue-bundle": "version-here"
    },

Add to AppKernel:

    // app/AppKernel.php
    public function registerBundles()
    {
        $bundles = array(
            // Your other bundles here
            new WhiteOctober\QueueBundle\WhiteOctoberQueueBundle(),
        );

        return $bundles;
    }

If you're using Doctrine Migrations Bundle, you can tell Doctrine to generate migrations for the QueueEntry entity in the usual way: `app/console doctrine:migrations:diff`.

# Usage

## Creating a QueueEntry

For creating a `QueueEntry`, use the `QueueService`.  For example:

    /** @var $queueService WhiteOctober\QueueBundle\Service\QueueService  */
    $queueService = $this->get('whiteoctober_queue');

    $queueService->create('entry type', 'entry data');

## Adding a QueueProcessor

Add something like this to your services.yml:

    userCommand.queue.processor:
        class:     MyProject\SomeBundle\QueueProcessor\AnExcitingProcessor
        arguments:
            - [@arguments.like.any.other.service]
        tags:
            - { name: whiteoctober.queue.processor}

The important bit here is the `tags` section.  Note also that your processor class (`AnExcitingProcessor` in the example above) should implement `QueueProcessorInterface`.

## Initiating Queue processing

Run the process queue command:

    app/console whiteoctober:queue:process

Note that, by default, an invocation of the command is limited to processing one item from the queue.  You'll probably want to run the command regularly from a cron job.

`whiteoctober:queue:process` has two options which can be passed in (both optional):

    app/console whiteoctober:queue:process --limit=5 --entry-type=thistype

# Project history

This project is a further development of the [QueueBundle in MyPace](https://github.com/whiteoctober/myPace/tree/develop/src/WhiteOctober/QueueBundle) (which was originally from GBK).
