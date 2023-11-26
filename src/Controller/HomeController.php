<?php

namespace App\Controller;

use App\Entity\Categorie;
use App\Form\CategorieType;
use App\Repository\CategorieRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\BrowserKit\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    #[Route('/homepage', name: 'app_homepage')]
    public function index(CategorieRepository $categorieRepository): Response

    {
        return $this->render('homepage/index.html.twig', [
            'lastCategories' => $categorieRepository->findBy([], ['id' => 'DESC'], 3)
        ]);
    }
}