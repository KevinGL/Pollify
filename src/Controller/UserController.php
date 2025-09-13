<?php

namespace App\Controller;

use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class UserController extends AbstractController
{
    #[Route('/users/{page}', name: 'app_user')]
    public function index(UserRepository $repo, int $page = 1): Response
    {
        if(!$this->getUser())
        {
            return $this->redirectToRoute("app_home");
        }

        if(!in_array("ROLE_ADMIN", $this->getUser()->getRoles()))
        {
            $this->addFlash("error", "Accès réservé aux administrateurs");
            return $this->redirectToRoute("app_user");
        }

        $nbPages = 1;
        $users = $repo->paginateUsers($page, $_ENV["LIMIT_PAGES"], $nbPages);
        
        return $this->render('user/index.html.twig',
        [
            'users' => $users,
            'nbPages' => $nbPages
        ]);
    }

    #[Route('/users/delete/{id}', name: 'delete_user')]
    public function delete(UserRepository $repo, EntityManagerInterface $em, int $id): Response
    {
        if(!$this->getUser())
        {
            return $this->redirectToRoute("app_home");
        }

        if(!in_array("ROLE_ADMIN", $this->getUser()->getRoles()))
        {
            $this->addFlash("error", "Accès réservé aux administrateurs");
            return $this->redirectToRoute("app_user");
        }

        $user = $repo->find($id);
        $em->remove($user);
        $em->flush();
        
        return $this->redirectToRoute("app_user");
    }

    #[Route('/users/set_admin/{id}', name: 'set_admin')]
    public function setAdmin(UserRepository $repo, EntityManagerInterface $em, int $id): Response
    {
        if(!$this->getUser())
        {
            return $this->redirectToRoute("app_home");
        }

        if(!in_array("ROLE_ADMIN", $this->getUser()->getRoles()))
        {
            $this->addFlash("error", "Accès réservé aux administrateurs");
            return $this->redirectToRoute("app_user");
        }

        $user = $repo->find($id);
        $user->setRoles(["ROLE_ADMIN"]);

        $em->persist($user);
        $em->flush();
        
        return $this->redirectToRoute("app_user");
    }

    #[Route('/users/unset_admin/{id}', name: 'unset_admin')]
    public function unsetAdmin(UserRepository $repo, EntityManagerInterface $em, int $id): Response
    {
        if(!$this->getUser())
        {
            return $this->redirectToRoute("app_home");
        }

        if(!in_array("ROLE_ADMIN", $this->getUser()->getRoles()))
        {
            $this->addFlash("error", "Accès réservé aux administrateurs");
            return $this->redirectToRoute("app_user");
        }

        $user = $repo->find($id);
        $user->setRoles(["ROLE_USER"]);

        $em->persist($user);
        $em->flush();
        
        return $this->redirectToRoute("app_user");
    }
}
