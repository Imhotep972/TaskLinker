<?php

namespace App\Security\Voter;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;
use App\Repository\ProjetRepository;
use App\Repository\TacheRepository;

final class ProjetVoter extends Voter
{
    public const ACCES_PROJET = 'acces_projet';
    public const ACCES_TACHE = 'acces_tache';

    public function __construct(private ProjetRepository $projetRepository,private TacheRepository $tacheRepository,)
    {
        
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        if (!in_array($attribute, [self::ACCES_PROJET, self::ACCES_TACHE]))
            return false;

        return true;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        // on recupere l'utilisateur en cours
        $user = $token->getUser();
        $uid = $user->getId();
        // si l'utilisatuer n'est pas connecte on refuse l'acces
        if (!$user instanceof UserInterface) {
            // en principe on n'y arrive pas car redirection si non connecté
            $vote?->addReason("Vous n'avez pas l'autorisation d'acceder à cette page.");

            return false;
        }

        switch ($attribute) {
            case self::ACCES_PROJET:
                // Si USER a pour role ROLE_ADMIN il a le droit
                if (in_array('ROLE_ADMIN',$user->getRoles()) )
                    return true;
                else 
                {
                    // on recupere les projets de l'utilisateur et si il appartient au projet on donne l'acces
                    $projets = $this->projetRepository->findByEmploye($uid);
                    foreach($projets as $projet) {               
                        if ($projet->getID() == $subject)
                            return true; 
                    }
                    // si on arrive là, l'utilisateur n'appartient pas au
                    $vote?->addReason("Vous n'appartenez pas à ce projet");
                    return false; 
                }
                break;

            case self::ACCES_TACHE:
                // USER id doit etre dans l'objet Tache
                // on recupere la tache
                if (in_array('ROLE_ADMIN',$user->getRoles()) )
                    return true;
                else 
                {
                    $taches = $this->tacheRepository->find($subject);
                    $employe = $taches->getEmploye();
                    if (!$employe or $user->getId() != $employe->getID()){
                        $vote?->addReason("Vous n'appartenez pas à cette tache");
                        return false;
                    }
                    else 
                        return true;
                }
                break;
        }
        $vote?->addReason("Vous n'avez pas l'autorisation d'acceder à cette page.");
        return false;
    }
}
