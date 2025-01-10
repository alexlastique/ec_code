<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\HttpFoundation\Cookie;

class LoginController extends AbstractController
{
    #[Route('/login', name: 'auth.login')]
    public function login(): Response
    {
        // Render the 'hello.html.twig' template
        return $this->render('auth/login.html.twig', [
            'name' => 'Thibaud', // Pass data to the view
        ]);
    }
    
    #[Route('/loginform', name: 'auth.loginform', methods: ['POST'])]
    public function registerform(Request $request, EntityManagerInterface $entityManager): Response
    {
        // Récupérer le contenu JSON de la requête
        $data = json_decode($request->getContent(), true);

        // Vérification des données
        $password = $data['password'] ?? null;
        $mail = $data['mail'] ?? null;
        if (!$password || !$mail) {
            return new JsonResponse(['ok' => false, 'message' => 'Données manquantes.'], Response::HTTP_OK);
        }

        // Vérifier si l'utilisateur existe déjà
        $user = $entityManager->getRepository(User::class)->findOneBy(['email' => $mail]);
        if (!$user) {
            return new JsonResponse(['ok' => false, 'message' => 'Mot de passe ou utilisateur incorrect.'], Response::HTTP_OK);
        }
        $password = hash("sha256",$password);
        if ($user->getPassword() == $password) {

            $cookieName = 'id';
            $cookieValue = $user->getId();
            $expiration = time() + (60 * 120);

            $cookie = Cookie::create($cookieName)
                ->withValue($cookieValue)
                ->withExpires($expiration)
                ->withPath('/')
                ->withSecure(false)
                ->withHttpOnly(true);

            $response = new JsonResponse(['ok' => true, 'message' => 'Connexion réussie.'], Response::HTTP_CREATED);
            $response->headers->setCookie($cookie);
        
            return $response;

        } else {
            return new JsonResponse(['ok' => false, 'message' => 'Mot de passe ou utilisateur incorrect.'], Response::HTTP_OK);
        }

    }


}
