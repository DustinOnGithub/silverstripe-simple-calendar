<?php
class CalendarAnnouncementException extends DataObject {

    private static $singular_name = 'Ausnahme';
    private static $plural_name = 'Ausnahmen';

    private static $db = [
        'Date' => 'Date',
        'Reaction' => 'Varchar(50)',
    ];

    private static $has_one = [
        'Announcement' => 'CalendarAnnouncement',
        'Category' => 'CalendarAnnouncementCategory'
    ];

    public function onAfterWrite() {
        parent::onAfterWrite();

        if(!$this->Date) {
            $this->delete();
        }
    }

    private $timestamp = -1;

    public function getTimestamp() {
        if($this->timestamp == -1) {
            $this->timestamp = strtotime($this->Date);
        }

        return $this->timestamp;
    }
}