<?php
class CalendarPage_ControllerExtension extends DataExtension {

  public function onAfterInit() {
    if($this->owner->CalendarView == 'cal') {
      Requirements::css('//cdnjs.cloudflare.com/ajax/libs/fullcalendar/3.4.0/fullcalendar.min.css');
      Requirements::css(SIMPLE_CALENDAR . '/css/simple-calendar.css');
      Requirements::javascript('//cdnjs.cloudflare.com/ajax/libs/moment.js/2.18.1/moment.min.js');
      Requirements::javascript('//cdnjs.cloudflare.com/ajax/libs/fullcalendar/3.4.0/fullcalendar.min.js');
      Requirements::javascript('//cdnjs.cloudflare.com/ajax/libs/fullcalendar/3.4.0/locale/de.js');
      Requirements::javascript(SIMPLE_CALENDAR . '/js/simple-calendar.js');
    }
  }
}