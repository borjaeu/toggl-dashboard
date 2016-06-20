<?php

namespace Kizilare\TogglBundle\Services;

use Kizilare\TogglBundle\Helper\Api;

class ScheduleLoader
{
    const SCALE = 60;

    /**
     * @var array
     */
    private $projects;

    /**
     * @var Api
     */
    private $toggl;

    /**
     * @var array
     */
    private $slots;

    /**
     * @var array
     */
    private $schedule;

    /**
     * @var array
     */
    private $estimation;

    /**
     * @var array
     */
    private $dailyTotal;

    /**
     * @var int
     */
    private $total;

    /**
     * @param Api   $toggl
     * @param array $projects
     */
    public function __construct(Api $toggl, array $projects)
    {
        $this->toggl = $toggl;
        $this->projects = $projects;
    }

    /**
     * @param int $offset
     */
    public function load($offset)
    {
        $date = time();
        if ($offset) {
            $date = time() - (86400 * 7 * $offset);
        }

        $entries = $this->toggl->getWeekDetails($date);

        $dayStartDate = 0;
        $this->schedule = [];
        $total = 0;
        $this->dailyTotal = [];
        foreach ($entries as $entry) {
            $slotStartDate = strtotime($entry['start']);
            if (empty($entry['stop'])) {
                $slotStopDate = time();
            } else {
                $slotStopDate = strtotime($entry['stop']);
            }
            $weekday = date('w', $slotStartDate);
            if (!isset($this->schedule[$weekday])) {
                $dayStartDate = mktime(
                    7,
                    0,
                    0,
                    date('m', $slotStartDate),
                    date('d', $slotStartDate),
                    date('Y', $slotStartDate)
                );
                $this->schedule[$weekday] = [];
                $this->dailyTotal[$weekday] = 0;
            }

            $slotLength = $this->minutes($slotStopDate) - $this->minutes($slotStartDate);
            $slotOffset = $this->minutes($slotStartDate) - $this->minutes($dayStartDate);
            $slot = [
                'start_time'    => date('H:i', $slotStartDate),
                'stop_time'     => date('H:i', $slotStopDate),
                'day'           => $weekday,
                'description'   => $entry['description'],
                'project'       => $entry['project'],
                'project_label' => strtolower(str_replace(' ', '_', $entry['project'])),
                'project_id'    => $entry['project_id'],
                'offset'        => $slotOffset,
                'counts'        => $this->getProjectValue($entry['project']),
                'length'        => $slotLength,
                'end'           => $slotOffset + $slotLength
            ];
            $this->schedule[$weekday][] = $slot;
            if ($slot['counts']) {
                $total += $slotLength;
                $this->dailyTotal[$weekday] += $slotLength;
            };
        }

        $this->slots = [];
        $date = $dayStartDate;
        for ($i = 0; $i < 12; $i++) {
            $this->slots[] = array(
                'time' => date('H:i', $date),
                'offset' => $this->minutes($date - $dayStartDate),
                'width' => 3600 / self::SCALE
            );
            $date += 3600;
        }

        $dailyMax = 60 * 8;
        $weeklyMax = $dailyMax * 5;

        $this->total = [
            'amount'    => $this->total,
            'weekly'    => number_format(100 * $this->total / $weeklyMax, 3),
            'label'     => $this->toReadableTime($this->total),
            'extra'     => 0
        ];

        $extra = 0;
        $dayTotal = [];
        foreach ($this->dailyTotal as $weekday => & $dayTotal) {
            $this->total['extra'] += $extra;
            $extra = $dayTotal - 480;
            $dayTotal = [
                'amount' => $dayTotal,
                'daily' => number_format(100 * $dayTotal / $dailyMax, 2),
                'weekly' => number_format(100 * $dayTotal / $weeklyMax, 2),
                'extra' => $dayTotal - 480,
                'label' => $this->toReadableTime($dayTotal)
            ];
        }

        $this->estimation = false;
        $now = time();
        if (!$offset) {
            $this->estimation = [
                'day'           => $weekday,
                'now'           => $this->minutes($now - $dayStartDate),
                'now_label'     => date('H:i', $now),
                'expected_out'  => $this->minutes($now - $dayStartDate) - $dayTotal['amount'] + 480,
                'expected_out_label'  => date('H:i', $now + ((480 - $dayTotal['amount']) * 60)),
                'sooner_out'    => $this->minutes($now - $dayStartDate) - $dayTotal['amount'] + 480 - $total['extra'],
                'sooner_out_label'  => date('H:i', $now + ((480 - $dayTotal['amount'] - $total['extra']) * 60))
            ];
        } else {
            $this->total['extra'] += $extra;
        }
        $this->total['extra'] = $this->toReadableTime($this->total['extra']);
    }

    /**
     * Return projects
     *
     * @return array
     */
    public function getProjects()
    {
        return $this->projects;
    }

    /**
     * @return array
     */
    public function getSlots()
    {
        return $this->slots;
    }

    /**
     * @return array
     */
    public function getEstimation()
    {
        return $this->estimation;
    }

    /**
     * @return array
     */
    public function getSchedule()
    {
        return $this->schedule;
    }

    /**
     * @return array
     */
    public function getDailyTotal()
    {
        return $this->dailyTotal;
    }

    /**
     * @return int
     */
    public function getTotal()
    {
        return $this->total;
    }

    private function toReadableTime($minutes)
    {
        $prefix = '';
        if ($minutes < 0) {
            $minutes = -$minutes;
            $prefix = '-';
        }
        $hours = floor($minutes / 60);
        $minutes = $minutes % 60;
        $format = sprintf('%s%02d:%02d', $prefix, $hours, $minutes);
        return $format;
    }

    private function minutes($seconds)
    {
        return (int) ($seconds / 60);
    }

    /**
     * @param string $projectName Name of the project
     * @return array
     */
    private function getProjectValue($projectName)
    {
        if (!isset($this->projects[$projectName])) {
            return false;
        }

        return $this->projects[$projectName];
    }
}
