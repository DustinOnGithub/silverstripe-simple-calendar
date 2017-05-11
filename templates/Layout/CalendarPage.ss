<% if $CalendarEntries %>
  <% if $CalendarView == 'list' %>
    <% include CalendarListView %>
  <% else %>
    <% include CalendarCalendarView %>
    <div class="calendar-ajax--legend">
      <% include CategoryLegend %>
    </div>
    <div class="calendar-ajax--list-view">
      <% include CalendarListView %>
    </div>
  <% end_if %>
<% else %>
  <div class="system-message system-message--neutral system-message--permanent">
    <p>Es liegen derzeit keine Kalendareinträge vor</p>
  </div>
<% end_if %>