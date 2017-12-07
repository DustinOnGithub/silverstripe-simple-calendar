<span class="date__day"><% include CalendarDateTime %></span>
<% if not $AllDay %>
  <span class="date__time"><% if not $EndTime %>ab <% end_if %>$StartTime.Format('H:i')<% if $EndTime %> - $EndTime.Format('H:i')<% end_if %> Uhr</span>
<% end_if %>