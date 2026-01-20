<?php

declare(strict_types=1);

namespace App\Domain\Calendar;

use App\Domain\Activity\EnrichedActivities;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;

final readonly class Calendar
{
    private function __construct(
        private Month $month,
        private EnrichedActivities $enrichedActivities,
    ) {
    }

    public static function create(
        Month $month,
        EnrichedActivities $enrichedActivities,
    ): self {
        return new self(
            month: $month,
            enrichedActivities: $enrichedActivities,
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
            $days->add(Day::create(
                dayNumber: $dayNumber,
                isCurrentMonth: false,
                activities: $this->enrichedActivities->findByStartDate(SerializableDateTime::createFromFormat(
                    format: 'd-n-Y',
                    datetime: $dayNumber.'-'.$previousMonth->getMonth().'-'.$previousMonth->getYear(),
                ), null)
            ));
        }

        for ($i = 0; $i < $this->month->getNumberOfDays(); ++$i) {
            $dayNumber = $i + 1;

            $days->add(Day::create(
                dayNumber: $dayNumber,
                isCurrentMonth: true,
                activities: $this->enrichedActivities->findByStartDate(SerializableDateTime::createFromFormat(
                    format: 'd-n-Y',
                    datetime: $dayNumber.'-'.$this->month->getMonth().'-'.$this->month->getYear(),
                ), null)
            ));
        }

        for ($i = 0; $i < count($days) % 7; ++$i) {
            // Append with days of next month.
            $dayNumber = $i + 1;

            $days->add(Day::create(
                dayNumber: $dayNumber,
                isCurrentMonth: false,
                activities: $this->enrichedActivities->findByStartDate(SerializableDateTime::createFromFormat(
                    format: 'd-n-Y',
                    datetime: $dayNumber.'-'.$nextMonth->getMonth().'-'.$nextMonth->getYear(),
                ), null)
            ));
        }

        return $days;
    }
}
