<?php
class GridConfig extends GridFieldConfig {

  public function __construct($itemsPerPage = 30, $sortField = false) {
    parent::__construct();

    $this->addComponent(new GridFieldButtonRow('before'));
    $this->addComponent(new GridFieldAddNewButton('buttons-before-left'));
    $this->addComponent(new GridFieldToolbarHeader());
    $this->addComponent($sort = new GridFieldSortableHeader());
    $this->addComponent($filter = new GridFieldFilterHeader());

    if($sortField) {
      $this->addComponent(new GridFieldOrderableRows($sortField));
    }

    $this->addComponent(new GridFieldDataColumns());
    $this->addComponent(new GridFieldEditButton());
    $this->addComponent(new GridFieldDeleteAction());
    $this->addComponent(new GridFieldPageCount('toolbar-header-right'));
    $this->addComponent($pagination = new GridFieldPaginator($itemsPerPage));
    $this->addComponent(new GridFieldDetailForm());

    $filter->setThrowExceptionOnBadDataType(false);
    $sort->setThrowExceptionOnBadDataType(false);
    $pagination->setThrowExceptionOnBadDataType(false);

    $this->extend('updateConfig', $this);
  }

  // - --------------------------------------------------------------------------------------
  // - Demo Usage
  // - Note that cou can combine relation and multi but not inline with relation and/or multi
  // - --------------------------------------------------------------------------------------
  //
  //  GridField::create('Relation', 'Title', $this->Relation(), $conf = GridConfig::create())
  //
  //  $conf->set([
  //    'relation',
  //    'multi',
  //    'inline' => [
  //      'edit',
  //      'fields' => [
  //        'FirstField' => [
  //          'title' => 'Custom Title',
  //          'field' => 'ReadonlyField'
  //        ],
  //        'SecondField' => [
  //          'title' => 'Custom Title Two',
  //          'callback' => function($record, $column, $grid) {
  //            return TextField::create($column);
  //          }
  //        ]
  //      ]
  //    ]
  //  ]);

  public function set(Array $args) {
    if(in_array('multi', $args)) {
      $this->removeComponentsByType('GridFieldAddNewButton');
      $this->addComponent(new GridFieldAddNewMultiClass('buttons-before-left'));
    }

    if(in_array('relation', $args)) {
      $this->addComponent(new GridFieldAddExistingSearchButton());
      $this->addComponent(new GridFieldDeleteAction(true));
    }

    if(in_array('inline', $args) || isset($args['inline'])) {
      $this->removeComponentsByType('GridFieldAddNewButton');
      $this->removeComponentsByType('GridFieldAddNewMultiClass');
      $this->removeComponentsByType('GridFieldDataColumns');
      $this->removeComponentsByType('GridFieldEditButton');
      $this->removeComponentsByType('GridFieldDeleteAction');

      $this->addComponent(new GridFieldEditableColumns());

      if(isset($args['inline'])) {
        $inlineSettings = $args['inline'];

        if(in_array('edit', $inlineSettings)) {
          $this->addComponent(new GridFieldDetailForm());
          $this->addComponent(new GridFieldEditButton());
        }

        if(isset($inlineSettings['fields'])) {
          $this->getComponentByType('GridFieldEditableColumns')
               ->setDisplayFields($inlineSettings['fields']);
        }
      }

      $this->addComponent(new GridFieldDeleteAction());
      $this->addComponent(new GridFieldAddNewInlineButton('buttons-before-left'));
    }
  }
}
