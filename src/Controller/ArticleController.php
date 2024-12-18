<?php

namespace App\Controller;

use App\Entity\Article;
use App\Form\ArticleType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class ArticleController extends AbstractController
{
    #[Route('/article', name: 'app_article')]
    public function index(): Response
    {
        return $this->render('article/index.html.twig', [
            'controller_name' => 'ArticleController',
        ]);
    }

    #[Route('/article/cree', name: 'app_article_create')]

    public function create(EntityManagerInterface $entityManager,Request $request,SluggerInterface $slugger,
    #[Autowire('%kernel.project_dir%/public/uploads/brochures')] string $brochuresDirectory): Response
    {

        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $article = new Article();
        $form=$this->createForm(ArticleType::class,$article);
        $form->handleRequest($request);
        if($form->isSubmitted()&& $form->isValid()){
            $brochureFile = $form->get('image')->getData();

            if ($brochureFile) {
                $originalFilename = pathinfo($brochureFile->getClientOriginalName(), PATHINFO_FILENAME);
                // this is needed to safely include the file name as part of the URL
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$brochureFile->guessExtension();

                // Move the file to the directory where brochures are stored
                try {
                    $brochureFile->move($brochuresDirectory, $newFilename);
                } catch (FileException $e) {
                    // ... handle exception if something happens during file upload
                }

                // updates the 'brochureFilename' property to store the PDF file name
                // instead of its contents
                $article->setImage($newFilename);
            }

            $this->addFlash('success', 'Article Created!');
            $entityManager->persist($article);
            $entityManager->flush();
        }

        return $this->render('article/creer.html.twig', [
            'controller_name' => 'ArticleController',
            'titre' => 'Article',
            'article' => $article,
            'form'=>$form
        ]);
    }

    #[Route('/article/update/{id}', name: 'app_article_update')]

    public function update(EntityManagerInterface $entityManager, int $id): Response
    {

        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $article = $entityManager->getRepository(Article::class)->find($id);

        if (!$article) {
            throw $this->createNotFoundException(
                'No article found for id ' . $id
            );
        }

        $article->setTitre('New article name!');
        $entityManager->flush();

        return $this->redirectToRoute('article_show', [
            'id' => $article->getId()
        ]);
    }

    #[Route('/article/show', name: 'article_show')]
    public function show(EntityManagerInterface $entityManager): Response
    {
        $articles = $entityManager->getRepository(Article::class)->findAll();

        return $this->render('article/show.html.twig', [
            'articles' => $articles
        ]);
    }

    #[Route('/article/delete/{id}', name: 'article_delete')]


    public function delete(EntityManagerInterface $entityManager, int $id)
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $article = $entityManager->getRepository(Article::class)->find($id);
        $entityManager->remove($article); 
        $entityManager->flush();
        return $this->redirectToRoute('article_show');
        
    }


    
    #[Route('/article/modify/{id}', name: 'article_modify')]
    public function modify(EntityManagerInterface $entityManager,$id,Request $request,SluggerInterface $slugger,
    #[Autowire('%kernel.project_dir%/public/uploads/brochures')] string $brochuresDirectory): Response
    {

        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $article = $entityManager->getRepository(Article::class)->find($id);
        
        
        $form=$this->createForm(ArticleType::class,$article);

        $form->handleRequest($request);

        if($form->isSubmitted()&& $form->isValid()){
            $article = $form ->getData();
            $brochureFile = $form->get('image')->getData();

            if ($brochureFile) {
                $originalFilename = pathinfo($brochureFile->getClientOriginalName(), PATHINFO_FILENAME);
                // this is needed to safely include the file name as part of the URL
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$brochureFile->guessExtension();

                // Move the file to the directory where brochures are stored
                try {
                    $brochureFile->move($brochuresDirectory, $newFilename);
                } catch (FileException $e) {
                    // ... handle exception if something happens during file upload
                }

                // updates the 'brochureFilename' property to store the PDF file name
                // instead of its contents
                $article->setImage($newFilename);
            }
            $this->addFlash('success', 'Article Modify!');
            $entityManager->persist($article);
            $entityManager->flush();
        }

        return $this->render('article/modify.html.twig', [
            'article' => $article,
            'form'=>$form->createView()
        ]);
    }
}