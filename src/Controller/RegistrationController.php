<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserSearchType;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class RegistrationController extends AbstractController
{

    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    #[Route('/', name: 'index')]
    public function index(): Response
    {
        return $this->render('index.html.twig');
    }

    #[Route('/home', name: 'home')]
    public function home(Request $request, LoggerInterface $logger): Response
    {
        $form = $this->createForm(UserSearchType::class,null,['attr' => ['id' => 'form']]);
        $form->handleRequest($request);
        $users = [];
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $users = $this->entityManager->getRepository(User::class)->searchUsers($data['username']);            
            $logger->info("Form submitted:", [// Log the search results
                'form' => $data,
                'users' => $users, // Log all users found
            ]);
            $userArray = [];
        foreach ($users as $user) {
            $userArray[] = [
                'id' => $user->getId(),          // Get user ID
                'username' => $user->getUsername() // Get username
            ];
            $logger->info('User found: ' . $user->getUsername() . "data:" . $data['username'],['Data'=>$userArray]);
        }

        return $this->redirectToRoute('home', ['users' => $userArray]);
            // return new JsonResponse(['users'=>$users]);
        }
        
        return $this->render('game/index.html.twig', ['game' => null, 'rank' => null, 'error' => null, 'users' => $users, 'form' => $form->createView()]);
    }

    #[Route('/signup', name: 'app_register')]
    public function register(Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordEncoder, AuthenticationUtils $auth): Response
    {
        $error = $auth->getLastAuthenticationError();
        $user = new User();
        $form = $this->createFormBuilder($user)
            ->add('username', TextType::class)
            ->add('password', PasswordType::class)
            ->add('save', SubmitType::class, ['label' => 'Sign up'])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $plainTextPassword = $user->getPassword();

            $hashedPassword = $passwordEncoder->hashPassword($user, $plainTextPassword);
            $user->setPassword($hashedPassword);
            $entityManager->persist($user);
            $entityManager->flush();

            return $this->redirectToRoute('login');
        }

        return $this->render('registration/register.html.twig', [
            'form' => $form->createView(),
            'error' => $error
        ]);
    }
}
