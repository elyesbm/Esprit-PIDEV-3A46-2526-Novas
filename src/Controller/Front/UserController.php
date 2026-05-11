<?php

namespace App\Controller\Front;

use App\Entity\User;
use App\Form\UserType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/user')]
class UserController extends AbstractController
{
    #[Route('/profile', name: 'app_user_profile')]
    public function profile(): Response
    {
        $user = $this->getAuthenticatedUser();
        if ($user === null) {
            return $this->redirectToRoute('app_login');
        }
        return $this->render('front/user/profile.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/update-face', name: 'app_user_update_face', methods: ['POST'])]
    public function updateFace(Request $request, EntityManagerInterface $em): Response
    {
        $user = $this->getAuthenticatedUser();
        if ($user === null) {
            return $this->json(['error' => 'Non connecté'], 401);
        }

        $data = json_decode($request->getContent(), true);
        if (!\is_array($data)) {
            return $this->json(['error' => 'Données invalides'], 400);
        }

        // Validation CSRF
        if (!$this->isCsrfTokenValid('face_encoding', (string) ($data['_csrf_token'] ?? ''))) {
            return $this->json(['error' => 'Token CSRF invalide'], 403);
        }

        $descriptorRaw = $data['descriptor'] ?? null;
        if (!\is_array($descriptorRaw) || \count($descriptorRaw) !== 128) {
            return $this->json(['error' => 'Descripteur facial invalide (' . \count((array) $descriptorRaw) . ' valeurs)'], 400);
        }

        $descriptor = [];
        foreach (array_values($descriptorRaw) as $value) {
            if (!\is_int($value) && !\is_float($value)) {
                return $this->json(['error' => 'Descripteur facial invalide (valeurs non numeriques)'], 400);
            }
            $descriptor[] = (float) $value;
        }

        $user->setFaceEncoding($descriptor);
        $em->flush();

        return $this->json(['success' => true, 'message' => 'Visage enregistré avec succès !']);
    }

    #[Route('/profile/edit', name: 'app_user_edit')]
    public function edit(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher
    ): Response {
        $user = $this->getAuthenticatedUser();
        if ($user === null) {
            return $this->redirectToRoute('app_login');
        }

        $form = $this->createForm(UserType::class, $user, [
            'require_password' => false,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $plainPassword = $form->get('plainPassword')->getData();
            if (is_string($plainPassword) && $plainPassword !== '') {
                $hashedPassword = $passwordHasher->hashPassword($user, $plainPassword);
                $user->setPassword($hashedPassword);
            }
            $em->flush();
            $this->addFlash('success', 'Profil mis à jour avec succès!');
            return $this->redirectToRoute('app_user_profile');
        }

        return $this->render('front/user/edit.html.twig', [
            'form' => $form,
            'user' => $user,
        ]);
    }

    private function getAuthenticatedUser(): ?User
    {
        $user = $this->getUser();
        return $user instanceof User ? $user : null;
    }
}
