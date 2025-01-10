<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use App\Repository\BookRepository;
use App\Repository\CategoryRepository;
use App\Entity\BookRead;
use Doctrine\ORM\EntityManagerInterface;

class BookController extends AbstractController
{
    #[Route('/book', name: 'app.book')]
    public function index(Request $request, EntityManagerInterface $entityManager, BookRepository $bookRepository, CategoryRepository $categoryRepository): Response
    {
        // Récupérer le contenu JSON de la requête
        $data = json_decode($request->getContent(), true);

        // Vérification des données
        $title = $data['title'] ?? null;
        $description = $data['description'] ?? null;
        $rating = $data['rating'] ?? null;
        $finish = $data['finish'];

        if (!$title || !$rating) {
            return new JsonResponse(['ok' => false, 'message' => 'Données manquantes.'], Response::HTTP_OK);
        }

        if (!is_numeric($rating)) {
            return new JsonResponse(['ok' => false, 'message' => 'La note n\'est pas un nombre.'], Response::HTTP_OK);
        }

        if ($description == "") {
            return new JsonResponse(['ok' => false, 'message' => 'La description est vide.'], Response::HTTP_OK);
        }

        // Créer un livre
        $bookRead = new BookRead();
        $book = $bookRepository->findOneBy(['name' => $title]);
        
        if (!$book) {
            return new JsonResponse(['ok' => false, 'message' => 'Aucun livre a ce nom.'], Response::HTTP_OK);
        }

        // recuperer un cookie
        $cookie = $request->cookies->get('id');
        if (!$cookie) {
            return new JsonResponse(['ok' => false, 'message' => 'Aucun utilisateur connecté.'], Response::HTTP_OK);
        }
        $userId = $cookie;

        // Créer une nouvelle entité BookRead
        $bookRead->setUserId($userId);
        $bookRead->setBookId($book->getId());
        $bookRead->setDescription($description);
        $bookRead->setRating($rating);
        $bookRead->setRead($finish);
        $now = new \DateTime();
        $bookRead->setCreatedAt($now);
        $bookRead->setUpdatedAt($now);

        // Enregistrer le livre dans la base de données
        $entityManager->persist($bookRead);
        $entityManager->flush();

        $description = $book->getDescription();
        $category = $categoryRepository->findOneBy(['id' => $book->getCategoryId()]);
        $date = $book->getUpdatedAt();

        return new JsonResponse(['ok' => true, 'message' => 'Avis créé avec succès.', 'description' => $description, 'category' => $category, 'date' => $date], Response::HTTP_CREATED);

    }


}
