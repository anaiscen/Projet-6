<?php

namespace App\Controller;

use App\Entity\Image;
use App\Form\AddImageType;
use App\Form\ImageType;
use App\Form\TrickType;
use App\Entity\Trick;
use DateTime;
use Doctrine\ORM\Mapping\Id;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class TrickController extends AbstractController
{
    /**
     * @Route("/", name="trick")
     * @return Response
     */
    public function index():Response
    {
        $tricks = $this->getDoctrine()->getRepository(Trick::class)->findBy(
            ['isPublished' => true],
            ['publicationDate' => 'desc']
        );
        return $this->render('trick/index.html.twig', ['tricks' => $tricks]);
    }

    /**
     * @Route("/add", name="trick_add")
     * @param Request $request
     * @return Response
     */
    public function add(Request $request): Response
    {
        $trick = new Trick();
        $form = $this->createForm(TrickType::class, $trick);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $trick->setLastUpdateDate(new DateTime('NOW'));

            if ($trick->getPicture() !== null) {
                $file = $form->get('picture')->getData();
                $fileName =  uniqid(). '.' .$file->guessExtension();

                try {
                    $file->move(
                        $this->getParameter('images_directory'), // Le dossier dans le quel le fichier va etre charger
                        $fileName
                    );
                } catch (FileException $e) {
                    return new Response($e->getMessage());
                }

                $trick->setPicture($fileName);
            }

            if ($trick->getIsPublished()) {
                $trick->setPublicationDate(new DateTime('NOW'));
            }

            $em = $this->getDoctrine()->getManager();
            $em->persist($trick);
            $em->flush();

            $this->addFlash('info','Le trick a bien été ajouté');
            return $this->redirectToRoute('trick');
        }

        return $this->render('trick/add.html.twig', [
            'form' => $form->createView(),
            'trick' => new Trick()
        ]);
    }

    /**
     * @Route("/show/{id}", name="trick_show")
     * @param $trick
     * @return Response
     */
    public function show(Trick $trick):Response
    {
        return $this->render('trick/show.html.twig', ['trick' => $trick ]);
    }

    /**
     * @Route("/edit/{id}", name="trick_edit")
     * @param Trick $trick
     * @param Request $request
     * @return Response
     */
    public function edit(Trick $trick, Request $request):Response
    {
        $oldPicture = $trick->getPicture();

        $form = $this->createForm(TrickType::class, $trick);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $trick->setLastUpdateDate(new \DateTime());

            if ($trick->getIsPublished()) {
                $trick->setPublicationDate(new \DateTime());
            }

            if ($trick->getPicture() !== null && $trick->getPicture() !== $oldPicture) {
                $file = $form->get('picture')->getData();
                $fileName = uniqid(). '.' .$file->guessExtension();
                try {
                    $file->move(
                        $this->getParameter('images_directory'),
                        $fileName
                    );
                } catch (FileException $e) {
                    return new Response($e->getMessage());
                }

                $trick->setPicture($fileName);
            } else {
                $trick->setPicture($oldPicture);
            }

            $em = $this->getDoctrine()->getManager();
            $em->persist($trick);
            $em->flush();
            $this->addFlash('info','Le trick a été bien modifié');
            return $this->redirectToRoute('trick');
        }

        $newPicture = new Image();
        $formNewPicture = $this->createForm(AddImageType::class, $newPicture, [
            'action' => $this->generateUrl('image_add')
        ]);
        $formNewPicture->get('trick_id')->setData($trick->getId());
        return $this->render('trick/edit.html.twig', [
            'trick' => $trick,
            'form' => $form->createView(),
            'imageForm'=> $formNewPicture->createView(),
        ]);
    }

    /**
     * @Route("/remove/{id}", name="trick_remove")
     * @param Trick $trick
     * @return Response
     */
    public function remove(Trick $trick):Response
    {
        $em = $this->getDoctrine()->getManager();
        $em->remove($trick);
        $em->flush();

        $this->addFlash('info','Le trick a été bien supprimé');
        return $this->redirectToRoute('trick');
    }
}