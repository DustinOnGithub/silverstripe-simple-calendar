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
          alert('Einträge der Quelle "entriesasjson" konnten nicht geladen werden');
        },
        success: function() {
          ec.emit('reloadListViewEntries');
          ec.emit('reloadCalendarLegend');
        }
      }
    ],
    windowResize: function(view) {
      if ($(window).width() < window.simplecalendarBreakpoint){
        $('.calendar.calendar--calendar-view').fullCalendar('changeView', 'listMonth');
      } else {
	      $('.calendar.calendar--calendar-view').fullCalendar('changeView', 'month');
      }
    }
  };

	window.simplecalendarOptions.defaultView = $('.calendar.calendar--calendar-view').data('default-view');
  $('.calendar.calendar--calendar-view').fullCalendar(window.simplecalendarOptions);
});

// - Event Controller
var EventController = function() {
  return {
    eventListeners: [],
    on: function(eventName, callback) {
      this.eventListeners.push({
        name: eventName,
        callback: callback
      });
    },
    emit: function(eventName, payload) {
      this.eventListeners.forEach(function(element, index) {
        if (element.name == eventName){
          element['callback'](payload);
        }
      })
    }
  }
}

var ec = new EventController();

ec.on('reloadListViewEntries', function(payload) {
  $.ajax({
    url: window.location.pathname + '/reloadlistviewentries',
    context: document.body,
    type: 'POST',
    data: {
      start: $('.calendar--calendar-view').fullCalendar('getView').start.format(),
      end: $('.calendar--calendar-view').fullCalendar('getView').end.format()
    },
    success: function(data) {
      var outputContainer = $('.calendar-ajax--list-view');
      outputContainer.html(data);
    },
    error: function(request, status, error) {
      alert('Einträge der Quelle "reloadlistviewentries" konnten nicht geladen werden');
    }
  });
});

ec.on('reloadCalendarLegend', function(payload) {
  $.ajax({
    url: window.location.pathname + '/reloadcalendarlegend',
    context: document.body,
    type: 'POST',
    data: {
      start: $('.calendar--calendar-view').fullCalendar('getView').start.format(),
      end: $('.calendar--calendar-view').fullCalendar('getView').end.format()
    },
    success: function(data) {
      var outputContainer = $('.calendar-ajax--legend');
      outputContainer.html(data);
    },
    error: function(request, status, error) {
      alert('Einträge der Quelle "reloadcalendarlegend" konnten nicht geladen werden');
    }
  });
});