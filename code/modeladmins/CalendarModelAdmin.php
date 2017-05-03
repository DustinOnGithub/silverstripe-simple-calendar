<?php
class CalendarModelAdmin extends ModelAdmin {

  private static $menu_priority = 1;
  private static $menu_title = 'Kalenderverwaltung';
  private static $menu_icon = 'simple-calendar/imgs/calendar.png';
  private static $url_segment = 'calendar';
    
  private static $managed_models = [
    'CalendarAnnouncement' => [
      'title' => 'Ankündigungen'
    ],
    'CalendarAnnouncementRegistration' => [
      'title' => 'Anmeldungen'
    ],
    'CalendarAnnouncementCategory' => [
      'title' => 'Kategorien'
    ],
    'CalendarEventPage' => [
      'title' => 'Veranstaltungen'
    ],
    'CalendarPage' => [
      'title' => 'Kalender'
    ],
  ];
  
  public function getEditForm($id = null, $fields = null) {
    $form = parent::getEditForm($id, $fields);
    $fields = $form->Fields();

    if($gridField = $form->Fields()->dataFieldByName($this->sanitiseClassName($this->modelClass))) {
      if($gridField instanceof GridField) {
        $gridFieldConfig = $gridField->getConfig();
        if($this->modelClass == 'CalendarEventPage' || $this->modelClass == 'CalendarPage') {
          $gridFieldConfig->removeComponentsByType('GridFieldAddNewButton');
          $gridFieldConfig->removeComponentsByType('GridFieldDeleteAction');
        }
      }
    }

    return $form;
  }

  public function getList() {
    $list = parent::getList();

    if($this->modelClass == 'CalendarAnnouncement'){
       $list = $list->exclude('EventID:GreaterThan', '0');
    }

    return $list;
  }
  
  public function getExportFields() {
    $r = $this->request;
      if($modelClass = $r->param('ModelClass')) {
      $model = singleton($modelClass);
      if($model->hasMethod('getExportFields')) {
        return $model->getExportFields();
      } else {
        return $model->SummaryFields();
      }
    }
  }
  
  private static $model_importers = [];
}