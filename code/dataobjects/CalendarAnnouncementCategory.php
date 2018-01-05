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
        'Exceptions' => 'CalendarAnnouncementException',
    ];

    private static $summary_fields = [
        'Title' => 'Titel',
        'Announcements.Count' => 'Anzahl',
    ];

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
            ),
            GridField::create('Exceptions', 'Ausnahmen', $this->Exceptions(), $exceptionGC = GridConfig::create())
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

        $this->extend('updateCMSFields', $fields);

        return $fields;
    }
}