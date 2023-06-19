<?php

namespace App\Subscriber;

use App\Utility\CustomSentrySampleRateInterface;
use Sentry\State\HubInterface;
use Sentry\Tracing\Span;
use Sentry\Tracing\SpanContext;
use Sentry\Tracing\TransactionContext;
use Sentry\Tracing\TransactionSource;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Messenger\Event\WorkerMessageFailedEvent;
use Symfony\Component\Messenger\Event\WorkerMessageHandledEvent;
use Symfony\Component\Messenger\Event\WorkerMessageReceivedEvent;

class SentryMessageSubscriber implements EventSubscriberInterface
{
    private ?Span $span;

    public function __construct(
        private readonly HubInterface $hub
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            WorkerMessageReceivedEvent::class => ['handleMessageReceived', -10000],
            WorkerMessageHandledEvent::class => ['handleMessageDone', -10000],
            WorkerMessageFailedEvent::class => ['handleMessageDone', -10000],
        ];
    }

    public function handleMessageDone(): void
    {
        $this->finish();
    }

    public function handleMessageReceived(WorkerMessageReceivedEvent $event): void
    {
        if (!$event->shouldHandle()) {
            return;
        }

        $currentSpan = $this->hub->getSpan();
        $message = $event->getEnvelope()->getMessage();

        if ($currentSpan !== null) {
            $context = new SpanContext();
            $context->setOp('queue.task');
            $context->setDescription($message::class);

            $this->span = $currentSpan->startChild($context);
        } else {
            $context = new TransactionContext();

            $context->setOp('queue.task');
            $context->setName($message::class);
            $context->setSource(TransactionSource::task());

            if ($message instanceof CustomSentrySampleRateInterface) {
                $context->getMetadata()->setSamplingRate($message->getSentrySampleRate());
            }

            $this->span = $this->hub->startTransaction($context);
        }

        $this->hub->setSpan($this->span);
    }

    private function finish(): void
    {
        if ($this->span) {
            $this->span->finish();
            $this->span = null;
            $this->hub->getClient()->flush()->wait(false);
        }
    }
}
