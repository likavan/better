<?php

declare(strict_types=1);

namespace App\Services;

use App\Entity\Improve;
use DateInterval;
use DatePeriod;
use DateTime;
use DateTimeImmutable;
use Doctrine\Common\Collections\Collection;

class ImproveService
{

    public function totalImproveDays(Improve $improve): array
    {
        $improveDays = $improve->isOnlyWorkingDays() ?  [1, 2, 3, 4, 5] : [1, 2, 3, 4, 5, 6, 7];
        $from = new DateTime($improve->getCreatedAt()->format('Y-m-d'));
        $to = new DateTime(date('Y-m-d', time()));
        $to->modify('+1 days');
        $interval = new DateInterval('P1D');
        $periods = new DatePeriod($from, $interval, $to);
        $days = [];
        foreach ($periods as $period) {
            if (!in_array($period->format('N'), $improveDays)) continue;
            $days[] = $period->format('Y-m-d');
        }
        return $days;
    }

    public function calculateBadDaysCount(Collection $getFails): int
    {
        $result = [];
        foreach ($getFails as $fail) {
            $failDate = $fail->getCreatedAt()->format('Y-m-d');
            $result[$failDate] = [$failDate];
        }
        return count($result);
    }

    public function maxDaysInOneLine(array $improveDays, Collection $fails): int
    {
        $failsDates = [];
        foreach ($fails as $fail) {
            $failsDates[] = $fail->getCreatedAt()->format('Y-m-d');
        }
        $count = 0;
        $maxCount = 0;
        array_pop($improveDays);
        foreach ($improveDays as $improveDay) {
            dump($improveDay);
            if (!in_array($improveDay, $failsDates, true)) {
                $count++;
                dump($count);
            } else {
                if ($count > $maxCount) {
                    $maxCount = $count;
                }
                $count = 0;
            }
        }
        if ($count > $maxCount) {
            $maxCount = $count;
        }

        return $maxCount;
    }

    public function actualDaysInOneLine(array $improveDays, Collection $fails): int
    {
        $count = 1;
        if ($fails->isEmpty()) {
            return count($improveDays);
        }
        $lastFailDate = $fails->last()->getCreatedAt()->format('Y-m-d');
        array_pop($improveDays);
        foreach ($improveDays as $improveDay) {
            if ($improveDay === $lastFailDate) {
                break;
            }
            else {
                $count++;
            }
        }

        return max(count($improveDays) - $count, 0);
    }

    private function daysFromTimestamp(int $timestamp): float
    {
        return floor($timestamp / (60 * 60 * 24));
    }

}