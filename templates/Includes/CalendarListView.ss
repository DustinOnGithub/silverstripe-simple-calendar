<section class="calendar calendar--list-view">
  <% if $AjaxData %>
    <% loop $CalendarEntries %>
      <% include CalendarEntryListView %>
    <% end_loop %>
  <% else %>
    <% loop $CalendarEntries('false', 'true') %>
      <% include CalendarEntryListView %>
    <% end_loop %>
  <% end_if %>
</section>