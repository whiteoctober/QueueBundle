QueueBundle
===========

**NB** This bundle should be considered at "early-release" stage.  Much of the code has been used in production environments, but the bundle in its current form has not been subject to such rigorous real-world usage.

# Installing

## Install via composer

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

## Add to AppKernel

    // app/AppKernel.php
    public function registerBundles()
    {
        $bundles = array(
            // Your other bundles here
            new WhiteOctober\QueueBundle\WhiteOctoberQueueBundle(),
        );

        return $bundles;
    }

## Database migration

If you're using Doctrine Migrations Bundle, you can tell Doctrine to generate migrations for the QueueEntry entity in the usual way: `app/console doctrine:migrations:diff`.

## (Optional) Add parameters for error emails:

If you want to be emailed when queue entries fail, add the following to your `parameters.yml`:

    task_errors_to: errors-sent-here-@example.com
    emailsFromEmail: error-emails-come-from@example.com

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

    app/console whiteoctober:queue:check-for-long-running errors@whiteoctober.co.uk info+myapp@whiteoctober.co.uk 'my proj'

This will send an e-mail to *errors@whiteoctober.co.uk* from *info+myapp@whiteoctober.co.uk* about any queue entries which have been in progress for more than 2 hours.  In the e-mail, the queue will be identified as being used by the application *my proj*.
