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
    #[Route('/poll', name: 'app_poll')]
    public function index(PollRepository $repo): Response
    {
        if(!$this->getUser())
        {
            return $this->redirectToRoute("app_home");
        }
        
        $polls = $repo->findAll();
        
        return $this->render('poll/index.html.twig',
        [
            'polls' => $polls
        ]);
    }

    #[Route("/poll/vote/{pollID}/{choiceID}", name: "vote")]
    public function vote(EntityManagerInterface $em, PollRepository $repo, ChoiceRepository $choiceRepo, VoteRepository $voteRepo, int $pollID, int $choiceID): Response
    {
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
            return $this->render("error.html.twig",
            [
                "error" => 403,
                "message" => "Vous avez déjà voté pour ce sondage"
            ]);
        }

        return $this->redirectToRoute("app_poll");
    }
}
