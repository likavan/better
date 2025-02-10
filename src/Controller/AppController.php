<?php

namespace App\Controller;

use App\Entity\Fail;
use App\Entity\Improve;
use App\Form\FailType;
use App\Form\ImproveType;
use App\Repository\FailRepository;
use App\Repository\ImproveRepository;
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
        ImproveRepository $improveRepository
    ): Response
    {
        $improves = $improveRepository->findAll();
        $resultImproves = [];
        $totalPercentageImprove = 0;

        foreach ($improves as $improve) {
            $totalImproveDays = $this->totalImproveDays($improve->getCreatedAt());
            $totalBadDays = $this->calculateBadDaysCount($improve->getFails());
            $percentageImproveDays = $totalImproveDays ? round((100 - ($totalBadDays / $totalImproveDays) * 100), 2) : 0;
            $totalPercentageImprove += $percentageImproveDays;

            $actualImprove['id'] = $improve->getId();
            $actualImprove['title'] = $improve->getTitle();
            $actualImprove['createdAt'] = $improve->getCreatedAt();
            $actualImprove['fails'] = $improve->getFails();
            $actualImprove['total_bad_days'] = $totalBadDays;
            $actualImprove['total_improve_days'] = $totalImproveDays;
            $actualImprove['percentage_improve_days'] = $percentageImproveDays;
            $actualImprove['max_days_in_one_line'] = $this->maxDaysInOneLine($improve->getCreatedAt(), $improve->getFails());
            $actualImprove['actual_days_in_one_line'] = $this->actualDaysInOneLine($improve->getCreatedAt(), $improve->getFails());


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

    private function totalImproveDays(DateTimeImmutable $improveStartDate): int
    {
        $different = time() - $improveStartDate->getTimestamp();
        return floor($different / (60 * 60 * 24));
    }

    private function calculateBadDaysCount(Collection $getFails): int
    {
        $result = [];
        foreach ($getFails as $fail) {
            $failDate = $fail->getCreatedAt()->format('Y-m-d');
            $result[$failDate] = [$failDate];
        }
        return count($result);
    }

    private function maxDaysInOneLine( DateTimeImmutable $improveStartDate, Collection $fails): int
    {
        $result = 0;
        $startDate = $improveStartDate->modify('-1 day');
        foreach ($fails as $fail)
        {
            $failDate = $fail->getCreatedAt();
            $days = ($this->daysFromTimestamp($failDate->getTimestamp())-1) - $this->daysFromTimestamp($startDate->getTimestamp());
            if ($days > $result) {
                $result = $days;
            }
            $startDate = $failDate;
        }

        $days = ($this->daysFromTimestamp(time()) -1) - $this->daysFromTimestamp($startDate->getTimestamp());
        if ($days > $result) {
            $result = $days;
        }
        return $result;
    }

    private function actualDaysInOneLine(DateTimeImmutable $improveStartDate,Collection $fails): int
    {
        $result = 0;
        $startDate = $fails->count() > 0 ? $fails->last()->getCreatedAt() : $improveStartDate->modify('-1 day');

        $days = ($this->daysFromTimestamp(time()) - 1) - $this->daysFromTimestamp($startDate->getTimestamp());
        if ($days > $result) {
            $result = $days;
        }
        return $result ;
    }

    private function daysFromTimestamp(int $timestamp): int
    {
        return floor($timestamp / (60 * 60 * 24));
    }
}
