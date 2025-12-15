<?php

namespace App\EventSubscriber;

use App\Repository\GeneralDataRepository;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Twig\Environment;

class GlobalVariablesSubscriber implements EventSubscriberInterface
{
    private Environment $twig;
    private GeneralDataRepository $generalDataRepository;

    public function __construct(Environment $twig, GeneralDataRepository $generalDataRepository)
    {
        $this->twig = $twig;
        $this->generalDataRepository = $generalDataRepository;
    }

    public function onKernelController(ControllerEvent $event): void
    {
        // Avoid running on sub-requests if desired, or run on all suitable controllers
        // if (!$event->isMainRequest()) { return; }
        
        // Check if we already added it to avoid overhead on multiple controller calls (e.g. fragments)
        $globals = $this->twig->getGlobals();
        if (array_key_exists('generalData', $globals)) {
             return;
        }

        $generalData = $this->generalDataRepository->findOneBy([]);
        
        // Fallback if null (shouldn't happen if seeded, but good for safety)
        if (!$generalData) {
            $generalData = new \App\Entity\GeneralData();
            // Optional: set default values
        }

        $this->twig->addGlobal('generalData', $generalData);
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::CONTROLLER => 'onKernelController',
        ];
    }
}
