<% if $CurrentCategories %>
  <div class="calendar-categories">
    <strong>Legende:</strong>
    <ul>
      <% loop $CurrentCategories %>
        <li style="background: $Color; color: $FontColor;">$Title</li>
      <% end_loop %>
      <li>Mehr Details / Detailseite</li>
    </ul>
  </div>
<% end_if %>