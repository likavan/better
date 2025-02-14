<?php

namespace App\Controller;

use App\Entity\Fail;
use App\Entity\Improve;
use App\Entity\ImproveGroup;
use App\Form\FailType;
use App\Form\ImproveGroupType;
use App\Form\ImproveType;
use App\Repository\FailRepository;
use App\Repository\ImproveGroupRepository;
use App\Repository\ImproveRepository;
use App\Services\ImproveService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class AppController extends AbstractController
{
    #[Route('/', name: 'index')]
    public function index(
        ImproveGroupRepository $improveGroupRepository,
        ImproveService         $improveService
    ): Response
    {
        $improveGroups = $improveGroupRepository->findAll();
        $resultImproveGroups = [];


        foreach ($improveGroups as $improveGroup) {
            $totalPercentageImprove = 0;
            $resultImproveGroups[$improveGroup->getId()]['title'] = $improveGroup->getTitle();
            $resultImproveGroups[$improveGroup->getId()]['id'] = $improveGroup->getId();
            $resultImproveGroups[$improveGroup->getId()]['improves'] = [];
            $improves = $improveGroup->getImproves();
            foreach ($improves as $improve) {
                $improveDays = $improveService->totalImproveDays($improve);
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


                $resultImproveGroups[$improveGroup->getId()]['improves'][] = $actualImprove;
            }

            $resultImproveGroups[$improveGroup->getId()]['totalPercentageImprove'] = count($improves) > 0 ? round($totalPercentageImprove / count($improves), 2) : 0;
        }


        return $this->render('app/index.html.twig', [
            'improveGroups' => $resultImproveGroups,
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

    #[Route('/upsert-improve-group/{improveGroupId}', name: 'upsert_improve_group', defaults: ['improveGroupId' => null])]
    public function upsertImproveGroup(
        Request                $request,
        EntityManagerInterface $entityManager,
        ImproveGroupRepository $improveGroupRepository,
        ?int                   $improveGroupId
    ): Response
    {
        if ($improveGroupId) {
            $improveGroup = $improveGroupRepository->find($improveGroupId);
        } else {
            $improveGroup = new ImproveGroup();
        }

        $form = $this->createForm(ImproveGroupType::class, $improveGroup);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $improveGroup = $form->getData();

            $entityManager->persist($improveGroup);
            $entityManager->flush();
            return $this->redirectToRoute('index');
        }

        return $this->render('app/upsert_improve_group.html.twig', [
            'form' => $form
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
