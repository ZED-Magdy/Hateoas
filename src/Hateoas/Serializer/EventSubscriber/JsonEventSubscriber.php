<?php

declare(strict_types=1);

namespace Hateoas\Serializer\EventSubscriber;

use Hateoas\Factory\EmbeddedsFactory;
use Hateoas\Factory\LinksFactory;
use Hateoas\Serializer\SerializerInterface;
use Hateoas\Serializer\Metadata\InlineDeferrer;
use JMS\Serializer\EventDispatcher\Events;
use JMS\Serializer\EventDispatcher\EventSubscriberInterface;
use JMS\Serializer\EventDispatcher\ObjectEvent;

class JsonEventSubscriber implements EventSubscriberInterface
{
    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            [
                'event'  => Events::POST_SERIALIZE,
                'format' => 'json',
                'method' => 'onPostSerialize',
            ],
        ];
    }

    /**
     * @var SerializerInterface
     */
    private $jsonSerializer;

    /**
     * @var LinksFactory
     */
    private $linksFactory;

    /**
     * @var EmbeddedsFactory
     */
    private $embeddedsFactory;

    /**
     * @var InlineDeferrer
     */
    private $embeddedsInlineDeferrer;

    /**
     * @var InlineDeferrer
     */
    private $linksInlineDeferrer;

    public function __construct(
        SerializerInterface $jsonSerializer,
        LinksFactory $linksFactory,
        EmbeddedsFactory $embeddedsFactory,
        InlineDeferrer $embeddedsInlineDeferrer,
        InlineDeferrer $linksInleDeferrer
    ) {
        $this->jsonSerializer          = $jsonSerializer;
        $this->linksFactory            = $linksFactory;
        $this->embeddedsFactory        = $embeddedsFactory;
        $this->embeddedsInlineDeferrer = $embeddedsInlineDeferrer;
        $this->linksInlineDeferrer     = $linksInleDeferrer;
    }

    public function onPostSerialize(ObjectEvent $event): void
    {
        $object  = $event->getObject();
        $context = $event->getContext();

        $context->startVisiting($object);

        $embeddeds = $this->embeddedsFactory->create($object, $context);
        $links     = $this->linksFactory->create($object, $context);

        $embeddeds = $this->embeddedsInlineDeferrer->handleItems($object, $embeddeds, $context);
        $links  = $this->linksInlineDeferrer->handleItems($object, $links, $context);

        if (count($links) > 0) {
            $this->jsonSerializer->serializeLinks($links, $event->getVisitor(), $context);
        }

        if (count($embeddeds) > 0) {
            $this->jsonSerializer->serializeEmbeddeds($embeddeds, $event->getVisitor(), $context);
        }

        $context->stopVisiting($object);
    }
}
