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


class RegisterController extends AbstractController
{
    #[Route('/register', name: 'auth.register')]
    public function register(): Response
    {
        
        return $this->render('auth/register.html.twig', [
            
        ]);
    }
    
    #[Route('/registerform', name: 'auth.registerform', methods: ['POST'])]
    public function registerform(Request $request, EntityManagerInterface $entityManager): Response
    {
        // Récupérer le contenu JSON de la requête
        $data = json_decode($request->getContent(), true);

        // Vérification des données
        $password = $data['password'] ?? null;
        $mail = $data['mail'] ?? null;
        $GCU = $data['GCU'] ?? null;

        if (!$password || !$mail) {
            return new JsonResponse(['ok' => false, 'message' => 'Données manquantes.'], Response::HTTP_OK);
        }

        if (!$GCU) {
            return new JsonResponse(['ok' => false, 'message' => 'Les CGU doivent être acceptées.'], Response::HTTP_OK);
        }

        // Validation du mail
        if (!filter_var($mail, FILTER_VALIDATE_EMAIL)) {
            return new JsonResponse(['ok' => false, 'message' => 'Email invalide.'], Response::HTTP_OK);
        }

        // Vérifier si l'utilisateur existe déjà
        $existingUser = $entityManager->getRepository(User::class)->findOneBy(['email' => $mail]);
        if ($existingUser) {
            return new JsonResponse(['ok' => false, 'message' => 'Cet email est déjà utilisé.'], Response::HTTP_OK);
        }

        // Vérifier si le mot de passe est suffisamment complexe
        if (strlen($password) < 8 || !preg_match('/[a-z]/', $password) || !preg_match('/[A-Z]/', $password) || !preg_match('/\d/', $password)) {
            return new JsonResponse(['ok' => false, 'message' => 'Le mot de passe doit contenir au moins 8 caractères, une lettre minuscule, une lettre majuscule et un chiffre.'], Response::HTTP_OK);
        }

        // Créer un utilisateur
        $user = new User();
        $user->setEmail($mail);
        $user->setPassword(hash("sha256",$password));

        // Enregistrer l'utilisateur dans la base de données
        $entityManager->persist($user);
        $entityManager->flush();

        return new JsonResponse(['ok' => true, 'message' => 'Inscription réussie.'], Response::HTTP_CREATED);
    }


}
