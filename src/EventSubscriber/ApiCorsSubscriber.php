<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final class ApiCorsSubscriber implements EventSubscriberInterface
{
    private const API_PREFIX = '/api';

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 100],
            KernelEvents::RESPONSE => ['onKernelResponse', 0],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        if (!str_starts_with($request->getPathInfo(), self::API_PREFIX)) {
            return;
        }

        if ($request->getMethod() !== 'OPTIONS') {
            return;
        }

        $response = new Response(null, Response::HTTP_NO_CONTENT);
        $this->applyCorsHeaders($event->getRequest(), $response);
        $event->setResponse($response);
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        if (!str_starts_with($request->getPathInfo(), self::API_PREFIX)) {
            return;
        }

        $this->applyCorsHeaders($request, $event->getResponse());
    }

    private function applyCorsHeaders(\Symfony\Component\HttpFoundation\Request $request, Response $response): void
    {
        $origin = (string) $request->headers->get('Origin', '');
        $allowOrigin = $origin !== '' ? $origin : '*';

        $response->headers->set('Access-Control-Allow-Origin', $allowOrigin);
        $response->headers->set('Vary', 'Origin');
        $response->headers->set('Access-Control-Allow-Methods', 'GET,POST,PUT,DELETE,OPTIONS');
        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization');
        $response->headers->set('Access-Control-Max-Age', '86400');
    }
}

