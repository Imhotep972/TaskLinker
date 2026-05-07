<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\ProjetRepository;
use App\Repository\TacheRepository;
use App\Form\TacheType;
use App\Entity\Tache;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class TacheController extends AbstractController
{

    public function __construct(
        private ProjetRepository $projetRepository,
        private TacheRepository $tacheRepository,
        private EntityManagerInterface $entityManager,
    )
    {

    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/projets/{id}/taches/ajouter', name: 'app_tache_add', methods: ['GET'], requirements: ['id'=>'\d+'])]
    public function ajouterTache(int $id, Request $request): Response
    {  
        $projet = $this->projetRepository->find($id);

        if(!$projet || $projet->isArchive()) {
            return $this->redirectToRoute('app_projets');
        }

        $tache = new Tache();

        $form = $this->createForm(TacheType::class, $tache, ['projet' => $projet]);
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()) {
            $tache->setProjet($projet);
            $this->entityManager->persist($tache);
            $this->entityManager->flush();
            return $this->redirectToRoute('app_projet', ['id' => $tache->getProjet()->getId()]);
        }

        return $this->render('tache/nouveau.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/taches/{id}/supprimer', name: 'app_tache_delete', methods: ['GET'], requirements: ['id'=>'\d+'])]
    public function supprimerTache(int $id): Response
    {  
        $tache = $this->tacheRepository->find($id);

        if(!$tache || $tache->getProjet()->isArchive()) {
            return $this->redirectToRoute('app_projets');
        }

        $this->entityManager->remove($tache);
        $this->entityManager->flush();
        
        return $this->redirectToRoute('app_projet', ['id' => $tache->getProjet()->getId()]);
    }


    #[isGranted('acces_tache','id')]
    #[Route('/taches/{id}', name: 'app_tache', methods: ['GET'], requirements: ['id'=>'\d+'])]
    public function tache(int $id, Request $request): Response
    {  
        $tache = $this->tacheRepository->find($id);

        if(!$tache || $tache->getProjet()->isArchive()) {
            return $this->redirectToRoute('app_projets');
        }

        $form = $this->createForm(TacheType::class, $tache, ['projet' => $tache->getProjet()]);
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();
            return $this->redirectToRoute('app_projet', ['id' => $tache->getProjet()->getId()]);
        }

        return $this->render('tache/tache.html.twig', [
            'form' => $form->createView(),
            'tache' => $tache,
        ]);
    }
}
