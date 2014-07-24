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

## Checking for long-running jobs

To check for long-running queue jobs, use the command `whiteoctober:queue:check-for-long-running`.  For example:

    app/console whiteoctober:queue:check-for-long-running --email-to=errors@whiteoctober.co.uk --email-from=info+myapp@whiteoctober.co.uk --application-name=myproj

This will send an e-mail to *errors@whiteoctober.co.uk* from *info+myapp@whiteoctober.co.uk* about any queue entries which have been in progress for more than 2 hours.  In the e-mail, the queue will be identified as being used by the application *myproj*.

# Project history

This project is a further development of the [QueueBundle in MyPace](https://github.com/whiteoctober/myPace/tree/develop/src/WhiteOctober/QueueBundle) (which was originally from GBK).
