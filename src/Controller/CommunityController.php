<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\CategoryRepository;
use App\Repository\BookReadRepository;
use App\Repository\BookRepository;
use App\Repository\UserRepository;
use App\Repository\FavoriteRepository;
use App\Repository\CommentaireRepository;
use App\Entity\Commentaire;
use App\Entity\Favorite;
use Doctrine\ORM\EntityManagerInterface;

class CommunityController extends AbstractController
{
    public function __construct(BookReadRepository $bookReadRepository, BookRepository $bookRepository, CategoryRepository $categoryRepository, UserRepository $userRepository, FavoriteRepository $favoriteRepository, CommentaireRepository $commentaireRepository)
    {
        $this->bookReadRepository = $bookReadRepository;
        $this->bookRepository = $bookRepository;
        $this->categoryRepository = $categoryRepository;
        $this->userRepository = $userRepository;
        $this->favoriteRepository = $favoriteRepository;
        $this->commentaireRepository = $commentaireRepository;
    }

    #[Route('/community', name: 'app.community')]
    public function index(Request $request): Response
    {

        $booksList = $this->bookReadRepository->findBy(['is_read' => true]);

        $books = [];

        foreach ($booksList as $book) {
            $bookInfo = $this->bookRepository->findOneBy(['id' => $book->getBookId()]);
            $category = $this->categoryRepository->findOneBy(['id' => $bookInfo->getCategoryId()])->getName();
            $user = $this->userRepository->findOneBy(['id' => $book->getUserId()])->getEmail();
            $rating = $book->getRating();
            $description = $book->getDescription();
            $date = $book->getUpdatedAt();
            $heart = $this->favoriteRepository->findOneBy(['UserId' => $request->cookies->get('id'), 'BookReadId' => $book->getId()]) ? "❤️" : "🖤";

            $books[] = [
                'heart' => $heart,
                'id' => $book->getId(),
                'favorite' => false,
                'name' => $bookInfo->getName(),
                'category' => $category,
                'user' => $user,
                'rating' => $rating,
                'description' => $description,
                'date' => $date,
            ];
        }


        return $this->render('community/index.html.twig', [
            'name' => 'Explorer',
            'books' => $books,
        ]);
    }

    #[Route('/favorite', name: 'app.favorite')]
    public function favorite(Request $request, EntityManagerInterface $entityManager): Response
    {
        $data = json_decode($request->getContent(), true);

        $userId = $request->cookies->get('id');

        if ($data['heart']) {

            $favorite = new Favorite();
            $favorite->setUserId($userId);
            $favorite->setBookReadId($data['title']);
            $entityManager->persist($favorite);

        } else {

            $favorite = $this->favoriteRepository->findOneBy(['UserId' => $userId, 'BookReadId' => $data['title']]);
            $entityManager->remove($favorite);

        }

        $entityManager->flush();

        return new JsonResponse(['ok' => true, 'message' => 'favorit mis a jour.'], Response::HTTP_CREATED);
    }

    #[Route('/comment', name: 'app.comment')]
    public function comment(Request $request, EntityManagerInterface $entityManager): Response
    {

        $data = json_decode($request->getContent(), true);
        $userId = $request->cookies->get('id');
        
        $commentaire = new Commentaire();

        $commentaire->setUserId($userId);
        $commentaire->setBookReadId($data['bookId']);
        $commentaire->setComment($data['commentaire']);

        // Enregistrer l'utilisateur dans la base de données
        $entityManager->persist($commentaire);
        $entityManager->flush();

        return new JsonResponse(['ok' => true, 'message' => 'Avis créé avec succès.'], Response::HTTP_CREATED);
    }

    #[Route('/getComment', name: 'app.getComment')]
    public function getComment(Request $request): Response
    {
        $data = json_decode($request->getContent(), true);
        $comments = $this->commentaireRepository->findBy(['BookReadId' => $data['bookId']]);
        $commentList = [];
        foreach ($comments as $comment) {
            $user = $this->userRepository->findOneBy(['id' => $comment->getUserId()])->getEmail();
            $commentList[] = ['user' => $user, 'comment' => $comment->getComment()];
        }
        
        return new JsonResponse(['ok' => true, 'message' => 'Avis créé avec succès.', 'comments' => $commentList], Response::HTTP_CREATED);
    }

}
