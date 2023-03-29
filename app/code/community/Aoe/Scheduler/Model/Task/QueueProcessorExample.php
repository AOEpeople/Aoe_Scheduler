<?php

/**
 * QueueProcessorExample Task
 *
 * @author Fabrizio Branca
 * @since 2016-03-08
 */
class Aoe_Scheduler_Model_Task_QueueProcessorExample
{

    /**
     * Run
     *
     * @return string
     * @throws Exception
     */
    public function run(Aoe_Scheduler_Model_Schedule $schedule)
    {
        $sqsClient = null;
        // in case you're interested how often this was repeated so far...
        $currentRepetition = $schedule->getRepetition();

        $numberOfMessagesToFetch = 10;

        // [...]
        $result = $sqsClient->receiveMessage([
            'AttributeNames' => ['All'],
            'MaxNumberOfMessages' => $numberOfMessagesToFetch,
            'QueueUrl' => $queueUrl,
        ]);
        $messages = $result->search('Messages[]');

        if ((is_countable($messages) ? count($messages) : 0) == 0) {
            $schedule->setStatus(Aoe_Scheduler_Model_Schedule::STATUS_DIDNTDOANYTHING);
            return;
        }

        // process messages
        foreach ($messages as $message) {
            // do something with the message
            // [...]
            $sqsClient->deleteMessage([
                'QueueUrl' => $queueUrl,
                'ReceiptHandle' => $message['ReceiptHandle']
            ]);
        }

        if ((is_countable($messages) ? count($messages) : 0) == $numberOfMessagesToFetch) {
            // there might be more messages on the queue
            $schedule->setStatus(Aoe_Scheduler_Model_Schedule::STATUS_REPEAT);
        } else {
            $schedule->setStatus(Aoe_Scheduler_Model_Schedule::STATUS_SUCCESS);
        }
    }
}
