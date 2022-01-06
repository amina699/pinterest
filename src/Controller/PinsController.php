<?php

namespace App\Controller;

use App\Entity\Pin;
use App\Form\PinType;
use App\Repository\PinRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;

use Symfony\Component\HttpFoundation\Request;








class PinsController extends AbstractController
{
    /**
     * @Route("/", name="app_home", methods="GET")
     */
    public function index(PinRepository $pinRepository, CacheInterface $cache)
    {

         if($cache->getItem('text_cache')->get() != []){        // ici on va tester s'il y a un champ dans le cache ,s'il n'existe pas la methode getItem va le créer
            $pins = $cache->getItem('text_cache')->get();        //permet de recuperer les données a partir de la cache
            return $this->render('pins/index.html.twig', compact('pins'));      //    


         }

else
{
    $pins = $pinRepository->findALL();       //recupérer les données a partir de base d données

    $texte = $cache->getItem('text_cache');      //nous utilisons la méthode getItem ici pour récupérer l'élément de cache avec la clé "text_cache".
    
    $texte->expiresAfter(10);

    $texte->set($pins);   //un mise un jour sur le champ dans le cache //nous utilisons la méthode set de l'objet $texte pour définir la valeur du cache.
    
    $cache->save($texte);  //pour le sauvegarde des données dans la cache  //nous enregistrons l'élément de cache $texte dans le pool de cache $cache à l'aide de la méthode save.
 
   return $this->render('pins/index.html.twig', compact('pins'));       //

}


    }
/**
     * @Route("/pins/create", name="app_pins_create", methods={"GET", "POST"})
     */
    public function create(Request $request, EntityManagerInterface $em , SluggerInterface $slugger ): Response
    {

        $pin = new Pin;

        $form = $this->createForm(PinType::class, $pin);

        $form->handleRequest($request);
        
        if($form->isSubmitted() && $form->isValid()){

            $brochureFile = $form->get('image')->getData();

            if ($brochureFile) {
                $originalFilename = pathinfo($brochureFile->getClientOriginalName(), PATHINFO_FILENAME);
                // this is needed to safely include the file name as part of the URL
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$brochureFile->guessExtension();

                // Move the file to the directory where brochures are stored
                try {
                    $brochureFile->move(
                        $this->getParameter('brochures_directory'),
                        $newFilename
                    );
                } catch (FileException $e) {
                    // ... handle exception if something happens during file upload
                }

            
                $pin->setImage($newFilename);
                $pin->setTitle($form->get('title')->getData());
                $pin->setDescription($form->get('description')->getData());
            }
            $em->persist($pin);
            $em->flush();

            $this->addFlash('success', 'Pin successfully created!');

            return $this->redirectToRoute('app_home');

        }
        return $this->render('pins/create.html.twig',[

            'form' => $form->createView()
        ]);
    }
    /**
     * @Route("/pins/{id}", name="app_pins_show", methods="GET")
     */
    

    public function show(Pin $pin): Response

    {

        return $this->render('pins/show.html.twig',[ 'pin'=>$pin]);
    }
     /**
     * @Route("/pins/{id}/edit", name="app_pins_edit", methods={"GET", "POST"})
     */
    public function edit(Request $request, Pin $pin, EntityManagerInterface $em): Response
    {
       
        $form = $this->createForm(PinType::class, $pin, [
            'method' => 'POST'
        ]);


        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid()){

            
            $em->persist($pin);
            $em->flush();

            $this->addFlash('success', 'Pin successfully updated!');

            return $this->redirectToRoute('app_home');

        }

        return $this->render('pins/edit.html.twig', [
            'pin' => $pin,
            'form' => $form->createView()
        ]);

    }

 /**
     * @Route("/pins/{id}/delete", name="app_pins_delete", methods={"POST"})
     */
    public function delete(Request $request, Pin $pin, EntityManagerInterface $em): Response   {

        if ($this->isCsrfTokenValid('pin_deletion_' . $pin->getId(),  $request->request->get('csrf_token'))){

        $em->remove($pin);
        $em->flush();

        $this->addFlash('success', 'Pin successfully Deleted!');

        }
        return $this->redirectToRoute('app_home');

    }
    public function fonctionLongue(): int 
    {
        sleep(6);
        return 10;
        
    }


}