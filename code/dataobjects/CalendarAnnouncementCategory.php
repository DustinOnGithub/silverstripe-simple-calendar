<?php
class CalendarAnnouncementCategory extends DataObject {

  private static $singular_name = 'Kategorie';
  private static $plural_name = 'Kategorien';

  private static $db = [
    'Title' => 'Varchar(255)',
    'Color' => 'Varchar(7)',
    'FontColor' => 'Varchar(7)'
  ];

  private static $has_many = [
    'Announcements' => 'CalendarAnnouncement',
  ];

  private static $summary_fields = [
    'Title' => 'Titel',
    'Announcements.Count' => 'Anzahl',
  ];

  public function validate() {
    $result = parent::validate();

    if(!$this->Title) {
      $result->error('Titel muss ausgefüllt werden');
    }

    if(!$this->Color) {
      $result->error('Farbe muss ausgefüllt werden');
    }

    if(!$this->FontColor) {
      $result->error('Schriftfarbe muss ausgefüllt werden');
    }

    return $result;
  }

  public function getCMSFields() {
    $fields = FieldList::create(
      TabSet::create('Root',
        Tab::create('Main', 'Hauptteil',
          TextField::create('Title', 'Titel'),
          ColourPicker::create('Color', 'Farbe'),
          ColourPicker::create('FontColor', 'Schriftfarbe')
        )
      )
    );

    $this->extend('updateCMSFields', $fields);

    return $fields;
  }
}