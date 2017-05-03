jQuery('document').ready(function() {
  window.simplecalendarBreakpoint = 500;
  window.simplecalendarOptions = {
    locale: 'de',
    header: {
      left: 'prev,next today',
      center: 'title',
      right: 'month,agendaWeek,agendaDay'
    },
    timeFormat: 'H:mm',
    displayEventEnd: true,
    defaultView: 'month',
    selectable: true,
    selectHelper: true,
    eventSources: [
      {
        url: window.location.pathname + '/entriesasjson',
        type: 'POST',
        error: function() {
          alert('Einträge der Queller "entriesasjson" konnten nicht geladen werden');
        }
      }
    ],
    windowResize: function(view) {
      if ($(window).width() < window.simplecalendarBreakpoint){
        $('.calendar.calendar--calendar-view').fullCalendar('changeView', 'listMonth');
        $('.fc-today-button').hide();
        $('.fc-right').not('.fc-center').hide();
        $('.fc-center').addClass('fc-right');
      } else {
        $('.calendar.calendar--calendar-view').fullCalendar('changeView', 'month');
        $('.fc-today-button').show();
        $('.fc-right').show();
        $('.fc-center').removeClass('fc-right');
      }
    }
  };

  window.simplecalendarOptions.defaultView = $('.calendar.calendar--calendar-view').data('default-view');

  if($(window).width() < window.simplecalendarBreakpoint) {
    window.simplecalendarOptions.defaultView = 'listMonth';
    window.simplecalendarOptions.header.left = 'prev';
    window.simplecalendarOptions.header.right = 'next';
  }

  $('.calendar.calendar--calendar-view').fullCalendar(window.simplecalendarOptions);
});