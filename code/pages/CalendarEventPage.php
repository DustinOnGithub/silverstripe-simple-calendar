<?php
class CalendarEventPage extends Page {

    private static $singular_name = 'Veranstaltung';
    private static $description = 'Eine Veranstalung mit einem oder mehreren Terminen';
    private static $can_be_root = false;
    private static $allowed_children = [];
    private static $default_child = '';

    private static $db = [
        'Location' => 'Varchar(255)',
        'EnableSignUp' => 'Boolean',
    ];

    private static $has_many = [
        'Announcements' => 'CalendarAnnouncement',
    ];

    private static $summary_fields = [
        'Title' => 'Titel',
        'Location' => 'Ort',
        'Announcements.Count' => 'Termine',
        'EnableSignUp.Nice' => 'Anmeldung möglich',
        'Registrations.Count' => 'Anmeldungen',
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

    public function getCMSFields() {
        $fields = parent::getCMSFields();
        $fields->addFieldsToTab('Root.Main', [
            TextField::create('Location', 'Veranstaltungsort')
        ], 'Content');

        $fields->insertAfter(Tab::create('Announcements', 'Termine'), 'Main');
        $fields->addFieldsToTab('Root.Announcements', [
            DropdownField::create('EnableSignUp', 'Anmeldung erlauben', [1 => 'Ja', 0 => 'Nein'], 1),
            GridField::create('Announcements', 'Termine', $this->Announcements(), $gridConf = CalendarGridConfig::create())
        ]);

        $gridConf->set([
            'inline' => [
                'fields' => [
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
                    'Registrations.Count' => [
                        'title' => 'Anmeldungen',
                        'field' => 'ReadonlyField',
                    ],
                    'ID' => [
                        'title' => 'ID',
                        'field' => 'ReadonlyField',
                    ],
                ],
            ],
        ]);

        $fields->insertAfter(Tab::create('Registrations', 'Anmeldungen'), 'Announcements');
        $fields->addFieldsToTab('Root.Registrations', [
            GridField::create('Registrations', 'Anmeldungen', $this->Registrations(), $gridConf = CalendarGridConfig::create())
                ->setDescription('<br>Bitte verwenden Sie die Termin-ID zum filtern. Diese finden Sie im Tab "Termine" beim jeweiligen Eintrag')
        ]);

        $gridConf->set([
            'inline' => [
                'fields' => [
                    'Email' => [
                        'title' => 'E-Mail',
                        'field' => 'EmailField',
                    ],
                    'FirstName' => [
                        'title' => 'Vorname',
                        'field' => 'TextField',
                    ],
                    'Surname' => [
                        'title' => 'Nachname',
                        'field' => 'TextField',
                    ],
                    'AnnouncementID' => [
                        'title' => 'Termin',
                        'callback' => function($record, $column, $grid) {
                            return DropdownField::create($column)
                                ->setSource($this->Announcements()->map('ID', 'DateRange')->toArray())
                                ->setEmptyString('(Bitte wählen Sie einen Termin aus)');
                        }
                    ]
                ]
            ]
        ]);

        return $fields;
    }

    public function Registrations() {
        $registrationIDs = [];

        foreach($this->Announcements() as $announcement) {
            $registrationIDs += $announcement->Registrations()->getIDList();
        }

        return CalendarAnnouncementRegistration::get()->byIDs($registrationIDs);
    }

    public function UniqueAnnouncementCategories() {
        $announcements = $this->Announcements();
        return CalendarAnnouncementCategory::get()->byIDs($announcements->column('CategoryID'));
    }
}

class CalendarEventPage_Controller extends Page_Controller {

    private static $allowed_actions = [
        'SignUp'
    ];

    public function init() {
        parent::init();
    }

    public function SignUp() {
        $fields = FieldList::create(
            HeaderField::create('Anmeldung', 2),
            TextField::create('FirstName', 'Vorname'),
            TextField::create('Surname', 'Nachname'),
            EmailField::create('Email', 'E-Mail'),
            CheckboxSetField::create('Dates', 'Termin(e)', $this->Announcements()->map('ID', 'DateRange')->toArray())
        );

        $required = RequiredFields::create('Email', 'FirstName', 'Surname', 'Dates');
        $actions = FieldList::create(
            FormAction::create('doSignUp', 'verbindlich zu den ausgewählten Terminen anmelden')
        );

        $form = Form::create($this, 'SignUp', $fields, $actions, $required);

        if($member = Member::currentUser()) {
            $form->loadDataFrom($member);
        }

        return $form;
    }

    public function doSignUp($data, $form) {
        $data = Convert::raw2sql($data);
        $siteconfig = SiteConfig::current_site_config();

        // - unset unnecessary fields
        unset($data['url']);
        unset($data['SecurityID']);
        unset($data['action_doSignUp']);

        $errors = [];

        // - generate registration
        foreach($data['Dates'] as $announcementID) {
            $registration = CalendarAnnouncementRegistration::create();
            $form->saveInto($registration);
            $registration->AnnouncementID = $announcementID;

            try {
                $registration->write();
                $form->sessionMessage('Ihre Buchung wurde eingetragen.', 'system-message system-message--good system-message--permanent');
            } catch (Exception $e) {
                $errors[] = $e->getMessage();
            }
        }

        if(count($errors) < count($data['Dates'])) {
            // - generate email content
            $subject = 'Neue Anmeldung für: ' . $this->MenuTitle;
            $emailBody[] = '<html><head><title>';
            $emailBody[] = $subject;
            $emailBody[] = '</title></head><body><h3>';
            $emailBody[] = $subject;
            $emailBody[] = '</h3><table border="0">';

            foreach ($data as $key => $value) {
                if (is_array($value)) {
                    $newValue = [];

                    foreach ($value as $announcementID) {
                        $newValue[] = CalendarAnnouncement::get()->byID($announcementID)->DateRange();
                    }

                    $value = implode(', ', $newValue);
                }

                $emailBody[] = '<tr><td valign="top"><strong>' . $key . '</strong></td><td valign="top"><span>' . $value . '</span></td></tr>';
            }

            $emailBody[] = '</table></body><html>';

            // - create noreply sender address and genereate admin email
            $noreply = explode('@', $siteconfig->Email);
            $noreply[0] = 'noreply';
            $from = $siteconfig->EmailSender . '<' . implode('@', $noreply) . '>';
            $email = Email::create($from, $siteconfig->Email, $subject, implode('', $emailBody));
            $email->replyTo($data['Email']);
            $email->send();

            // - send copy to user
            $subject2 = 'Kopie Ihrer Anmeldung für: ' . $this->MenuTitle;
            $emailBody[1] = $subject2;
            $emailBody[3] = $subject2;
            $email2 = Email::create($from, $data['Email'], $subject2, implode('', $emailBody));
            $email2->send();
        }

        if($count = count($errors)) {
            if($count >= count($data['Dates'])) {
                $form->sessionMessage('Sie sind bereits zu dieser Veranstaltung angemeldet', 'system-message system-message--neutral system-message--permanent');
            } else {
                $form->sessionMessage('Vielen Dank für Ihre Anmeldung. ' . implode(',', $errors), 'system-message system-message--good system-message--permanent');
            }
        } else {
            $form->sessionMessage('Vielen Dank für Ihre Anmeldung.', 'system-message system-message--good system-message--permanent');
        }

        return $this->redirect($this->Link() . '#' . $form->getName());
    }
}