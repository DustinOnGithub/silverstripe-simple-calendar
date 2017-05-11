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
  ];
  
  private static $has_one = [
    'Calendar' => 'CalendarPage',
    'Event' => 'CalendarEventPage',
    'Category' => 'CalendarAnnouncementCategory',
  ];

  private static $has_many = [
    'Registrations' => 'CalendarAnnouncementRegistration',
  ];

  private static $default_sort = 'StartDate DESC, EndDate DESC, StartTime DESC, EndTime DESC';

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

    return $result;
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

  public function getCMSFields() {
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
            ->setEmptyString('(Bitte auswählen)'),
          DropdownField::create('EventID', 'Veranstaltung', CalendarEventPage::get()->map('ID', 'MenuTitle')->toArray())
            ->setEmptyString('(Bitte auswählen)')
        )
      )
    );

    $this->extend('updateCMSFields', $fields);

    return $fields;
  }
}