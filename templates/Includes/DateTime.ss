<% if $StartDate != $EndDate && $EndDate %>
  <% if $StartDate.Year == $EndDate.Year %>
    <% if $StartDate.Time24 && $StartDate.Time24 != '00:00' %>
      $StartDate.Format('d.m H:i') Uhr
    <% else %>
      $StartDate.Format('d.m')
    <% end_if %>
    -
    <% if $EndDate.Time24 && $EndDate.Time24 != '00:00' %>
      $EndDate.Format('d.m.Y H:i') Uhr
    <% else %>
      $EndDate.Format('d.m.Y')
    <% end_if %>
  <% else %>
    <% if $StartDate.Time24 && $StartDate.Time24 != '00:00' %>
      $StartDate.Format('d.m.Y H:i') Uhr
    <% else %>
      $StartDate.Format('d.m.Y')
    <% end_if %>
    -
    <% if $EndDate.Time24 && $EndDate.Time24 != '00:00' %>
      $EndDate.Format('d.m.Y H:i') Uhr
    <% else %>
      $EndDate.Format('d.m.Y')
    <% end_if %>
  <% end_if %>
<% else_if $EndDate %>
  <% if $StartDate.Time24 && $StartDate.Time24 != '00:00' %>
    $StartDate.Format('d.m.Y H:i') Uhr
  <% else %>
    $StartDate.Format('d.m.Y')
  <% end_if %>
<% else %>
  <% if $StartDate.Time24 && $StartDate.Time24 != '00:00' %>
    $StartDate.Format('d.m.Y H:i') Uhr
  <% else %>
    $StartDate.Format('d.m.Y')
  <% end_if %>
<% end_if %>