<?php

namespace App\Security;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Http\Authorization\AccessDeniedHandlerInterface;

class AccessDeniedHandler implements AccessDeniedHandlerInterface
{
    public function __construct(
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function handle(Request $request, AccessDeniedException $accessDeniedException): ?Response
    {
        $session = $request->hasSession() ? $request->getSession() : null;
        if ($session !== null && method_exists($session, 'getFlashBag')) {
            $session->getFlashBag()->add(
                'error',
                'Vous n\'avez pas les droits necessaires pour acceder a cette page.'
            );
        }

        return new RedirectResponse($this->urlGenerator->generate('app_home'));
    }
}
