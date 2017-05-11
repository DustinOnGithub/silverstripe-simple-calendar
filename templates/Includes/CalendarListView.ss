<section class="calendar calendar--list-view">
    <% if $CalendarEntries %>
      <% loop $CalendarEntries('false', 'true') %>
        <% include CalendarEntryListView %>
      <% end_loop %>
    <% else %>
      <div class="system-message system-message--neutral system-message--permanent">
        <p>Für diesen Zeitraum liegen derzeit keine Kalendareinträge vor</p>
      </div>
    <% end_if %>
</section>