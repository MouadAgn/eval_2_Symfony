<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Categorie;
use App\Repository\CategorieRepository;
use App\Repository\ArticleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use App\Form\CategorieType;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class CategorieController extends AbstractController
{
    private EntityManagerInterface $em;
    private CategorieRepository $categorieRepository;
    private ArticleRepository $articleRepository;

    public function __construct(EntityManagerInterface $em, CategorieRepository $categorieRepository, ArticleRepository $articleRepository)
    {
        $this->em = $em;
        $this->categorieRepository = $categorieRepository;
        $this->articleRepository = $articleRepository;
    }

    #[Route('/', name: 'app_home')]
    public function home(): Response
    {
        $troisDerCategories = $this->categorieRepository->findBy([], ['id' => 'DESC'], 3);

        return $this->render('categorie/home.html.twig', [
            'categories' => $troisDerCategories,
            'articles' => $this->articleRepository->findAll(),
        ]);
    }

    #[Route('/categorie', name: 'app_categorie')]
    public function index(Request $request): Response
    {
        $categorie = new Categorie();
        $form = $this->createForm(CategorieType::class, $categorie);
        $form->handleRequest($request);

        if ($form->isSubmitted() && !$form->isValid()) {
            $this->addFlash('danger', 'La categorie n\'a pas été ajoutée.');
        } elseif ($form->isSubmitted() && $form->isValid()) {
            $this->em->persist($categorie);
            $this->em->flush();
            $this->addFlash('success', 'La categorie a bien été ajoutée.');
            return $this->redirectToRoute('app_categorie');
        }

        return $this->render('categorie/index.html.twig', [
            'controller_name' => 'CategorieController',
            'form' => $form->createView(),
            'categories' => $this->categorieRepository->findAll(),
            'articles' => $this->articleRepository->findAll(),
        ]);
    }

    #[Route('/categorie-details/{id}', name: 'detail_categorie')]
    public function details(Categorie $categorie = null): Response
    {
        if ($categorie == null) {
            return $this->redirectToRoute('app_categorie');
        } else {
            return $this->render('categorie/details.html.twig', [
                'categorie' => $categorie,
                'articles' => $this->articleRepository->findBy(['categorie' => $categorie->getId()]),
            ]);
        }
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/categorie-delete/{id}', name: 'delete_categorie')]
    public function delete($id): Response
    {
        $categorie = $this->em->getRepository(Categorie::class)->find($id);

        if ($categorie == null) {
            return $this->redirectToRoute('app_categorie');
        } else {
            $this->deleteCategoryAndRelatedEntities($categorie);
            $this->addFlash('success', 'Success');
            return $this->redirectToRoute('app_categorie');
        }
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/categorie-update/{id}', name: 'update_categorie')]
    public function editCategorie(Request $request, $id): Response
    {
        $categorie = $this->em->getRepository(Categorie::class)->find($id);
        $form = $this->createForm(CategorieType::class, $categorie);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->flush();
            $this->addFlash('success', 'Success');
            return $this->redirectToRoute('app_categorie');
        }

        return $this->render('categorie/edit.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * Deletes a category and its related entities (articles and comments).
     */
    private function deleteCategoryAndRelatedEntities(Categorie $categorie): void
    {
        $articles = $categorie->getArticles();

        foreach ($articles as $article) {
            $commentaires = $article->getCommentaires();

            foreach ($commentaires as $commentaire) {
                $this->em->remove($commentaire);
            }

            $this->em->remove($article);
        }

        $this->em->remove($categorie);
        $this->em->flush();
    }
}
