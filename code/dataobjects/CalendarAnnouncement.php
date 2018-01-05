<?php
class CalendarAnnouncement extends DataObject {

    private static $singular_name = 'Ankündigung';
    private static $plural_name = 'Ankündigungen';

    private static $db = [
        'Title' => 'Varchar(255)',
        'Content' => 'Text',
        'StartDate' => 'Date',
        'EndDate' => 'Date',
        'StartTime' => 'Time',
        'EndTime' => 'Time',
        'AllDay' => 'Boolean',
        'Recurring' => 'Boolean',
        'RecurrenceType' => 'Varchar(50)',
        'RecurrenceNthDay' => 'Int',
        'RecurrenceNthDayOnStart' => 'Boolean',
        'RecurrenceNthWeek' => 'Int',
        'RecurrenceNthWeekDays' => 'Varchar(255)',
        'RecurrenceNthMonth' => 'Int',
        'RecurrenceNthYear' => 'Int',
        'RecurrenceNthMonthDay' => 'Int',
        'RecurrenceNthMonthDayType' => 'Varchar(50)',
    ];

    private static $has_one = [
        'Calendar' => 'CalendarPage',
        'Event' => 'Page',
        'Category' => 'CalendarAnnouncementCategory',
    ];

    private static $has_many = [
        'Registrations' => 'CalendarAnnouncementRegistration',
        'Exceptions' => 'CalendarAnnouncementException',
    ];

    private static $default_sort = 'StartDate ASC, EndDate ASC, StartTime ASC, EndTime ASC';

    private static $summary_fields = [
        'Title' => 'Titel',
        'Content' => 'Inhalt',
        'StartDate' => 'Startdatum',
        'EndDate' => 'Enddatum',
        'StartTime' => 'Beginn',
        'EndTime' => 'End',
        'AllDay.Nice' => 'Ganztags',
        'Category.Title' => 'Kategorie',
        'Calendar.MenuTitle' => 'Kalender',
    ];

    private $startDateTimestamp = -1;
    private $endDateTimestamp = -1;
    private $dailyRpTxt = 'Alle x Tage';
    private $weeklyRpTxt = 'Alle x Wochen';
    private $monthlyRpTxt = 'Alle x Monate';
    private $yearlyRpTxt = 'Alle x Jahre';
    private $dayInSeconds = 86400;

    public function canCreate($member = null) {
        $can = Permission::check(['ADMIN', 'CMS_ACCESS']);
        return $can;
    }

    public function canEdit($member = null) {
        $can = Permission::check(['ADMIN', 'CMS_ACCESS']);
        return $can;
    }

    public function canDelete($member = null) {
        $can = Permission::check(['ADMIN', 'CMS_ACCESS']);
        return $can;
    }

    public function canView($member = null) {
        return true;
    }

    public function validate() {
        $result = parent::validate();

        if(!$this->Title && $this->CalendarID) {
            $result->error('Bitte geben Sie einen Titel an');
        }

        if(!$this->CategoryID) {
            $result->error('Bitte wählen Sie eine Kategorie aus');
        }

        if(!$this->StartDate) {
            $result->error('Bitte geben Sie ein Startdatum an');
        }

        if($this->EndDate && $this->EndDate < $this->StartDate) {
            $result->error('Das Enddatum muss nach dem Startdatum liegen');
        }

        if(!$this->StartTime) {
            if(!$this->AllDay) {
                $result->error('Bitte geben Sie einen Zeitraum an. Beginn/Ende oder Ganztags');
            }
        }

        if($this->EndTime && $this->EndTime < $this->StartTime) {
            $result->error('Das Ende muss nach dem Start liegen');
        }

        if($this->Recurring) {
            $field = false;

            switch($this->RecurrenceType) {
                case 'daily':
                    if($this->RecurrenceNthDay == null || $this->RecurrenceNthDay < 1 || is_float($this->RecurrenceNthDay)) {
                        $field = $this->dailyRpTxt;
                    }
                    break;
                case 'weekly':
                    if($this->RecurrenceNthWeek == null || $this->RecurrenceNthWeek < 1 || is_float($this->RecurrenceNthWeek)) {
                        $field = $this->weeklyRpTxt;
                    }
                    break;
                case 'monthly':
                    if($this->RecurrenceNthMonth == null || $this->RecurrenceNthMonth < 1 || is_float($this->RecurrenceNthMonth)) {
                        $field = $this->monthlyRpTxt;
                    }
                    break;
                case 'yearly':
                    if($this->RecurrenceNthYear == null || $this->RecurrenceNthYear < 1 || is_float($this->RecurrenceNthYear)) {
                        $field = $this->yearlyRpTxt;
                    }
                    break;
                default:
                    break;
            }

            if($field) {
                $result->error('Geben Sie im "' . $field . '" einen ganzahligen Wert größer 0 ein!');
            }

            if($this->RecurrenceType == 'weekly' && !$this->RecurrenceNthWeekDays) {
                $result->error('Bitte wählen Sie mindestens einen Wochentag aus');
            }
        }

        return $result;
    }

    /**
     * @param null|string $weekday
     * @return int
     */
    public function getStartDateTimestamp($weekday = null) {
        if(gettype($weekday) === 'string') {
            $diff = $this->getDayDiff($weekday, date('l', $this->getStartDateTimestamp()));
            return strtotime("$diff day", $this->getStartDateTimestamp());
        }

        if($this->startDateTimestamp == -1) {
            $this->startDateTimestamp = strtotime($this->StartDate);
        }

        return $this->startDateTimestamp;
    }

    public function getEndDateTimestamp() {
        if($this->endDateTimestamp == -1) {
            $this->endDateTimestamp = strtotime($this->EndDate);
        }

        return $this->startDateTimestamp;
    }

    function __clone() {
        $this->endDateTimestamp = -1;
        $this->startDateTimestamp = -1;
        $this->exceptions = [];
        $this->checkedExceptions = false;
    }

    public function onBeforeWrite() {
        parent::onBeforeWrite();

        if(!$this->EndDate) {
            $this->EndDate = $this->StartDate;
        }
    }

    public function onBeforeDelete() {
        parent::onBeforeDelete();

        foreach($this->Exceptions() as $exception) {
            $exception->delete();
        }

        foreach($this->Registrations() as $registration) {
            $registration->delete();
        }
    }

    public function StartDateNice() {
        return $this->dbObject('StartDate')->Format('d.m.Y');
    }

    public function DateRange() {
        $range = $this->StartDateNice();

        if($this->EndDate && $this->StartDate != $this->EndDate) {
            $range .= ' - ' . $this->dbObject('EndDate')->Format('d.m.Y');
        }

        if(!$this->AllDay && $this->StartTime) {
            $range .= ' (' . $this->dbObject('StartTime')->Format('H:i');

            if($this->EndTime) {
                $range .= ' - ' . $this->dbObject('EndTime')->Format('H:i');
            }

            $range .= ' Uhr)';
        }

        return $range;
    }

    /** return:
     *    0 -> no exception
     *    1 -> exception for this date
     *    2 -> exception for complete entry
     * @param string $date
     * @return int
     */
    public function isDateException($date) {
        foreach($this->Exceptions() as $exception) {
            if($exception->Date == $date) {
                if($exception->Reaction == 'complete') {
                    return 2;
                }
                return 1;
            }
        }

        foreach($this->Category()->Exceptions() as $exception) {
            if($exception->Date == $date) {
                if($exception->Reaction == 'complete') {
                    return 2;
                }
                return 1;
            }
        }

        return 0;
    }

    /** cut exceptions and return clones of $this with manipulated StartDate's and EndDate's
     * @param int $startIndex
     * @throws EndlessLoopException
     * @return array
     */
    public function cutExceptions($startIndex = 0) {
        if(gettype($startIndex) !== 'integer') {
            $startIndex = 0;
        }

        $entries = [];

        if($this->StartDate == $this->EndDate) {
            if($this->isDateException($this->StartDate) === 0) {
                return $entries[$startIndex] = $this;
            }
            return [];
        }

        $days = (strtotime($this->EndDate) - strtotime($this->StartDate)) / $this->dayInSeconds;
        $count = 0;
        $startTime = $this->getStartDateTimestamp();
        $startDate = date('Y-m-d', $startTime);
        $clone = clone $this;
        $lastStartTime = 0;
        $lastWasOk = true;

        while($days-- >= 0) {
            if ($count++ > 400) {
                throw new EndlessLoopException('too much days between ' . $this->StartDate . ' and ' . $this->EndDate);
            }

            $exception = $this->isDateException($startDate);

            if($exception === 2) {
                return [];
            }

            if($exception === 1) {
                if($lastStartTime !== 0) {
                    $clone->StartDate = date('Y-m-d', $lastStartTime);
                    $clone->EndDate = date('Y-m-d', $startTime - $this->dayInSeconds);
                    $entries[$startIndex++] = clone $clone;
                }

                $lastWasOk = false;
                $clone->StartDate = date('Y-m-d', $startTime + $this->dayInSeconds);
                $lastStartTime = $startTime + $this->dayInSeconds;
            } else {
                $lastWasOk = true;
            }

            if($lastStartTime == 0) {
                $lastStartTime = $startTime;
            }

            $startTime += $this->dayInSeconds;
            $startDate = date('Y-m-d', $startTime);
        }

        if($lastWasOk) {
            $entries[$startIndex] = $clone;
        }

        return $entries;
    }

    /**generate array of CalenderAnnouncement Objects which are in the time window between $startDate and $endDate
     *
     * @param string $startDate
     * @param string $endDate
     * @throws InvalidArgumentException
     *@throws UnknownRecurrenceTypeException
     * @return array|string
     */
    public function generateRecurrentEntries($startDate, $endDate) {
        if(gettype($startDate) !== 'string' || gettype($endDate) !== 'string') {
            if(gettype($startDate) !== 'string') {
                throw new InvalidArgumentException('argument $startDate is not type of string! type of $startDate: ' . gettype($startDate));
            }
            else {
                throw new InvalidArgumentException('argument $endDate is not type of string! type of $endDate: ' . gettype($endDate));
            }
        }

        if(!$this->Recurring) {
            return [];
        }

        $startTime = strtotime($startDate);
        $endTime = strtotime($endDate);

        if($startTime === false || $endTime === false) {
            if($startTime === false) {
                throw new InvalidArgumentException('argument $startDate is not a date, $startDate: ' . $startDate);
            } else {
                throw new InvalidArgumentException('argument $endDate is not a date, $endDate: ' . $endDate);
            }
        }

        switch ($this->RecurrenceType) {
            case 'daily':
                return $this->generateDailyRecurringEntries($startTime, $endTime);
            case 'weekly':
                return $this->generateWeeklyRecurringEntries($startTime, $endTime);
            case 'monthly':
                return $this->generateMonthlyRecurringEntries($startTime, $endTime);
            case 'yearly':
                return $this->generateYearlyRecurringEntries($startTime, $endTime);
            default:
                throw new UnknownRecurrenceTypeException('unknown type ' . $this->RecurrenceType);
                break;
        }
    }

    /**generate array of CalendarAnnouncement objects
     *
     * @param int $startTimestamp
     * @param int $endTimestamp
     * @return array
     */
    private function generateWeeklyRecurringEntries($startTimestamp, $endTimestamp) {
        $repeatDays = explode(',', $this->RecurrenceNthWeekDays);

        if(gettype($repeatDays) !== 'array') {
            return [];
        }

        $allRelevantRecurringEntries = [];
        $index = 0;

        foreach ($repeatDays as $repeatDay) {
            $repeatDay = $this->recurrenceNthDayToString (intval($repeatDay));
            $startTimestamp = strtotime("$repeatDay this week", $startTimestamp);
            $oneRecurring = $this->dayInSeconds * 7 * $this->RecurrenceNthWeek;
            $numberOfRecurringToFirst = 1;
            $timespan = $startTimestamp - $this->getStartDateTimestamp($repeatDay);
            if ($timespan > 0) {
                //startDate is not in this time window
                $numberOfRecurringToFirst = ceil ($timespan / $oneRecurring);
            }

            $recurringEntryTimestamp = ($numberOfRecurringToFirst * $oneRecurring) + $this->getStartDateTimestamp ($repeatDay);
            $allRelevantRecurringEntries += $this->generateRecurringEntriesByRecurringTime($oneRecurring, $recurringEntryTimestamp, $endTimestamp, $index);
            $index = count($allRelevantRecurringEntries);
        }

        return $allRelevantRecurringEntries;
    }

    /**
     * generate entries in the time window between $startRecurringTime and $endTimestamp with a time gap of $recurringTime
     *
     * @param int $recurringTime
     * @param int $startRecurringTime
     * @param int $endTimestamp
     * @param int $index
     * @throws EndlessLoopException
     * @return array
     */
    private function generateRecurringEntriesByRecurringTime($recurringTime, $startRecurringTime, $endTimestamp, $index = 0) {
        $shitHappens = 0;
        $allRelevantRecurringEntries = [];

        while($endTimestamp >= $startRecurringTime) {
            if($shitHappens++ > 500) {
                throw new EndlessLoopException('not realistic number of recurring entries!');
                break;
            }

            $recurringEntryDate = date('Y-m-d', $startRecurringTime);
            $newRecurringEntry = clone $this;
            $newRecurringEntry->StartDate = $recurringEntryDate;
            $newRecurringEntry->SetEndDateByParent ($this->getStartDateTimestamp(), strtotime($this->EndDate));
            $allRelevantRecurringEntries[$index++] = $newRecurringEntry;
            $startRecurringTime += $recurringTime;
        }

        return $allRelevantRecurringEntries;
    }

    /**generate array of CalendarAnnouncement object
     * @param int $startTimestamp
     * @param int $endTimestamp
     * @return array
     */
    private function generateMonthlyRecurringEntries($startTimestamp, $endTimestamp) {
        //in one view are three months possible
        $lastMonth = date('m', $startTimestamp);
        $currentMonth = date('m', strtotime('+ 15 day', $startTimestamp));
        $nextMonth = date('m', $endTimestamp);

        $monthsToCheck = [];
        $i = 0;
        $temp = [];

        if($lastMonth != $currentMonth) {
            $temp['month'] = $lastMonth;
            $temp['timestamp'] = $startTimestamp;
            $monthsToCheck[$i++] = $temp;
        }

        if($nextMonth != $currentMonth) {
            $temp['month'] = $nextMonth;
            $temp['timestamp'] = $endTimestamp;
            $monthsToCheck[$i++] = $temp;
        }

        $temp['month'] = $currentMonth;
        $temp['timestamp'] = strtotime('+ 15 day', $startTimestamp);
        $monthsToCheck[$i++] = $temp;
        $startMonth = date('m', $this->getStartDateTimestamp());
        $recurringEntries = [];
        $i = 0;

        $query = $this->RecurrenceNthMonthDayType . ' ' . $this->recurrenceNthDayToString () . ' of ';

        foreach($monthsToCheck as $month) {
            if(($month['month'] - $startMonth) % $this->RecurrenceNthMonth != 0) {
                //no recurring in this month
                continue;
            }

            $recurringTime = strtotime($query . date('F o', $month['timestamp']));
            if($recurringTime > $endTimestamp || $recurringTime < $startTimestamp) {
                continue;
            }

            $recurringEntry = clone $this;
            $recurringEntry->StartDate = date('Y-m-d', $recurringTime);
            $recurringEntry->SetEndDateByParent ($this->getStartDateTimestamp (), strtotime($this->EndDate));
            $recurringEntries[$i++] = $recurringEntry;
        }

        return $recurringEntries;
    }

    /**
     * @param int $day
     * @return string
     * @throws InvalidArgumentException
     */
    private function recurrenceNthDayToString($day = -1) {
        if($day == -1) {
            $day = $this->RecurrenceNthMonthDay;
        }

        if($day < 1 || $day > 7) {
            throw new InvalidArgumentException('$day must be between 1 and 7! $day is ' . $day);
        }

        $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];

        return $days[$day - 1];
    }

    /**@inheritdoc
     * @throws InvalidArgumentException
     * @param string $day1
     * @param string $day2
     * @return string
     */
    private function getDayDiff($day1, $day2) {
        if(gettype($day1) !== 'string' || gettype($day2) !== 'string') {
            throw new InvalidArgumentException(
                '$day1 or $day2 or both is/are not strings! typeof $day1: ' . gettype($day1) . ' typeof $day2: ' . gettype($day2)
            );
        }

        $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
        $a = array_search(strtolower($day1), $days);
        $b = array_search(strtolower($day2), $days);
        if($a === false || $b === false) {
            throw new InvalidArgumentException(
                "day1 and day2 must be a weekday (monday - sunday)! day1: $day1 , day2: $day2"
            );
        }

        $diff = $a - $b;
        if($diff > 0) {
            return '+' . $diff;
        } else {
            return strval($diff);
        }
    }

    /**generate array of CalendarAnnouncement object
     * @param int $startTimestamp
     * @param int $endTimestamp
     * @return array
     */
    private function generateYearlyRecurringEntries($startTimestamp, $endTimestamp) {
        //in one view are two years possible (current, current and last, current and next)
        $lastYear = date('Y', $startTimestamp);
        $currentYear = date('Y', strtotime(' + 15 day', $startTimestamp));
        $nextYear = date('Y', $endTimestamp);

        $years = [];
        $i = 0;

        if($lastYear != $currentYear) {
            $years[$i++] = $lastYear;
        } else if($nextYear != $currentYear) {
            $years[$i++] = $nextYear;
        }

        $years[$i] = $currentYear;
        $startYear = date('Y', $this->getStartDateTimestamp());
        $entries = [];
        $i = 0;

        foreach($years as $year) {
            if (($year - $startYear) % $this->RecurrenceNthYear != 0) {
                // not in this year
                continue;
            }

            $dateForThisYear = $year . date('-m-d', $this->getStartDateTimestamp ());
            $timestampForThisYear = strtotime($dateForThisYear);
            if($timestampForThisYear < $startTimestamp || $timestampForThisYear > $endTimestamp) {
                //not in the time window
                continue;
            }

            $recurringEntry = clone $this;
            $recurringEntry->StartDate = $dateForThisYear;
            $recurringEntry->SetEndDateByParent ($this->getStartDateTimestamp (), strtotime($this->EndDate));
            $entries[$i++] = $recurringEntry;
        }

        return $entries;
    }

    /**generate array of CalendarAnnouncement object
     *
     * @param int $startTimestamp
     * @param int $endTimestamp
     * @return array recurringEntries
     */
    private function generateDailyRecurringEntries($startTimestamp, $endTimestamp) {
        $oneRecurring = $this->dayInSeconds * $this->RecurrenceNthDay;

        if(!$this->RecurrenceNthDayOnStart) {
            $oneRecurring += $this->getDuration();
        }

        $numberOfRecurringToFirst = 1;
        $timespan = $startTimestamp - $this->getStartDateTimestamp();
        if($timespan > 0) {
            //startDate is not in this time window
            $numberOfRecurringToFirst = ceil($timespan / $oneRecurring);
        }

        $recurringStartTime = ($numberOfRecurringToFirst * $oneRecurring) + $this->getStartDateTimestamp();

        return $this->generateRecurringEntriesByRecurringTime($oneRecurring, $recurringStartTime, $endTimestamp);
    }

    /**
     * @param int $parentStartTimestamp
     * @param int $parentEndTimestamp
     */
    private function SetEndDateByParent($parentStartTimestamp, $parentEndTimestamp) {
        $timespan = $parentEndTimestamp - $parentStartTimestamp;
        $this->EndDate = date('Y-m-d', $this->getStartDateTimestamp() + $timespan);
    }

    /**get duration between start and end date
     *
     * @param string $returnType
     * @return float|int
     */
    private function getDuration($returnType = 'timestamp') {
        if($returnType == 'timestamp') {
            return strtotime($this->EndDate) - strtotime($this->StartDate);
        } else if($returnType == 'days' || $returnType == 'day') {
            return (strtotime($this->EndDate) - strtotime($this->StartDate)) / $this->dayInSeconds;
        }

        return -1;
    }

    public function getCMSFields() {
        $weekDays = [
            1 => 'Mo', 2 => 'Di', 3 => 'Mi', 4 => 'Do', 5 => 'Fr', 6 => 'Sa', 7 => 'So'
        ];

        $fields = FieldList::create(
            TabSet::create('Root',
                Tab::create('Main', 'Hauptteil',
                    TextField::create('Title', 'Titel'),
                    TextareaField::create('Content', 'Inhalt'),
                    DateField::create('StartDate', 'Startdatum'),
                    DateField::create('EndDate', 'Enddatum'),
                    TimeField::create('StartTime', 'Beginn'),
                    TimeField::create('EndTime', 'Ende'),
                    DropdownField::create('AllDay', 'Ganztags', [1 => 'Ja', 0 => 'Nein'], 0),
                    DropdownField::create('CategoryID', 'Kategorie', CalendarAnnouncementCategory::get()->map('ID', 'Title')->toArray())
                        ->setEmptyString('(Bitte auswählen)'),
                    DropdownField::create('CalendarID', 'Kalender', CalendarPage::get()->map('ID', 'MenuTitle')->toArray())
                        ->setEmptyString('(Bitte auswählen)')
                ),
                Tab::create('Recurrence', 'Wiederholung',
                    DropdownField::create('Recurring', 'Termin wiederholen', [1 => 'Ja', 0 => 'Nein'], 0),
                    $type = DropdownField::create('RecurrenceType', 'Wiederholung', [
                        'daily' => 'täglich',
                        'weekly' => 'wöchentlich',
                        'monthly' => 'monatlich',
                        'yearly' => 'jährlich',
                    ]),
                    $nthDay = DisplayLogicWrapper::create(
                        NumericField::create('RecurrenceNthDay', $this->dailyRpTxt),
                        DropdownField::create('RecurrenceNthDayOnStart', 'Wiederholung ab', [
                            true => 'Start Datum',
                            false => 'End Datum',
                        ])
                    ),
                    $nthWeek = DisplayLogicWrapper::create(
                        NumericField::create('RecurrenceNthWeek', $this->weeklyRpTxt),
                        CheckboxSetField::create('RecurrenceNthWeekDays', 'am', $weekDays)
                    ),
                    $nthMonth = DisplayLogicWrapper::create(
                        NumericField::create('RecurrenceNthMonth', $this->monthlyRpTxt),
                        FieldGroup::create(
                            DropdownField::create('RecurrenceNthMonthDayType', '', [
                                'first' => 'ersten',
                                'second' => 'zweiten',
                                'third' => 'dritten',
                                'fourth' => 'vierten',
                                'last' => 'letzten',
                            ]),
                            DropdownField::create('RecurrenceNthMonthDay', '', $weekDays),
                            LiteralField::create('MonthLiteral', '<div class="literal">des Monats</div>')
                        )->setTitle('Am')
                    ),
                    $nthYear = NumericField::create('RecurrenceNthYear', $this->yearlyRpTxt),
                    $exception = DisplayLogicWrapper::create(
                        GridField::create('Exceptions', 'Ausnahmen', $this->Exceptions(), $exceptionGC = CalendarGridConfig::create())
                    )
                )
            )
        );

        $exceptionGC->set([
            'inline' => [
                'fields' => [
                    'Date' => [
                        'title' => 'Datum',
                        'field' => 'DateField',
                    ],
                    'Reaction' => [
                        'title' => 'Reaktion',
                        'callback' => function($record, $column, $grid) {
                            return DropdownField::create('Reaction', 'Reaktion', [
                                'complete' => 'ganzen Termin ignorieren',
                                'separately' => 'nur betroffenen Tag ignorieren',
                            ]);
                        },
                    ],
                ],
            ],
        ]);

        $type->displayIf('Recurring')->isEqualTo(1);
        $nthDay->displayIf('RecurrenceType')->isEqualTo('daily')->andIf('Recurring')->isEqualTo(1);
        $nthWeek->displayIf('RecurrenceType')->isEqualTo('weekly')->andIf('Recurring')->isEqualTo(1);
        $nthMonth->displayIf('RecurrenceType')->isEqualTo('monthly')->andIf('Recurring')->isEqualTo(1);
        $nthYear->displayIf('RecurrenceType')->isEqualTo('yearly')->andIf('Recurring')->isEqualTo(1);
        $exception->displayIf('Recurring')->isEqualTo(1);

        $this->extend('updateCMSFields', $fields);

        return $fields;
    }
}