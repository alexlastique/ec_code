<?php

namespace App\Controller;

use App\Repository\BookReadRepository;
use App\Repository\BookRepository;
use App\Repository\CategoryRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    private BookReadRepository $readBookRepository;

    // Inject the repository via the constructor
    public function __construct(BookReadRepository $bookReadRepository, BookRepository $bookRepository, CategoryRepository $categoryRepository)
    {
        $this->bookReadRepository = $bookReadRepository;
        $this->bookRepository = $bookRepository;
        $this->categoryRepository = $categoryRepository;
    }

    #[Route('/', name: 'app.home')]
    public function index(Request $request): Response
    {
        $cookie = $request->cookies->get('id');
        $userId = $cookie;
        if (!$userId) {
            return $this->redirectToRoute('auth.login');
        }
        $booksR = $this->bookReadRepository->findByUserId($userId);
        $books = $this->bookRepository->findAll();
        $booksRead = [];
        $booksUnread = [];

        foreach ($booksR as $book) {
            $bookInfo = $this->bookRepository->findOneBy(['id' => $book->getBookId()]);
            if ($book->isRead()) {
                $bookRead = [
                    'name' => $bookInfo->getName(),
                    'description' => $bookInfo->getDescription(),
                    'bookId' => $book->getBookId(),
                    'rating' => $book->getRating(),
                ];
                $booksRead[] = $bookRead;
            } else {
                $bookUnread = [
                    'name' => $bookInfo->getName(),
                    'description' => $bookInfo->getDescription(),
                    'date' => $bookInfo->getUpdatedAt(),
                ];
                $booksUnread[] = $bookUnread;
            }
        }

        $category = $this->categoryRepository->findAll();
        $categorytab = "[";

        $lenC = 0;
        foreach ($category as $cat) {
            $lenC += 1;
            if ($categorytab=="["){
                $categorytab .= "'" . $cat->getName() . "'";
            }else{
                $categorytab .= ", '" . $cat->getName() . "'";
            }
        }
        $categorytab.= "]";

        $categoryValue = [];
        $booksRating = $this->bookReadRepository->findReadByUserId($userId);
        foreach ($booksRating as $book) {
            $bookInfo = $this->bookRepository->findOneBy(['id' => $book->getBookId()]);
            $categoryId = $this->categoryRepository->findOneBy(['id' => $bookInfo->getCategoryId()])->getName();
            if (array_key_exists($categoryId, $categoryValue)) {
                $categoryValue[$categoryId]['count']++;
                $categoryValue[$categoryId]['total'] += $book->getRating();
            } else {
                $categoryValue[$categoryId] = [
                    'count' => 1,
                    'total' => $book->getRating(),
                ];
            }
        }
        
        $categoryValuetab = "[";
        $lenV = 0;
        foreach ($categoryValue as $categoryId => $value) {
            $lenV += 1;
            if ($categoryValuetab=="["){
                $categoryValuetab .= "'" . round($value['total'] / $value['count'], 2) . "'";
            }else{
                $categoryValuetab .= ", '" . round($value['total'] / $value['count'], 2) . "'";
            }
        }
        while ($lenV < $lenC){
            $categoryValuetab.= ", '0'";
            $lenV += 1;
        };
        $categoryValuetab.= "]";

        // Render the 'hello.html.twig' template
        return $this->render('pages/home.html.twig', [
            'books' => $books,
            'booksRead' => $booksRead,
            'booksUnread' => $booksUnread,
            'category' => $categorytab,
            'categoryValue' => $categoryValuetab,
            'name' => 'Accueil',
        ]);
    }
    
    #[Route('/GCU', name: 'app.GCU')]
    public function register(): Response
    {
        return $this->render('pages/GCU.html.twig', [
        ]);
    }

    #[Route('/logout', name: 'app.logout')]
    public function logout(): Response
    {
        if (isset($_COOKIE['id'])) {
            setcookie('id', '', time() - 3600, '/');
        }
        return $this->redirectToRoute('app.home');
    }
}
