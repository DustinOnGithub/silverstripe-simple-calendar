<% if $CalendarEntries %>
  <% if $CalendarView == 'list' %>
    <% include CalendarListView %>
  <% else %>
    <% include CalendarCalendarView %>
  <% end_if %>
<% else %>
  <div class="system-message system-message--neutral system-message--permanent">
    <p>Es liegen derzeit keine Kalendareinträge vor</p>
  </div>
<% end_if %>