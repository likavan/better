<?php

namespace App\Controller;

use App\Entity\Fail;
use App\Entity\Improve;
use App\Form\FailType;
use App\Form\ImproveType;
use App\Repository\FailRepository;
use App\Repository\ImproveRepository;
use App\Services\ImproveService;
use DateInterval;
use DatePeriod;
use DateTime;
use DateTimeImmutable;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class AppController extends AbstractController
{
    #[Route('/', name: 'index')]
    public function index(
        ImproveRepository $improveRepository,
        ImproveService    $improveService
    ): Response
    {
        $improves = $improveRepository->findAll();
        $resultImproves = [];
        $totalPercentageImprove = 0;

        foreach ($improves as $improve) {
            $improveDays = $improveService->totalImproveDays($improve->getCreatedAt());
            $totalImproveDays = count($improveDays);
            $totalBadDays = $improveService->calculateBadDaysCount($improve->getFails());
            $percentageImproveDays = $totalImproveDays ? round((100 - ($totalBadDays / $totalImproveDays) * 100), 2) : 0;
            $totalPercentageImprove += $percentageImproveDays;

            $actualImprove['id'] = $improve->getId();
            $actualImprove['title'] = $improve->getTitle();
            $actualImprove['createdAt'] = $improve->getCreatedAt();
            $actualImprove['fails'] = $improve->getFails();
            $actualImprove['total_bad_days'] = $totalBadDays;
            $actualImprove['total_improve_days'] = $totalImproveDays;
            $actualImprove['percentage_improve_days'] = $percentageImproveDays;
            $actualImprove['max_days_in_one_line'] = $improveService->maxDaysInOneLine($improveDays, $improve->getFails());
            $actualImprove['actual_days_in_one_line'] = $improveService->actualDaysInOneLine($improveDays, $improve->getFails());


            $resultImproves[] = $actualImprove;
        }


        return $this->render('app/index.html.twig', [
            'improves' => $resultImproves,
            'totalPercentageImprove' => round($totalPercentageImprove / count($improves), 2)
        ]);
    }

    #[Route('/upsert-improve/{improve_id}', name: 'upsert_improve', defaults: ['improve_id' => null])]
    public function upsertImprove(
        Request                $request,
        EntityManagerInterface $entityManager,
        ImproveRepository      $improveRepository,
        ?int                   $improve_id
    ): Response
    {
        if ($improve_id) {
            $improve = $improveRepository->find($improve_id);
        } else {
            $improve = new Improve();
        }

        $form = $this->createForm(ImproveType::class, $improve);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $improve = $form->getData();

            $entityManager->persist($improve);
            $entityManager->flush();
            return $this->redirectToRoute('index');
        }

        return $this->render('app/upsert_improve.html.twig', [
            'form' => $form,
            'improve' => $improve
        ]);
    }

    #[Route('/add-fail/{improve_id}', name: 'add_fail')]
    public function addFail(
        EntityManagerInterface $entityManager,
        ImproveRepository      $improveRepository,
        ?int                   $improve_id
    ): Response
    {

        $improve = $improveRepository->find($improve_id);
        $fail = new Fail();
        $fail->setImprove($improve);
        $entityManager->persist($fail);
        $entityManager->flush();

        return $this->redirectToRoute('index');

    }

    #[Route('/edit-fail/{fail_id}', name: 'edit_fail')]
    public function editFail(
        Request                $request,
        EntityManagerInterface $entityManager,
        FailRepository         $failRepository,
        ?int                   $fail_id
    ): Response
    {

        if ($fail_id) {
            $fail = $failRepository->find($fail_id);
        } else {
            return $this->redirectToRoute('index');
        }

        $form = $this->createForm(FailType::class, $fail);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $fail = $form->getData();

            $entityManager->persist($fail);
            $entityManager->flush();

            return $this->redirectToRoute('upsert_improve', ['improve_id' => $fail->getImprove()->getId()]);
        }

        return $this->render('app/edit_fail.html.twig', [
            'form' => $form,
            'improve' => $fail->getImprove()
        ]);

    }

    #[Route('/remove-fail/{improve_id}/{fail_id}', name: 'remove_fail')]
    public function removeFail(
        EntityManagerInterface $entityManager,
        ImproveRepository      $improveRepository,
        FailRepository         $failRepository,
        ?int                   $improve_id,
        ?int                   $fail_id
    ): Response
    {

        $improve = $improveRepository->find($improve_id);
        $fail = $failRepository->find($fail_id);
        $improve->removeFail($fail);
        $entityManager->persist($improve);
        $entityManager->flush();

        return $this->redirectToRoute('upsert_improve', ['improve_id' => $improve_id]);

    }




}
