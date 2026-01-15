<?php

declare(strict_types=1);

namespace App\Domain\Calendar;

use App\Domain\Activity\ActivitiesEnricher;
use App\Domain\Activity\ActivityIdRepository;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;

final readonly class Calendar
{
    private function __construct(
        private Month $month,
        private ActivityIdRepository $activityIdRepository,
        private ActivitiesEnricher $activitiesEnricher,
    ) {
    }

    public static function create(
        Month $month,
        ActivityIdRepository $activityIdRepository,
        ActivitiesEnricher $activitiesEnricher,
    ): self {
        return new self(
            month: $month,
            activityIdRepository: $activityIdRepository,
            activitiesEnricher: $activitiesEnricher,
        );
    }

    public function getMonth(): Month
    {
        return $this->month;
    }

    public function getDays(): Days
    {
        $previousMonth = $this->month->getPreviousMonth();
        $nextMonth = $this->month->getNextMonth();
        $numberOfDaysInPreviousMonth = $previousMonth->getNumberOfDays();

        $days = Days::empty();
        for ($i = 1; $i < $this->month->getWeekDayOfFirstDay(); ++$i) {
            // Prepend with days of previous month.
            $dayNumber = $numberOfDaysInPreviousMonth - ($this->month->getWeekDayOfFirstDay() - $i - 1);
            $activityIds = $this->activityIdRepository->findByStartDate(SerializableDateTime::createFromFormat(
                format: 'd-n-Y',
                datetime: $dayNumber.'-'.$previousMonth->getMonth().'-'.$previousMonth->getYear(),
            ), null);

            $days->add(Day::create(
                dayNumber: $dayNumber,
                isCurrentMonth: false,
                activities: $this->activitiesEnricher->getEnrichedActivitiesByActivityIds($activityIds)
            ));
        }

        for ($i = 0; $i < $this->month->getNumberOfDays(); ++$i) {
            $dayNumber = $i + 1;

            $activityIds = $this->activityIdRepository->findByStartDate(SerializableDateTime::createFromFormat(
                format: 'd-n-Y',
                datetime: $dayNumber.'-'.$this->month->getMonth().'-'.$this->month->getYear(),
            ), null);

            $days->add(Day::create(
                dayNumber: $dayNumber,
                isCurrentMonth: true,
                activities: $this->activitiesEnricher->getEnrichedActivitiesByActivityIds($activityIds)
            ));
        }

        for ($i = 0; $i < count($days) % 7; ++$i) {
            // Append with days of next month.
            $dayNumber = $i + 1;

            $activityIds = $this->activityIdRepository->findByStartDate(SerializableDateTime::createFromFormat(
                format: 'd-n-Y',
                datetime: $dayNumber.'-'.$nextMonth->getMonth().'-'.$nextMonth->getYear(),
            ), null);

            $days->add(Day::create(
                dayNumber: $dayNumber,
                isCurrentMonth: false,
                activities: $this->activitiesEnricher->getEnrichedActivitiesByActivityIds($activityIds)
            ));
        }

        return $days;
    }
}
