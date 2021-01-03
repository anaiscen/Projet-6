<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\NewPasswordType;
use App\Form\PasswordForgotType;
use App\Form\RegistrationFormType;
use App\Repository\UserRepository;
use App\Security\EmailVerifier;
use Swift_Message;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;

class RegistrationController extends AbstractController
{
    private $emailVerifier;

    public function __construct(EmailVerifier $emailVerifier)
    {
        $this->emailVerifier = $emailVerifier;
    }

    /**
     * @Route("/register", name="app_register")
     * @param Request $request
     * @param UserPasswordEncoderInterface $passwordEncoder
     * @param $mailer
     * @return Response
     * @throws \Exception
     */
    public function register(Request $request, UserPasswordEncoderInterface $passwordEncoder, MailerInterface $mailer): Response
    {
        $user = new User();
        $user->setIsVerified(false);
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // encode the plain password
            $user->setPassword(
                $passwordEncoder->encodePassword(
                    $user,
                    $form->get('plainPassword')->getData()
                )
            );
            $user->setIsVerified(false)
                ->setToken(md5(random_bytes(10)));


            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($user);
            $entityManager->flush();

            $email = new TemplatedEmail();
            $email->subject('SnowTricks user account validation')
                ->to($user->getEmail())
                ->from('eee45ee559-c5b5f5@inbox.mailtrap.io')
                ->html(
                    $this->renderView('registration/confirmation_email.html.twig', [
                        'user' => $user
                    ]),
                    'text/html'
                );
            $mailer->send($email);
            return $this->redirectToRoute('trick');
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }

    /**
     * @Route("/verify/{username}/{token}", name="app_verify")
     * @param $username
     * @param $token
     * @param UserRepository $userRepo
     * @return Response
     */
    public function verifyUserEmail(string $username, string $token, UserRepository $userRepo): Response
    {
        $user = $userRepo->findOneByUserName($username);

        if($token != null && $token === $user->getToken()) {
            $user->setIsVerified(true);
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($user);
            $entityManager->flush();
            $this->addFlash('success', 'Your email address has been verified.');
            return $this->redirectToRoute('app_login');
        }
        else
        {
            $this->addFlash('verify_email_error','Erreur lors de la validation du compte');
            return $this->redirectToRoute('app_register');
        }
    }

    /**
     * @Route("/passwordforgot", name="app_password_forgot")
     * @param Request $request
     * @param UserRepository $userRepo
     * @param MailerInterface $mailer
     * @return Response
     */
    public function passwordForgot(Request $request,UserRepository $userRepo, MailerInterface $mailer): Response
    {

        $form = $this->createForm(PasswordForgotType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user = $userRepo->findOneByUserName($form->get('username')->getData());

            $user->setToken(md5(random_bytes(10)));

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($user);
            $entityManager->flush();

            $email = new TemplatedEmail();
            $email->subject('SnowTricks - Reset password')
                ->to($user->getEmail())
                ->from('eee45ee559-c5b5f5@inbox.mailtrap.io')
                ->html(
                    $this->renderView('registration/password_forgot_email.html.twig', [
                        'user' => $user
                    ]),
                    'text/html'
                );
            $mailer->send($email);
            return $this->redirectToRoute('trick');
        }

        return $this->render('registration/password_forgot.html.twig', [
            'passwordForgotForm' => $form->createView(),
        ]);
    }

    /**
     * @Route("/newpassword/{username}/{token}", name="app_new_password")
     * @param Request $request
     * @param UserPasswordEncoderInterface $passwordEncoder
     * @param string $username
     * @param string $token
     * @param UserRepository $userRepo
     * @return Response
     */
    public function newPassword(Request $request, UserPasswordEncoderInterface $passwordEncoder,string $username, string $token, UserRepository $userRepo): Response
    {
        $user = $userRepo->findOneByUserName($username);
        $form = $this->createForm(NewPasswordType::class,$user);
        $form->handleRequest($request);

        // Check token validity
        if(!$user || $user->getToken() != $token) {
            $this->addFlash('verify_email_error','The link is not valid');
            return $this->redirectToRoute('app_password_forgot');
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $user->setPassword(
                $passwordEncoder->encodePassword(
                    $user,
                    $form->get('plainPassword')->getData()
                )
            );
            $entityManager->persist($user);
            $entityManager->flush();

            return $this->redirectToRoute('app_login');
        }
        else {
            return $this->render('registration/new_password.html.twig', [
                'newPasswordForm' => $form->createView(), ['user' => $user]
            ]);
        }
    }
}
