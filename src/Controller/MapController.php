<?php

namespace App\Controller;

use App\Repository\TileRepository;
use App\Services\MapManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Tile;
use App\Repository\BoatRepository;

class MapController extends AbstractController
{
    /**
     * @Route("/map", name="map")
     */
    public function displayMap(BoatRepository $boatRepository, MapManager $mapManager,
                               TileRepository $tileRepository, SessionInterface $session) :Response
    {

        $em = $this->getDoctrine()->getManager();
        $tiles = $em->getRepository(Tile::class)->findAll();

        /*create session for randIsland and update database tile->hasTreasure*/
        if (!$session->has('randomIsland')) {
            $randomIsland = $mapManager->getRandomIsland($tileRepository);
            $session->set('randomIsland', [
                $randomIsland->getCoordX(),
                $randomIsland->getCoordY()
            ]);

            $randomIsland->setHasTreasure(true);
            $em->persist($randomIsland);
            $em->flush();

        }

        /*check what type of boat is on*/
        $tiles = $em->getRepository(Tile::class)->findAll();
        $tileType = null;
        $boat = $boatRepository->findOneBy([], ['id' => 'ASC']  );
        foreach ($tiles as $tile) {
            $map[$tile->getCoordX()][$tile->getCoordY()] = $tile;
            if ($tile->getCoordX() == $boat->getCoordX() && $tile->getCoordY() == $boat->getCoordY()) {
                $tileType = $tile->getType();

            }
        }
        /*check if the boat is on the treasure*/
        $findTreasure = $mapManager->checkTreasure($boatRepository, $tileRepository);

        return $this->render('map/index.html.twig', [
            'map'  => $map ?? [],
            'boat' => $boat,
            'tileType' => $tileType,
            'findTreasure' => $findTreasure
        ]);
    }

    /**
     * @Route("/start", name="start")
     */
     public function start(TileRepository $tileRepository,
                           BoatRepository $boatRepository,
                          SessionInterface $session): Response
    {
        $em = $this->getDoctrine()->getManager();
        $boat = $boatRepository->findOneBy([]);
        $boat->setCoordX(0);
        $boat->setCoordY(0);
        $em->persist($boat);
        $em->flush();
        $tiles= $tileRepository->findBy(['hasTreasure' => true]);
        foreach ($tiles as $tile) {
            $tile->setHasTreasure(false);
            $em->persist($tile);
            $em->flush();
        }

        $session->remove('randomIsland');

        return $this->redirectToRoute('map');

    }

}
