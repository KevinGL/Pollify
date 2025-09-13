<?php

namespace App\Controller;

use App\Entity\Choice;
use App\Entity\Poll;
use App\Entity\Vote;
use App\Form\PollType;
use App\Repository\ChoiceRepository;
use App\Repository\PollRepository;
use App\Repository\VoteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class PollController extends AbstractController
{
    #[Route('/polls/{page}', name: 'app_poll', requirements: ['page' => '\d+'])]
    public function index(PollRepository $repo, int $page = 1): Response
    {
        if(!$this->getUser())
        {
            return $this->redirectToRoute("app_home");
        }

        $nbPages = 1;
        
        $polls = $repo->paginatePolls($page, $_ENV["LIMIT_PAGES"], $nbPages);
        
        return $this->render('poll/index.html.twig',
        [
            'polls' => $polls,
            'nbPages' => $nbPages,
        ]);
    }

    #[Route("/polls/add", name: "add_poll")]
    public function add(Request $req, EntityManagerInterface $em, ChoiceRepository $choiceRepo): Response
    {
        if(!$this->getUser())
        {
            return $this->redirectToRoute("app_home");
        }
        
        $poll = new Poll();

        $form = $this->createForm(PollType::class, $poll, ['is_admin' => $this->isGranted('ROLE_ADMIN')]);
        $form->handleRequest($req);

        if($form->isSubmitted() && $form->isValid())
        {
            if(!$this->isGranted('ROLE_ADMIN'))
            {
                $poll->setUser($this->getUser());
            }

            $datasChoices = explode("\r\n", $form->get("choices")->getData());

            foreach($datasChoices as $ch)
            {
                $choice = new Choice();

                $choice->setPoll($poll);
                $choice->setTitle($ch);

                if(!$choiceRepo->findByTitle($choice->getTitle()))
                {
                    $em->persist($choice);
                }
            }

            $em->persist($poll);
            $em->flush();

            $this->addFlash("success", "Sondage ajouté");

            return $this->redirectToRoute("app_poll");
        }
        
        return $this->render('poll/add.html.twig', ["form" => $form]);
    }

    private function stringToColor(string $text): string
    {
        $hash = md5($text);
        return sprintf("rgb(%d, %d, %d)",
            hexdec(substr($hash, 0, 2)),
            hexdec(substr($hash, 2, 2)),
            hexdec(substr($hash, 4, 2))
        );
    }


    #[Route("/polls/view/{id}", name: "view_poll")]
    public function view(PollRepository $repo, VoteRepository $voteRepo, int $id): Response
    {
        $poll = $repo->find($id);

        $votes = $voteRepo->getChoicesWithVotes($poll);
        $colors = [];

        foreach($votes as $vote)
        {
            /*$r = rand(0, 255);
            $g = rand(0, 255);
            $b = rand(0, 255);
            
            array_push($colors, "rgb(" . $r . ", " . $g . ", " . $b . ")");*/

            array_push($colors, $this->stringToColor($vote["choice_title"]));
        }

        //dd($colors);

        return $this->render("poll/view.html.twig", ["poll" => $poll, "votes" => $votes, "colors" => $colors]);
    }

    #[Route("/polls/edit/{id}", name: "edit_poll")]
    public function edit(Request $req, EntityManagerInterface $em, PollRepository $repo, ChoiceRepository $choiceRepo, int $id): Response
    {
        if(!$this->getUser())
        {
            return $this->redirectToRoute("app_home");
        }
        
        $poll = $repo->find($id);

        if((new \DateTime())->getTimestamp() > $poll->getBeginAt()->getTimestamp())
        {
            $this->addFlash("error", "Ce sondage est actif, il ne peut plus être modifié");
            return $this->redirectToRoute("app_poll");
        }

        $form = $this->createForm(PollType::class, $poll, ['is_admin' => $this->isGranted('ROLE_ADMIN')]);
        $form->handleRequest($req);

        $choices = implode("\r\n", $poll->getChoices()->map(fn($c) => $c->getTitle())->toArray());
        
        if(!$form->isSubmitted())
        {
            $form->get('choices')->setData($choices);
        }

        if($form->isSubmitted() && $form->isValid())
        {
            if(!$this->isGranted('ROLE_ADMIN'))
            {
                $poll->setUser($this->getUser());
            }

            $datasChoices = explode("\r\n", $form->get("choices")->getData());

            foreach($datasChoices as $ch)
            {
                $choice = new Choice();

                $choice->setPoll($poll);
                $choice->setTitle($ch);

                if(!$choiceRepo->findByTitle($choice->getTitle()))
                {
                    $em->persist($choice);
                }
            }

            foreach($poll->getChoices() as $ch)
            {
                if(!in_array($ch->getTitle(), $datasChoices))
                {
                    $em->remove($ch);
                }
            }

            $em->persist($poll);
            $em->flush();

            $this->addFlash("success", "Sondage mis à jour");

            return $this->redirectToRoute("app_poll");
        }
        
        return $this->render('poll/edit.html.twig', ["form" => $form]);
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
        $poll = $repo->find($id);
        
        if(!$this->getUser())
        {
            return $this->redirectToRoute("app_home");
        }

        if(!in_array("ROLE_ADMIN", $this->getUser()->getRoles()) && $poll->getUser() != $this->getUser())
        {
            $this->addFlash("error", "Cette action est réservée aux admins ou aux propriétaires du sondage");
            return $this->redirectToRoute("app_poll");
        }

        $em->remove($poll);
        $em->flush();

        $this->addFlash("success", "Ce sondage a bien été supprimé");

        return $this->redirectToRoute("app_poll");
    }
}
