<?php

namespace App\EventSubscriber;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\RateLimiter\RateLimiterFactoryInterface;

final class RateLimitSubscriber implements EventSubscriberInterface
{
    public function __construct(
        #[Autowire(service: 'limiter.login')]
        private readonly RateLimiterFactoryInterface $loginLimiter,
        #[Autowire(service: 'limiter.ai')]
        private readonly RateLimiterFactoryInterface $aiLimiter,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::REQUEST => ['onRequest', 30]];
    }

    public function onRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }
        $request = $event->getRequest();
        $path = $request->getPathInfo();
        $ip = $request->getClientIp() ?? 'unknown';

        if ($path === '/api/login' && $request->isMethod('POST')) {
            $this->consume($this->loginLimiter, $ip, 'تعداد تلاش ورود بیش از حد مجاز است.');
        }
        if (str_starts_with($path, '/api/ai/') && $request->isMethod('POST')) {
            $this->consume($this->aiLimiter, $ip, 'تعداد درخواست دستیار دانش بیش از حد مجاز است.');
        }
    }

    private function consume(RateLimiterFactoryInterface $factory, string $key, string $message): void
    {
        if (!$factory->create($key)->consume(1)->isAccepted()) {
            throw new TooManyRequestsHttpException(60, $message);
        }
    }
}
