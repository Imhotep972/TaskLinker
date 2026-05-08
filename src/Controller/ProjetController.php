<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\ProjetRepository;
use App\Repository\StatutRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Projet;
use App\Form\ProjetType;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class ProjetController extends AbstractController
{
    public function __construct(
        private ProjetRepository $projetRepository,
        private StatutRepository $statutRepository,
        private EntityManagerInterface $entityManager,
    )
    {

    }

    /* affichage des projets si ROLE_ADMIN ou seulement ceux de l'user */
    #[Route('/', name: 'app_projets')]
    public function projets(): Response
    {
 
    /*
        Affichage de la liste des projets de l'utilisateur ou bien tous si ROLE_ADMIN
        avec $this->projetRepository->findByEmploye($user->getId())
    */
        $user = $this->getUser();
        $employId = $user->getId();
        if (in_array('ROLE_ADMIN',$user->getRoles()) )
        {
            $projets = $this->projetRepository->findBy([
                'archive' => false,
            ]);
        }
        else
        {
            // Récupération des projets liés à l'employé
            $projets = $this->projetRepository->findByEmploye($employId);
        }
        return $this->render('projet/liste.html.twig', [
            'projets' => $projets, 
        ]); 
    }

    /* Uniquement acces si ROLE_ADMIN */
    #[IsGranted('ROLE_ADMIN')]
    #[Route('/projets/ajouter', name: 'app_projet_add')]
    public function ajouterProjet(Request $request): Response
    {  
        $projet = new Projet();

        $form = $this->createForm(ProjetType::class, $projet);
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()) {
            $projet->setArchive(false);
            $this->entityManager->persist($projet);
            $this->entityManager->flush();
            return $this->redirectToRoute('app_projet', ['id' => $projet->getId()]);
        }

        return $this->render('projet/nouveau.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /* acces si user appartient au projet ou ROLE_ADMIN */
    #[isGranted('acces_projet','id')]
    #[Route('/projets/{id}', name: 'app_projet', methods: ['GET'], requirements: ['id'=>'\d+'])]
    public function projet(int $id): Response
    {  
        $statuts = $this->statutRepository->findAll();
        $projet = $this->projetRepository->find($id);
        $user = $this->getUser();
        $employeId = $user->getId();

        if(!$projet || $projet->isArchive()) {
            return $this->redirectToRoute('app_projets');
        }

        return $this->render('projet/projet.html.twig', [
            'projet' => $projet,
            'statuts' => $statuts,
            'employeId', $employeId,
        ]);
    }

    /* Uniquement acces si ROLE_ADMIN */
    #[IsGranted('ROLE_ADMIN')]
    #[Route('/projets/{id}/archiver', name: 'app_projet_archive', methods: ['GET'], requirements: ['id'=>'\d+'])]
    public function archiverProjet(int $id): Response
    {  
        $projet = $this->projetRepository->find($id);

        if(!$projet || $projet->isArchive()) {
            return $this->redirectToRoute('app_projets');
        }

        $projet->setArchive(true);
        $this->entityManager->persist($projet);
        $this->entityManager->flush();
        
        return $this->redirectToRoute('app_projets');
    }

    /* Uniquement acces si ROLE_ADMIN  */
    #[IsGranted('ROLE_ADMIN')]
    #[Route('/projets/{id}/editer', name: 'app_projet_edit', methods: ['GET'], requirements: ['id'=>'\d+'])]
    public function editerProjet(int $id, Request $request): Response
    {  
        $projet = $this->projetRepository->find($id);

        if(!$projet || $projet->isArchive()) {
            return $this->redirectToRoute('app_projets');
        }

        $form = $this->createForm(ProjetType::class, $projet);
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()) {
        
            $projet->setArchive(false);
            $this->entityManager->flush();

           return $this->redirectToRoute('app_projet', ['id' => $projet->getId()]);
        }

        return $this->render('projet/editer.html.twig', [
            'projet' => $projet,
            'form' => $form->createView(),
        ]);
    }
}
