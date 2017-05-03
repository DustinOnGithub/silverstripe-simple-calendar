<?php
class CalendarAnnouncementRegistration extends DataObject {

  private static $singular_name = 'Anmeldung';
  private static $plural_name = 'Anmeldungen';

  private static $db = [
    'Email' => 'Varchar(255)',
    'FirstName' => 'Varchar(255)',
    'Surname' => 'Varchar(255)',
  ];
  
  private static $has_one = [
    'Announcement' => 'CalendarAnnouncement',
  ];

  private static $summary_fields = [
    'Announcement.Event.Title' => 'Veranstaltung',
    'Email' => 'E-Mail',
    'FirstName' => 'Vorname',
    'Surname' => 'Nachname',
  ];
  
  public function validate() {
    $result = parent::validate();

    if(!$this->AnnouncementID) {
      $result->error('Bitte wählen Sie einen Termin aus');
    }

    if(!$this->Email) {
      $result->error('Bitte geben Sie mindestens eine E-Mail Adresse an');
    }

    if(CalendarAnnouncementRegistration::get()
      ->exclude('ID', $this->ID)
      ->filter([
        'AnnouncementID' => $this->AnnouncementID,
        'Email' => $this->Email,
        'FirstName' => $this->FirstName,
        'Surname' => $this->Surname,
      ])
    ->first()) {
      $result->error($this->Email . ' ist bereits für den Termin ' . $this->Announcement()->DateRange() . ' angemeldet');
    }

    return $result;
  }

  public function getCMSFields() {
    $fields = FieldList::create(
      TabSet::create('Root',
        Tab::create('Main', 'Hauptteil',
          EmailField::create('Email', 'E-Mail'),
          TextField::create('FirstName', 'Vorname'),
          TextField::create('Surname', 'Nachname'),
          DropdownField::create('AnnouncementID', 'Termin', CalendarAnnouncement::get()->map('ID', 'DateRange')->toArray())
            ->setEmptyString('(Bitte wählen Sie einen Termin aus)')
        )
      )
    );

    $this->extend('updateCMSFields', $fields);

    return $fields;
  }
}