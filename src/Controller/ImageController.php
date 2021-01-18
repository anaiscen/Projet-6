<?php

namespace App\Controller;

use App\Entity\Image;
use App\Form\AddImageType;
use App\Form\ImageType;
use App\Repository\TrickRepository;
use Container9e7VW4U\getTrickRepositoryService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;


class ImageController extends AbstractController
{
    /**
     * @Route("/image", name="image")
     */
    public function index(): Response
    {
        return $this->render('image/index.html.twig', [
            'controller_name' => 'ImageController',
        ]);
    }

    /**
     * @Route("/image/remove/{id}", name="image_remove")
     * @param Image $image
     * @return Response
     */
    public function image_remove(Image $image):Response
    {
        $em = $this->getDoctrine()->getManager();
        $em->remove($image);
        $em->flush();
        return $this->redirectToRoute('trick_edit',['id'=>$image->getTrick()->getId()]);

    }

    /**
     * @Route("/image/add/", name="image_add")
     * @param Request $request
     * @param TrickRepository $trickRepo
     * @return Response
     */
    public function image_add(Request $request, TrickRepository $trickRepo):Response
    {
        $image = new Image();
        $form = $this->createForm(AddImageType::class,$image);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if($image->getFileName() !== null)
            {
                $file = $form->get('fileName')->getData();
                $fileName =  uniqid(). '.' .$file->guessExtension();

                try {
                    $file->move(
                        $this->getParameter('images_directory'), // Le dossier dans le quel le fichier va etre charger
                        $fileName
                    );
                } catch (FileException $e) {
                    return new Response($e->getMessage());
                }
                $image->setFileName($fileName);
                $trick = $trickRepo->findOneById($form->get('trick_id')->getData());
                $image->setTrick($trick);
                $em = $this->getDoctrine()->getManager();
                $em->persist($image);
                $em->flush();
            }
        }
        return $this->redirectToRoute('trick_edit', ['id' => $image->getTrick()->getId()]);

    }
}
