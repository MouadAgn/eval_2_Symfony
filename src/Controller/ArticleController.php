<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Article;
use App\Entity\Categorie;
use App\Repository\CategorieRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use App\Form\ArticleType;
use App\Repository\ArticleRepository;
use App\Form\CommentaireType;
use App\Entity\Commentaire;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class ArticleController extends AbstractController
{
    public function __construct(private EntityManagerInterface $em, private ArticleRepository $ArticleRepository, private CategorieRepository $categorieRepository, private Security $security)
    {
            
    }

    #[Route('/article', name: 'app_article')]
    public function index(Request $request): Response
    {
        $article = new Article();
        $form = $this->createForm(ArticleType::class, $article);
        $form->handleRequest($request);
        $auteur = $this->security->getUser();
        $article->setAuteur($auteur);

        if ($form->isSubmitted() && !$form->isValid()) {
            $this->addFlash('danger', "Le formulaire contient des erreurs");
        } elseif ($form->isSubmitted() && $form->isValid()) {
            // Vérifier si l'état est égal à 1 (Publié)
            if ($article->isEtat() == 1) {
                // Si l'état est égal à 1, générer la date de publication
                $article->setDateParu(new \DateTimeImmutable());
            } else {
                // Si l'état n'est pas égal à 1, définir dateParu sur null
                $article->setDateParu(null);
            }
            if (!$auteur){
                $this->addFlash('danger', "Vous devez être connecté pour ajouter un article");
                return $this->redirectToRoute('app_article');
            } else{
                $this->em->persist($article);
                $this->em->flush();
                $this->addFlash('success', "L'article a bien été ajouté");
                return $this->redirectToRoute('app_article');

            }
        }

        return $this->render('article/index.html.twig', [
            'controller_name' => 'ArticleController',
            'form' => $form->createView(),
            'articles' => $this->ArticleRepository->findAll()
        ]);
    }

    #[Route('/article-details/{id}', name: 'detail_article')]
    public function details(int $id, Article $article = null, Request $request, CommentaireController $commentaireController)
    {
        if ($article == null) {
            return $this->redirectToRoute('app_article');
        }
        $article = $this->ArticleRepository->find($id); 
        $categorie = $article->getCategorie();
        $auteur = $article->getAuteur();
        $auteurComm = $this->getUser(); 
        $commentaire = new Commentaire();
        $commentaire->setAuteur($auteurComm);
        $commentaire->setArticle($article);
        $commentaires = $article->getCommentaires();
        
        $commentaireForm = $commentaireController->createForm(CommentaireType::class, $commentaire);
        $commentaireForm->handleRequest($request);
    
        if ($commentaireForm->isSubmitted() && $commentaireForm->isValid()) {
            $this->em->persist($commentaire);
            $this->em->flush();
            $this->addFlash('success', 'Le commentaire a bien été ajouté.');
            return $this->redirectToRoute('detail_article', ['id' => $id]);
        } elseif ($commentaireForm->isSubmitted()) {
            $this->addFlash('danger', 'Le commentaire n\'a pas été ajouté.');
        }
    
        return $this->render('article/details.html.twig', [
            'articles' => $article,
            'commentaireForm' => $commentaireForm->createView(),
            'commentaires' => $commentaires,
            'categorie' => $categorie
        ]);
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/article-modifier/{id}', name: 'modifier_article')]
    public function modifier(Request $request, Article $article = null)
    {
        if ($article == null) {
            return $this->redirectToRoute('app_article');
        } else {
            $form = $this->createForm(ArticleType::class, $article);
            $form->handleRequest($request);

            if ($form->isSubmitted() && !$form->isValid()) {
                $this->addFlash('danger', "Le formulaire contient des erreurs");
            } elseif ($form->isSubmitted() && $form->isValid()) {
                // Vérifier si l'état est égal à 1 (Publié)
                if ($article->isEtat() == 1) {
                    // Si l'état est égal à 1, générer la date de publication
                    $article->setDateParu(new \DateTimeImmutable());
                } else {
                    // Si l'état n'est pas égal à 1, définir dateParu sur null
                    $article->setDateParu(null);
                }

                $this->em->persist($article);
                $this->em->flush();
                $this->addFlash('success', "L'article a bien été modifié");
                return $this->redirectToRoute('app_article');
            }

            return $this->render('article/modifier.html.twig', [
                'form' => $form->createView(),
                'article' => $article
            ]);
        }
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/article-supprimer/{id}', name: 'supprimer_article')]
    public function supprimer(Article $article = null)
    {
        if ($article == null) {
            return $this->redirectToRoute('app_article');
        } else {
            foreach ($article->getCommentaires() as $commentaire){
                $article->removeCommentaire($commentaire);
            }
            $this->em->remove($article);
            $this->em->flush();
            $this->addFlash('success', "L'article a bien été supprimé");
            return $this->redirectToRoute('app_article');
        }
    }
}
