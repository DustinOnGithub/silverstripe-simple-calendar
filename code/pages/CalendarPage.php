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
    ], 'Content');
    $fields->insertAfter(Tab::create('Announcements', 'Ankündigungen'), 'Main');
    $fields->addFieldsToTab('Root.Announcements', [
      GridField::create('Announcements', 'Alle Ankündigungen', $this->Announcements(), $gridConf = GridConfig::create())
        ->addExtraClass('annoucements-grid')
        ->setDescription('<br>Ankündigungen sind "Termine" ohne eigene Seite. z.B. Betriebsurlaub oder Geburtstage')
    ]);

    $fields->insertAfter(Tab::create('Categories', 'Kategorien'), 'Announcements');
    $fields->addFieldsToTab('Root.Categories', [
      GridField::create('Categories', 'Kategorien', CalendarAnnouncementCategory::get(), $calAnnCatConf = GridConfig::create())
        ->addExtraClass('calendarannoucementscategory-grid')
    ]);

    $calAnnCatConf->set([
      'inline' => [
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
  ];

  public function CalendarEntries($allEntries = false, $reverseOrder = false, $entries = false, $noDateFilter = false) {
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

  public function entriesasjson($start = false, $end = false, $returnIDs = false) {
    $r = $this->request;
    $v = $r->postVars();

    if($start instanceof SS_HTTPRequest) {
      $start = $v['start'];
    }

    if(!$end) {
      $end = $v['end'];
    }

    $allEntries = $this->CalendarEntries(true);

    $data = [];
    $ids = [];

    foreach($allEntries as $entry) {
      if(!($entry->StartDate >= $start && $entry->StartDate <= $end)) {
        continue;
      }

      if(!$entry->AllDay && $entry->StartDate != $entry->EndDate && $entry->StartTime != $entry->EndTime && $entry->StartDate && $entry->EndDate) {
        $days = (strtotime($entry->EndDate) - strtotime($entry->StartDate)) / (60 * 60 * 24) + 1;
        $daysI = 0;

        if($entry->EventID) {
          $title = $entry->Event()->Title;
          $url = $entry->Event()->AbsoluteLink();
        } else {
          $title = $entry->Title;
          $url = false;
        }

        do {
          $newDate = date('Y-m-d', strtotime("$entry->StartDate +$daysI days"));

          $newData = [
            'id' => $entry->ID + $daysI + 20,
            'title' => $title,
            'start' => $newDate . ' ' . $entry->StartTime,
            'end' => $newDate . ' ' . $entry->EndTime,
            'color' => 'yellow',
            'textColor' => 'black',
          ];

          if($entry->CategoryID) {
            $newData['color'] = $entry->Category()->Color;
            $newData['textColor'] = $entry->Category()->FontColor;
          }

          if($url) {
            $newData['url'] = $url;
            $newData['className'] = 'calendar-entry-with-detailpage';
          }

          $data[] = $newData;
          $ids[$entry->ID] = $entry->ID;

          $daysI++;
        } while ($daysI < $days);
      } else {
        $newData = [
          'id' => $entry->ID,
          'title' => $entry->Title,
          'allDay' => $entry->AllDay,
          'color' => 'yellow',
          'textColor' => 'black',
        ];

        if($entry->CategoryID) {
          $newData['color'] = $entry->Category()->Color;
          $newData['textColor'] = $entry->Category()->FontColor;
        }

        $startTime = $entry->StartTime;
        if(!$startTime) {
          $startTime = '00:00:00';
        }

        $newData['start'] = $entry->StartDate . ' ' . $startTime;

        if(!$entry->EndDate) {
          $newData['end'] = $entry->StartDate . ' ' . $entry->EndTime;
        } else {
          $newData['end'] = $entry->EndDate . ' ' . $entry->EndTime;
        }

        if($entry->EventID) {
          $newData['title'] = $entry->Event()->Title;
          $newData['url'] = $entry->Event()->AbsoluteLink();
          $newData['className'] = 'calendar-entry-with-detailpage';
        }

        $data[] = $newData;
        $ids[$entry->ID] = $entry->ID;
      }
    }

    if($returnIDs) {
      return $ids;
    }

    return json_encode($data);
  }

  public function reloadlistviewentries() {
    $r = $this->request;

    if($r->isAjax()) {
      $v = $r->postVars();
      $start = $v['start'];
      $end = date('Y-m-d', strtotime('-1 day', strtotime($v['end'])));
      $entriesIDs = $this->entriesasjson($start, $end, true);
      $entries = CalendarAnnouncement::get()->byIDs($entriesIDs);
      $entries = $this->CalendarEntries(false, true, $entries, true);

      return $this->renderWith('CalendarListView', ['CalendarEntries' => $entries, 'AjaxData']);
    }
  }

  public function reloadcalendarlegend() {
    $r = $this->request;

    if($r->isAjax()) {
      $v = $r->postVars();
      $start = $v['start'];
      $end = date('Y-m-d', strtotime('-1 day', strtotime($v['end'])));
      $entriesIDs = $this->entriesasjson($start, $end, true);
      $entries = CalendarAnnouncement::get()->byIDs($entriesIDs);
      $categories = CalendarAnnouncementCategory::get()->byIDs($entries->column('CategoryID'));

      return $this->renderWith('CategoryLegend', ['CurrentCategories' => $categories, 'AjaxData']);
    }
  }
}