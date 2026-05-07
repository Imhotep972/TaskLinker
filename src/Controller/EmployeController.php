<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Entity\Employe;
use App\Repository\EmployeRepository;
use App\Form\EmployeType;
use App\Form\RegisterType;

class EmployeController extends AbstractController
{
    public function __construct(private EmployeRepository $employeRepository, private EntityManagerInterface $entityManager, )
    {

    }

    /*  Gestion acces   */
    #[Route(path: '/connexion', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();

        // last username entered by the user
        $email = $authenticationUtils->getLastUsername();

        return $this->render('auth/login.html.twig', [
            'email' => $email,
            'error' => $error,
        ]);
    }

    #[Route(path: '/deconnexion', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }

    #[Route('/bienvenue', name: 'app_bienvenue')]
    public function bienvenue(): Response
    {
        return $this->render('auth/bienvenue.html.twig');
    }

    #[Route('/inscription', name : 'app_register')]
    public function register(Request $request, UserPasswordHasherInterface $hasher): Response
    {
        $employe = new Employe();
        // on met la date courrante comme date d'entree et statut CDI
        $employe
            ->setStatut('CDI')
            ->setDateArrivee(new \DateTimeImmutable())
            ->setRoles(array('ROLE_USER'));

        // on cree le formulaire
        $form = $this->createForm(RegisterType::class, $employe);
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()) {
            $employe->setPassword($hasher->hashPassword($employe, $employe->getPassword()));
            $this->entityManager->persist($employe);
            $this->entityManager->flush(); 
            return $this->redirectToRoute('app_projets');
        }

        return $this->render('auth/register.html.twig', [
            'form' => $form,
        ]);

    }

    /*  Gestion employé    */
    #[IsGranted('ROLE_ADMIN')]
    #[Route('/employes', name: 'app_employes')]
    public function employes(): Response
    {
        $employes = $this->employeRepository->findAll();
        
        return $this->render('employe/liste.html.twig', [
            'employes' => $employes,
        ]);
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/employes/{id}', name: 'app_employe', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function employe(int $id): Response
    {
        $employe = $this->employeRepository->find($id);

        if(!$employe) {
            $this->addFlash('error', 'Aucun employé avec cet ID.');
            return $this->redirectToRoute('app_employes');
        }
        
        return $this->render('employe/employe.html.twig', [
            'employe' => $employe,
        ]);
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/employes/{id}/supprimer', name: 'app_employe_delete', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function supprimerEmploye(int $id): Response
    {
        $employe = $this->employeRepository->find($id);

        if(!$employe) {
            $this->addFlash('error', 'Aucun employé avec cet ID.');
            return $this->redirectToRoute('app_employes');
        }

        $this->entityManager->remove($employe);
        $this->entityManager->flush();
        
        return $this->redirectToRoute('app_employes');
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/employes/{id}/editer', name: 'app_employe_edit', methods: ['GET'], requirements: ['id'=>'\d+'])]
    public function editEmployer(int $id, Request $request): Response
    {
        $employe = $this->employeRepository->find($id);

        if(!$employe) {
            $this->addFlash('error', 'Aucun employé avec cet ID.');
            return $this->redirectToRoute('app_employes');
        }

        $form = $this->createForm(EmployeType::class, $employe);
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();
            return $this->redirectToRoute('app_employes');
        }

        // a voir  'form' => $form->createView();
        return $this->render('employe/employe.html.twig', [
            'employe' => $employe,
            'form' => $form->createView(),  
        ]);
    }
}
