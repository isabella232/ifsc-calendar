<?php declare(strict_types=1);

/**
 * @license  http://opensource.org/licenses/mit-license.php MIT
 * @link     https://github.com/nicoSWD
 * @author   Nicolas Oelgart <nico@oelgart.com>
 */
namespace nicoSWD\IfscCalendar\tests\Infrastructure\Schedule;

use DateTimeImmutable;
use DateTimeZone;
use Exception;
use nicoSWD\IfscCalendar\Domain\Round\IFSCRoundNameNormalizer;
use nicoSWD\IfscCalendar\Domain\Schedule\IFSCSchedule;
use nicoSWD\IfscCalendar\Domain\Schedule\IFSCScheduleFactory;
use nicoSWD\IfscCalendar\Domain\Tags\IFSCTagsParser;
use nicoSWD\IfscCalendar\Infrastructure\Schedule\HTMLNormalizer;
use nicoSWD\IfscCalendar\Infrastructure\Schedule\InfoSheetScheduleProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class PDFScheduleProviderTest extends TestCase
{
    private readonly InfoSheetScheduleProvider $scheduleProvider;

    #[Test] public function keqiao_schedule_is_found(): void
    {
        $schedule = $this->parseEventsFromFile('Keqiao.pdf.html', 'Asia/Shanghai');

        $this->assertSame(6, count($schedule));

        $this->assertSame("Women's Boulder Qualification", $schedule[0]->name);
        $this->assertSameDate("2024-04-08T09:00:00+08:00", $schedule[0]->startsAt);
        $this->assertNull($schedule[0]->endsAt);

        $this->assertSame("Men's Boulder Qualification", $schedule[1]->name);
        $this->assertSameDate("2024-04-08T16:00:00+08:00", $schedule[1]->startsAt);
        $this->assertNull($schedule[1]->endsAt);

        $this->assertSame("Women's Boulder Semi-Final", $schedule[2]->name);
        $this->assertSameDate("2024-04-09T12:00:00+08:00", $schedule[2]->startsAt);
        $this->assertNull($schedule[2]->endsAt);

        $this->assertSame("Women's Boulder Final", $schedule[3]->name);
        $this->assertSameDate("2024-04-09T19:00:00+08:00", $schedule[3]->startsAt);
        $this->assertNull($schedule[3]->endsAt);

        $this->assertSame("Men's Boulder Semi-Final", $schedule[4]->name);
        $this->assertSameDate("2024-04-10T12:00:00+08:00", $schedule[4]->startsAt);
        $this->assertNull($schedule[4]->endsAt);

        $this->assertSame("Men's Boulder Final", $schedule[5]->name);
        $this->assertSameDate("2024-04-10T19:00:00+08:00", $schedule[5]->startsAt);
        $this->assertNull($schedule[5]->endsAt);
    }

    #[Test] public function wujiang_schedule_is_found(): void
    {
        $schedule = $this->parseEventsFromFile('Wujiang.pdf.html', 'Asia/Shanghai');

        $this->assertSame(5, count($schedule));

        $this->assertSame("Men's & Women's Lead Qualification", $schedule[0]->name);
        $this->assertSameDate("2024-04-12T09:00:00+08:00", $schedule[0]->startsAt);
        $this->assertSameDate("2024-04-12T15:00:00+08:00", $schedule[0]->endsAt);

        $this->assertSame("Men's & Women's Speed Qualification", $schedule[1]->name);
        $this->assertSameDate("2024-04-12T19:00:00+08:00", $schedule[1]->startsAt);
        $this->assertNull($schedule[1]->endsAt);

        $this->assertSame("Men's & Women's Lead Semi-Final", $schedule[2]->name);
        $this->assertSameDate("2024-04-13T15:00:00+08:00", $schedule[2]->startsAt);
        $this->assertNull($schedule[2]->endsAt);

        $this->assertSame("Men's & Women's Speed Final", $schedule[3]->name);
        $this->assertSameDate("2024-04-13T19:30:00+08:00", $schedule[3]->startsAt);
        $this->assertNull($schedule[3]->endsAt);

        $this->assertSame("Men's & Women's Lead Final", $schedule[4]->name);
        $this->assertSameDate("2024-04-14T19:00:00+08:00", $schedule[4]->startsAt);
        $this->assertNull($schedule[4]->endsAt);
    }

    #[Test] public function salt_lake_city_schedule_is_found(): void
    {
        $schedule = $this->parseEventsFromFile('Salt_Lake_City.pdf.html', 'America/Phoenix');

        $this->assertSame(10, count($schedule));

        $this->assertSame("Men's Boulder Qualification", $schedule[0]->name);
        $this->assertSameDate("2024-05-03T09:00:00-07:00", $schedule[0]->startsAt);
        $this->assertSameDate("2024-05-03T13:30:00-07:00", $schedule[0]->endsAt);

        $this->assertSame("Women's Boulder Qualification", $schedule[1]->name);
        $this->assertSameDate("2024-05-03T15:30:00-07:00", $schedule[1]->startsAt);
        $this->assertSameDate("2024-05-03T21:00:00-07:00", $schedule[1]->endsAt);

        $this->assertSame("Men's Boulder Semi-Final", $schedule[2]->name);
        $this->assertSameDate("2024-05-04T10:00:00-07:00", $schedule[2]->startsAt);
        $this->assertSameDate("2024-05-04T12:30:00-07:00", $schedule[2]->endsAt);

        $this->assertSame("Women's Speed Qualification", $schedule[3]->name);
        $this->assertSameDate("2024-05-04T15:45:00-07:00", $schedule[3]->startsAt);
        $this->assertSameDate("2024-05-04T17:15:00-07:00", $schedule[3]->endsAt);

        $this->assertSame("Men's Boulder Final", $schedule[4]->name);
        $this->assertSameDate("2024-05-04T18:00:00-07:00", $schedule[4]->startsAt);
        $this->assertNull($schedule[4]->endsAt);

        $this->assertSame("Women's Speed Final", $schedule[5]->name);
        $this->assertSameDate("2024-05-04T20:00:00-07:00", $schedule[5]->startsAt);
        $this->assertNull($schedule[5]->endsAt);

        $this->assertSame("Women's Boulder Semi-Final", $schedule[6]->name);
        $this->assertSameDate("2024-05-05T10:00:00-07:00", $schedule[6]->startsAt);
        $this->assertSameDate("2024-05-05T12:30:00-07:00", $schedule[6]->endsAt);

        $this->assertSame("Men's Speed Qualification", $schedule[7]->name);
        $this->assertSameDate("2024-05-05T15:45:00-07:00", $schedule[7]->startsAt);
        $this->assertSameDate("2024-05-05T17:15:00-07:00", $schedule[7]->endsAt);

        $this->assertSame("Women's Boulder Final", $schedule[8]->name);
        $this->assertSameDate("2024-05-05T18:00:00-07:00", $schedule[8]->startsAt);
        $this->assertNull($schedule[8]->endsAt);

        $this->assertSame("Men's Speed Final", $schedule[9]->name);
        $this->assertSameDate("2024-05-05T20:00:00-07:00", $schedule[9]->startsAt);
        $this->assertNull($schedule[9]->endsAt);
    }

    #[Test] public function innsbruck_schedule_is_found(): void
    {
        $schedule = $this->parseEventsFromFile('Innsbruck.pdf.html', 'Europe/Vienna');

        $this->assertSame(10, count($schedule));

        $this->assertSame("Women's Boulder Qualification", $schedule[0]->name);
        $this->assertSameDate("2024-06-26T09:00:00+02:00", $schedule[0]->startsAt);
        $this->assertSameDate("2024-06-26T13:30:00+02:00", $schedule[0]->endsAt);

        $this->assertSame("Men's Boulder Qualification", $schedule[1]->name);
        $this->assertSameDate("2024-06-26T15:30:00+02:00", $schedule[1]->startsAt);
        $this->assertSameDate("2024-06-26T21:00:00+02:00", $schedule[1]->endsAt);

        $this->assertSame("Women's Boulder Semi-Final", $schedule[2]->name);
        $this->assertSameDate("2024-06-27T13:00:00+02:00", $schedule[2]->startsAt);
        $this->assertSameDate("2024-06-27T15:30:00+02:00", $schedule[2]->endsAt);

        $this->assertSame("Women's Boulder Final", $schedule[3]->name);
        $this->assertSameDate("2024-06-27T19:30:00+02:00", $schedule[3]->startsAt);
        $this->assertSameDate("2024-06-27T21:30:00+02:00", $schedule[3]->endsAt);

        $this->assertSame("Men's Boulder Semi-Final", $schedule[4]->name);
        $this->assertSameDate("2024-06-28T13:00:00+02:00", $schedule[4]->startsAt);
        $this->assertSameDate("2024-06-28T15:30:00+02:00", $schedule[4]->endsAt);

        $this->assertSame("Men's Boulder Final", $schedule[5]->name);
        $this->assertSameDate("2024-06-28T19:30:00+02:00", $schedule[5]->startsAt);
        $this->assertSameDate("2024-06-28T21:30:00+02:00", $schedule[5]->endsAt);

        $this->assertSame("Men's & Women's Lead Qualification", $schedule[6]->name);
        $this->assertSameDate("2024-06-29T09:00:00+02:00", $schedule[6]->startsAt);
        $this->assertSameDate("2024-06-29T15:30:00+02:00", $schedule[6]->endsAt);

        $this->assertSame("Men's & Women's Lead Semi-Final", $schedule[7]->name);
        $this->assertSameDate("2024-06-29T19:00:00+02:00", $schedule[7]->startsAt);
        $this->assertSameDate("2024-06-29T21:30:00+02:00", $schedule[7]->endsAt);

        $this->assertSame("Men's Lead Final", $schedule[8]->name);
        $this->assertSameDate("2024-06-30T19:40:00+02:00", $schedule[8]->startsAt);
        $this->assertSameDate("2024-06-30T20:30:00+02:00", $schedule[8]->endsAt);

        $this->assertSame("Women's Lead Final", $schedule[9]->name);
        $this->assertSameDate("2024-06-30T20:40:00+02:00", $schedule[9]->startsAt);
        $this->assertSameDate("2024-06-30T21:30:00+02:00", $schedule[9]->endsAt);
    }

    #[Test] public function koper_schedule_is_found(): void
    {
        $schedule = $this->parseEventsFromFile('Koper.pdf.html', 'Europe/Ljubljana');

        $this->assertSame(4, count($schedule));

        $this->assertSame("Men's & Women's Lead Qualification", $schedule[0]->name);
        $this->assertSameDate("2024-09-06T09:00:00+02:00", $schedule[0]->startsAt);
        $this->assertSameDate("2024-09-06T13:45:00+02:00", $schedule[0]->endsAt);

        $this->assertSame("Men's & Women's Lead Semi-Final", $schedule[1]->name);
        $this->assertSameDate("2024-09-06T20:00:00+02:00", $schedule[1]->startsAt);
        $this->assertSameDate("2024-09-06T22:30:00+02:00", $schedule[1]->endsAt);

        $this->assertSame("Men's Lead Final", $schedule[2]->name);
        $this->assertSameDate("2024-09-07T20:00:00+02:00", $schedule[2]->startsAt);
        $this->assertNull($schedule[2]->endsAt);

        $this->assertSame("Women's Lead Final", $schedule[3]->name);
        $this->assertSameDate("2024-09-07T21:00:00+02:00", $schedule[3]->startsAt);
        $this->assertNull($schedule[3]->endsAt);
    }

    #[Test] public function chamonix_schedule_is_found(): void
    {
        $schedule = $this->parseEventsFromFile('Chamonix.pdf.html', 'Europe/Paris');

        $this->assertSame(6, count($schedule));

        $this->assertSame("Men's & Women's Speed Qualification", $schedule[0]->name);
        $this->assertSameDate("2024-07-12T18:45:00+02:00", $schedule[0]->startsAt);
        $this->assertNull($schedule[0]->endsAt);

        $this->assertSame("Men's & Women's Lead Qualification", $schedule[1]->name);
        $this->assertSameDate("2024-07-13T09:00:00+02:00", $schedule[1]->startsAt);
        $this->assertSameDate("2024-07-13T18:00:00+02:00", $schedule[1]->endsAt);

        $this->assertSame("Men's & Women's Speed Final", $schedule[2]->name);
        $this->assertSameDate("2024-07-13T21:00:00+02:00", $schedule[2]->startsAt);
        $this->assertNull($schedule[2]->endsAt);

        $this->assertSame("Men's & Women's Lead Semi-Final", $schedule[3]->name);
        $this->assertSameDate("2024-07-14T10:00:00+02:00", $schedule[3]->startsAt);
        $this->assertSameDate("2024-07-14T12:30:00+02:00", $schedule[3]->endsAt);

        $this->assertSame("Women's Lead Final", $schedule[4]->name);
        $this->assertSameDate("2024-07-14T20:30:00+02:00", $schedule[4]->startsAt);
        $this->assertNull($schedule[4]->endsAt);

        $this->assertSame("Men's Lead Final", $schedule[5]->name);
        $this->assertSameDate("2024-07-14T21:25:00+02:00", $schedule[5]->startsAt);
        $this->assertNull($schedule[5]->endsAt);
    }

    #[Test] public function prague_schedule_is_found(): void
    {
        $schedule = $this->parseEventsFromFile('Prague.pdf.html', 'Europe/Prague');

        $this->assertSame(6, count($schedule));

        $this->assertSame("Men's Boulder Qualification", $schedule[0]->name);
        $this->assertSameDate("2024-09-20T09:00:00+02:00", $schedule[0]->startsAt);
        $this->assertNull($schedule[0]->endsAt);

        $this->assertSame("Women's Boulder Qualification", $schedule[1]->name);
        $this->assertSameDate("2024-09-20T16:00:00+02:00", $schedule[1]->startsAt);
        $this->assertNull($schedule[1]->endsAt);

        $this->assertSame("Men's Boulder Semi-Final", $schedule[2]->name);
        $this->assertSameDate("2024-09-21T12:00:00+02:00", $schedule[2]->startsAt);
        $this->assertNull($schedule[2]->endsAt);

        $this->assertSame("Men's Boulder Final", $schedule[3]->name);
        $this->assertSameDate("2024-09-21T20:00:00+02:00", $schedule[3]->startsAt);
        $this->assertNull($schedule[3]->endsAt);

        $this->assertSame("Women's Boulder Semi-Final", $schedule[4]->name);
        $this->assertSameDate("2024-09-22T12:00:00+02:00", $schedule[4]->startsAt);
        $this->assertNull($schedule[4]->endsAt);

        $this->assertSame("Women's Boulder Final", $schedule[5]->name);
        $this->assertSameDate("2024-09-22T19:00:00+02:00", $schedule[5]->startsAt);
        $this->assertNull($schedule[5]->endsAt);
    }

    private function assertSameDate(string $expected, DateTimeImmutable $actual): void
    {
        $this->assertSame($expected, $actual->format(\DateTimeInterface::RFC3339));
    }

    /**
     * @return IFSCSchedule[]
     * @throws Exception
     */
    private function parseEventsFromFile(string $filename, string $timeZone): array
    {
        return $this->scheduleProvider->parseSchedule(
            $this->loadTestFile($filename),
            new DateTimeZone($timeZone),
        );
    }

    private function loadTestFile(string $filename): string
    {
        return file_get_contents(__DIR__ . "/../../../resources/infosheets/{$filename}");
    }

    protected function setUp(): void
    {
        $this->scheduleProvider = new InfoSheetScheduleProvider(
            new HTMLNormalizer(),
            new IFSCScheduleFactory(
                new IFSCTagsParser(),
                new IFSCRoundNameNormalizer(),
            ),
        );
    }
}
