<?php declare(strict_types=1);

/**
 * @license  http://opensource.org/licenses/mit-license.php MIT
 * @link     https://github.com/nicoSWD
 * @author   Nicolas Oelgart <nico@oelgart.com>
 */
namespace nicoSWD\IfscCalendar\Infrastructure\Schedule;

use DateTimeImmutable;
use DateTimeZone;
use nicoSWD\IfscCalendar\Domain\Schedule\IFSCSchedule;
use nicoSWD\IfscCalendar\Domain\Schedule\IFSCScheduleFactory;
use nicoSWD\IfscCalendar\Domain\Schedule\IFSCScheduleProvider;

final readonly class InfoSheetScheduleProvider implements IFSCScheduleProvider
{
    private const string REGEX_DAY_SCHEDULE = '~
        # day name
        (Monday|Tuesday|Wednesday|Thursday|Friday|Saturday|Sunday),?\s*
        # day
        (\d{1,2}(st|nd|rd|th|ve)?,?\s+
        # month
        (January|February|March|April|May|June|July|August|September|October|November|December)){1,2}[\n\s]*
        # schedule name
        (.+)
        # stop at next day block
        (?=(Monday|Tuesday|Wednesday|Thursday|Friday|Saturday|Sunday)|$)~nsUx';

    public function __construct(
        private HTMLNormalizer $htmlNormalizer,
        private IFSCScheduleFactory $scheduleFactory,
    ) {
    }

    /** @inheritdoc */
    public function parseSchedule(string $html, DateTimeZone $timeZone): array
    {
        $schedules = [];

        foreach ($this->daySchedules($html) as $daySchedule) {
            foreach ($this->parseDaySchedule($daySchedule, $timeZone) as $schedule) {
                $schedules[] = $schedule;
            }
        }

        return $schedules;
    }

    /** @return string[] */
    private function daySchedules(string $html): array
    {
        $normalize = $this->htmlNormalizer->normalize($html);

        if (preg_match_all(self::REGEX_DAY_SCHEDULE, $normalize, $matches)) {
            return $matches[0];
        }

        return [];
    }

    /** @return IFSCSchedule[] */
    private function parseDaySchedule(string $schedule, DateTimeZone $timeZone): array
    {
        [$dayName, $schedule] = $this->parseDayAndSchedule($schedule);

        $scheduleRegex = '~
            (?<start_time>\d?\d:\d\d|follow(?:ing|ed)\s+by)\n?(?:\s*-\s*
            (?<end_time>\d?\d:\d\d))?\s*\n
            (?<name>[^\r\n]+)\s*\n~xi';

        /** @var IFSCSchedule[] $schedules */
        $schedules = [];

        if (preg_match_all($scheduleRegex, $schedule, $match, flags: PREG_UNMATCHED_AS_NULL)) {
            foreach (array_keys($match['start_time']) as $key) {
                if ($this->followsLastRound($match['start_time'][$key])) {
                    $prevIndex = count($schedules) - 1;

                    if (isset($schedules[$prevIndex])) {
                        $schedule = $this->scheduleFactory->create(
                            name: "{$schedules[$prevIndex]->name} & {$match['name'][$key]}",
                            startsAt: $schedules[$prevIndex]->startsAt,
                            endsAt: $schedules[$prevIndex]->endsAt,
                        );

                        if (!$schedule->isPreRound) {
                            $schedules[$prevIndex] = $schedule;
                        }
                    }
                } else {
                    $startsAt = $this->createStartDate($dayName, $match['start_time'][$key], $timeZone);
                    $endsAt = $this->createEndDate($dayName, $match['end_time'][$key] ?? null, $timeZone);

                    $schedule = $this->scheduleFactory->create(
                        name: $match['name'][$key],
                        startsAt: $startsAt,
                        endsAt: $endsAt,
                    );

                    if (!$schedule->isPreRound) {
                        $schedules[] = $schedule;
                    }
                }
            }
        }

        return $schedules;
    }

    private function createStartDate(string $day, string $time, DateTimeZone $timeZone): DateTimeImmutable
    {
        // Year is missing!!

        $day = preg_replace('~(\d{1,2})(?:st|nd|rd|th|ve)~', '$1', $day);

        return DateTimeImmutable::createFromFormat(
            'l j M Y H:i',
            sprintf(
                '%s 2024 %s',
                trim($day),
                trim($time),
            ),
            $timeZone,
        );
    }

    private function createEndDate(string $dayName, ?string $time, DateTimeZone $timeZone): ?DateTimeImmutable
    {
        if ($time !== null && trim($time) !== '') {
            return $this->createStartDate($dayName, $time, $timeZone);
        }

        return null;
    }

    /** @return string[] */
    private function parseDayAndSchedule(string $schedule): array
    {
        return explode("\n", $this->normalizeTime($schedule), limit: 2);
    }

    private function normalizeTime(string $schedule): string
    {
        return preg_replace('~(\d\d:\d\d)\s*\n(\d\d:\d\d)\s*~', "\$1 - \$2\n", $schedule);
    }

    private function followsLastRound(string $haystack): bool
    {
        return str_contains(strtolower($haystack), 'follow');
    }
}
