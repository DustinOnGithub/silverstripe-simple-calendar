<?php
class CalendarPage extends Page {

    private static $singular_name = 'Kalender';
    private static $description = 'Ein Kalender kann mehrere Events haben';
    private static $can_be_root = true;
    private static $allowed_children = ['CalendarEventPage'];
    private static $default_child = 'CalendarEventPage';

    private static $db = [
        'CalendarView' => 'Varchar(4)',
        'DefaultView' => 'Varchar(50)',
    ];

    private static $has_many = [
        'Announcements' => 'CalendarAnnouncement',
    ];

    private static $many_many = [
        'CategoriesToDisplay' => 'CalendarAnnouncementCategory',
    ];

    private static $summary_fields = [
        'Title' => 'Titel',
        'Announcements.Count' => 'Ankündigungen',
        'Children.Count' => 'Veranstaltungen',
    ];

    public function getLumberjackTitle() {
        return 'Veranstaltungen';
    }

    public function getCMSFields() {
        $fields = parent::getCMSFields();
        $fields->addFieldsToTab('Root.Main', [
            DropdownField::create('CalendarView', 'Ansicht', ['list' => 'Listenansicht', 'cal' => 'Kalenderansicht'], 'list'),
            $defaultView = DropdownField::create('DefaultView', 'Standard Kalenderansicht', [
                'agendaDay' => 'Tag',
                'agendaWeek' => 'Woche',
                'month' => 'Monat',
            ], 'month'),
            ListboxField::create('CategoriesToDisplay', 'Termine folgender Kategorien hinzufügen', CalendarAnnouncementCategory::get()->map()->toArray())
                ->setMultiple(true)
                ->setDescription('Dadurch werden alle Termine/Ankündigungen der ausgewählten Kategorien in diesem Kalender mit ausgegeben'),
        ], 'Content');
        $fields->insertAfter(Tab::create('Announcements', 'Ankündigungen'), 'Main');
        $fields->addFieldsToTab('Root.Announcements', [
            GridField::create('Announcements', 'Alle Ankündigungen', $this->Announcements(), $gridConf = CalendarGridConfig::create())
                ->addExtraClass('annoucements-grid')
                ->setDescription('<br>Ankündigungen sind "Termine" ohne eigene Seite. z.B. Betriebsurlaub oder Geburtstage')
        ]);

        $fields->insertAfter(Tab::create('Categories', 'Kategorien'), 'Announcements');
        $fields->addFieldsToTab('Root.Categories', [
            GridField::create('Categories', 'Kategorien', CalendarAnnouncementCategory::get(), $calAnnCatConf = CalendarGridConfig::create())
                ->addExtraClass('calendarannoucementscategory-grid')
        ]);

        $calAnnCatConf->set([
            'inline' => [
                'edit',
                'fields' => [
                    'Title' => [
                        'title' => 'Titel',
                        'field' => 'TextField',
                    ],
                    'Color' => [
                        'title' => 'Farbe',
                        'field' => 'ColourPicker'
                    ],
                    'FontColor' => [
                        'title' => 'Schriftfarbe',
                        'field' => 'ColourPicker'
                    ],
                ],
            ],
        ]);

        $gridConf->set([
            'inline' => [
                'edit',
                'fields' => [
                    'Title' => [
                        'title' => 'Titel',
                        'field' => 'TextField',
                    ],
                    'Content' => [
                        'title' => 'Inhalt',
                        'field' => 'TextareaField',
                    ],
                    'StartDate' => [
                        'title' => 'Startdatum',
                        'field' => 'DateField',
                    ],
                    'EndDate' => [
                        'title' => 'Enddatum',
                        'field' => 'DateField',
                    ],
                    'StartTime' => [
                        'title' => 'Beginn',
                        'field' => 'TimeField',
                    ],
                    'EndTime' => [
                        'title' => 'Ende',
                        'field' => 'TimeField',
                    ],
                    'AllDay' => [
                        'title' => 'Ganztags',
                        'field' => 'CheckboxField',
                    ],
                    'CategoryID' => [
                        'title' => 'Kategorie',
                        'callback' => function($record, $column, $grid) {
                            return DropdownField::create($column, 'Kategorie', CalendarAnnouncementCategory::get()->map()->toArray());
                        },
                    ],
                    'Recurring.Nice' => [
                        'title' => 'Wiederholung',
                        'field' => 'ReadonlyField',
                    ],
                ],
            ],
        ]);

        $lumberjackGrid = $fields->dataFieldByName('ChildPages');
        $lumberjackDataCols = $lumberjackGrid->getConfig()->getComponentByType('GridFieldDataColumns');
        $lumberjackSummary = $lumberjackDataCols->getDisplayFields($lumberjackGrid);
        $lumberjackSummary['Location'] = 'Veranstaltungsort';
        $lumberjackSummary['Announcements.Count'] = 'Termine';
        $lumberjackSummary['Announcements.First.StartDateNice'] = 'Erster Termin';
        $lumberjackSummary['Announcements.Last.StartDateNice'] = 'Letzter Termine';
        $lumberjackSummary['Registrations.Count'] = 'Anmeldungen (Gesamt)';
        $lumberjackDataCols->setDisplayFields($lumberjackSummary);

        $defaultView->displayIf('CalendarView')->isEqualTo('cal');

        return $fields;
    }
}

class CalendarPage_Controller extends Page_Controller {

    private static $allowed_actions = [
        'entriesasjson',
        'reloadlistviewentries',
        'reloadcalendarlegend',
        'icsfeed',
    ];

    public function CalendarEntries($allEntries = false, $reverseOrder = false, $entries = false, $noDateFilter = false) {
        $categories = $this->CategoriesToDisplay();

        if(!$entries) {
            $entries = ArrayList::create();
            $announcements = $this->Announcements();
            $events = $this->Children();
            $eventsAnnouncements = ArrayList::create();

            foreach ($events as $event) {
                foreach ($event->Announcements() as $eventAnnouncements) {
                    $eventsAnnouncements->push($eventAnnouncements);
                }
            }

            $entries->merge($eventsAnnouncements);
            $entries->merge($announcements);

            if($categories->first()) {
                $extraEntries = CalendarAnnouncement::get()->filter('CategoryID', $categories->column('ID'));
                $entries->merge($extraEntries);
            }
        }


        if($allEntries === true) {
            // ALLE Einträge
            return $entries;
        }

        if(!$noDateFilter) {
            $entries = $entries->filterByCallback(function ($item) {
                $date = $item->EndDate;
                if (!$date) {
                    $date = $item->StartDate;
                }

                if ($date >= date('Y-m-d')) {
                    return true;
                }
            });
        }

        $filteredEntries = ArrayList::create();

        foreach($entries as $entry) {
            if($entry->EventID) {
                if(!$filteredEntries->find('EventID', $entry->EventID)) {
                    $filteredEntries->add($entry);
                }
            } else {
                $filteredEntries->add($entry);
            }
        }

        // Alle noch nicht abgelaufenen Einträge
        if($reverseOrder) {
            $filteredEntries = $filteredEntries->sort('StartDate ASC');
        }

        return $filteredEntries;
    }

    public function CalendarEntriesForTemplate($entries = false) {
        $entries = $this->CalendarEntries(false, true, $entries, false);
        return $entries;
    }

    public function entriesasjson($startDate = false, $endDate = false, $returnIDs = false) {
        $r = $this->request;
        $v = $r->postVars();

        if($startDate instanceof SS_HTTPRequest) {
            $startDate = $v['start'];
        }

        if(!$endDate) {
            $endDate = $v['end'];
        }

        $allEntries = $this->CalendarEntries(true);
        $allEntries = CalendarAnnouncement::get()->byIDs($allEntries->column('ID'));

        $noRecurringEntriesInThisTimeWindow = $allEntries->filter([
            'StartDate:GreaterThanOrEqual' => $startDate,
            'EndDate:LessThanOrEqual' => $endDate,
        ]);

        $recurringEntries = $allEntries->filter([
            'Recurring' => true,
            'StartDate:LessThanOrEqual' => $endDate,
        ]);

        $recurringEntriesInThisWindow = [];

        function addArrayToArray($a, $b) {
            $key = count($a);

            foreach($b as $item) {
                $a[$key++] = $item;
            }

            return $a;
        }

        foreach($recurringEntries as $recurringEntry) {
            $recurringEntriesInThisWindow = addArrayToArray(
                $recurringEntriesInThisWindow,
                $recurringEntry->generateRecurrentEntries($startDate, $endDate)
            );
        }

        $entriesInThisWindow = addArrayToArray($recurringEntriesInThisWindow, $noRecurringEntriesInThisTimeWindow->toArray());
        $returnEntries = $this->parseEntriesToDataArray($entriesInThisWindow, $returnIDs);

        if($returnIDs === true) {
            return $returnEntries;
        } else {
            return json_encode ($returnEntries);
        }
    }

    /**
     * @param array $entries CalenderAnnouncement objects
     * @param boolean $returnIDs true -> returns id's of $entries objects
     * @throws InvalidArgumentException
     * @return array
     */
    private function parseEntriesToDataArray($entries, $returnIDs = false) {
        if(!is_array($entries)) {
            throw new InvalidArgumentException('type of $entries is not array');
        }

        $data = [];
        $ids = [];
        $key = 0;

        foreach($entries as $entry) {
            if($entry == null || !($entry instanceof CalendarAnnouncement)){
                continue;
            }

            if($returnIDs === true) {
                $ids[$entry->ID] = $entry->ID;
                continue;
            }

            $generatedEntries = $entry->cutExceptions();

            foreach($generatedEntries as $generatedEntry) {
                $data[$key++] = $this->entryToDataArray($generatedEntry);
            }
        }

        if($returnIDs === true) {
            return $ids;
        }

        return $data;
    }

    /** generates a data array for the frontend
     * @param CalendarAnnouncement $entry
     * @return array
     */
    private function entryToDataArray($entry) {
        $data = [
            'id' => $entry->ID,
            'title' => $entry->Title,
            'allDay' => $entry->AllDay,
        ];

        if($entry->AllDay == 0) {
            $data['start'] = $entry->StartDate . ' ' . $entry->StartTime;
            $data['end'] = $entry->EndDate . ' ' . $entry->EndTime;
        } else {
            $data['start'] = $entry->StartDate;
            $data['end'] = $entry->EndDate;
        } if($entry->CategoryID) {
            $data['color'] = $entry->Category()->Color;
            $data['textColor'] = $entry->Category()->FontColor;
        }

        return $data;
    }

    public function reloadlistviewentries($r = false) {
        if(!$r) {
            $r = $this->request;
        }

        if($r->isAjax()) {
            $v = $r->postVars();
            $start = $v['start'];
            $end = date('Y-m-d', strtotime('-1 day', strtotime($v['end'])));
            $entriesIDs = $this->entriesasjson($start, $end, true);

            if($entriesIDs != null) {
                $entries = CalendarAnnouncement::get()->byIDs($entriesIDs);
                $entries = $this->CalendarEntries(false, true, $entries, true);

                return $this->renderWith('CalendarListView', ['CalendarEntries' => $entries, 'AjaxData']);
            }
        }
    }

    public function reloadcalendarlegend($r = false) {
        if(!$r) {
            $r = $this->request;
        }

        if($r->isAjax()) {
            $v = $r->postVars();
            $start = $v['start'];
            $end = date('Y-m-d', strtotime('-1 day', strtotime($v['end'])));
            $entriesIDs = $this->entriesasjson($start, $end, true);

            if($entriesIDs != null) {
                $entries = CalendarAnnouncement::get()->byIDs($entriesIDs);
                $categories = CalendarAnnouncementCategory::get()->byIDs($entries->column('CategoryID'));

                return $this->renderWith('CategoryLegend', ['CurrentCategories' => $categories, 'AjaxData']);
            }
        }
    }

    public function icsfeed() {
        $cal = new SimpleICS();

        foreach($this->Announcements() as $announcement) {
            $cal->addEvent(function($e) use ($announcement) {
                $e->startDate = $announcement->StartDate . ' ' . $announcement->StartTime;
                $e->endDate = $announcement->EdnDate . ' ' . $announcement->EdnTime;
                $e->description = $announcement->Title;
                $e->summary = $announcement->Content;
            });
        }

        header('Content-Type: '.SimpleICS::MIME_TYPE);
        header('Content-Disposition: attachment; filename=event.ics');
        echo $cal->serialize();
    }
}