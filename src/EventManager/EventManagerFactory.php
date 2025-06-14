<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Doctrine\EventManager;

use Doctrine\Common\EventManager;
use Doctrine\Common\EventSubscriber;
use RuntimeException;
use Yiisoft\Injector\Injector;
use Yiisoft\Yii\Doctrine\Orm\Enum\ConfigOptions;

use function sprintf;

final class EventManagerFactory
{
    public function __construct(private readonly Injector $injector)
    {
    }

    public function create(array $eventConfig): EventManager
    {
        $eventManager = new EventManager();

        $this->configureListener($eventManager, $eventConfig[ConfigOptions::EVENTS_LISTENERS] ?? []);

        $this->configureSubscribers($eventManager, $eventConfig[ConfigOptions::EVENTS_SUBSCRIBERS] ?? []);

        return $eventManager;
    }

    private function configureListener(EventManager $eventManager, array $listeners = []): void
    {
        foreach ($listeners as $eventName => $classListeners) {
            foreach ($classListeners as $classListener) {
                $eventManager->addEventListener($eventName, $this->injector->make($classListener));
            }
        }
    }

    private function configureSubscribers(EventManager $eventManager, array $subscribers = []): void
    {
        foreach ($subscribers as $className) {
            $subscriber = $this->injector->make($className);

            if (!$subscriber instanceof EventSubscriber) {
                throw new RuntimeException(sprintf('Class %s not instanceof %s', $className, EventSubscriber::class));
            }

            $eventManager->addEventSubscriber($subscriber);
        }
    }
}
