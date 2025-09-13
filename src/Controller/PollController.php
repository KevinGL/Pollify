<?php

namespace App\Controller;

use App\Entity\Vote;
use App\Repository\ChoiceRepository;
use App\Repository\PollRepository;
use App\Repository\VoteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class PollController extends AbstractController
{
    #[Route('/poll/{page}', name: 'app_poll')]
    public function index(PollRepository $repo, int $page): Response
    {
        if(!$this->getUser())
        {
            return $this->redirectToRoute("app_home");
        }
        
        $polls = $repo->paginatePolls($page, $_ENV["LIMIT_PAGES"]);
        $nbPages = $repo->getNbPages($_ENV["LIMIT_PAGES"]);
        
        return $this->render('poll/index.html.twig',
        [
            'polls' => $polls,
            'nbPages' => $nbPages,
        ]);
    }

    #[Route("/poll/vote/{pollID}/{choiceID}", name: "vote")]
    public function vote(EntityManagerInterface $em, PollRepository $repo, ChoiceRepository $choiceRepo, VoteRepository $voteRepo, int $pollID, int $choiceID): Response
    {
        if(!$this->getUser())
        {
            return $this->redirectToRoute("app_home");
        }
        
        $poll = $repo->find($pollID);
        $choice = $choiceRepo->find($choiceID);

        $vote = $voteRepo->findByUserPoll($this->getUser(), $poll);

        if(!$vote)
        {
            $vote = new Vote();

            $vote->setUser($this->getUser())
                ->setPoll($poll)
                ->setChoice($choice)
                ->setVotedAt(new \DateTime());
            
            $em->persist($vote);
            $em->flush();
        }

        else
        {
            $this->addFlash("error", "Vous avez déjà voté pour ce sondage");
        }

        return $this->redirectToRoute("app_poll");
    }

    #[Route("/poll/delete/{id}", name: "delete_poll")]
    public function delete(EntityManagerInterface $em, PollRepository $repo, int $id): Response
    {
        if(!$this->getUser())
        {
            return $this->redirectToRoute("app_home");
        }

        if(!in_array("ROLE_ADMIN", $this->getUser()->getRoles()))
        {
            $this->addFlash("error", "Cette action est réservée aux admins");
            return $this->redirectToRoute("app_poll");
        }

        $poll = $repo->find($id);

        $em->remove($poll);
        $em->flush();

        $this->addFlash("success", "Ce sondage a bien été supprimé");

        return $this->redirectToRoute("app_poll");
    }
}
