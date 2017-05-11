<div class="calendar-entry calendar-entry--is-<% if $Event %>event<% else %>announcement<% end_if %> cf">
  <div class="calendar-entry__dates">
    <% if $Event %>
      <% loop $Event.Announcements %>
        <div class="dates__date">
          <% include CalendarAnnouncementDate %>
        </div>
      <% end_loop %>
    <% else %>
      <% include CalendarAnnouncementDate %>
    <% end_if %>
  </div>
  <div class="calendar-entry__details">
    <% if $Event %>
      <% with $Event %>
        <h3><a href="$Link" title="$Title anzeigen">$MenuTitle</a></h3>
        <% if $Location %>
          <span class="calendar-entry__location">
                  <i class="fa fa-map-marker"></i>$Location
                </span>
        <% end_if %>
        <% if $Announcements %>
          <span class="calendar-entry__category">
                <i class="fa fa-tag"></i><% loop $Announcements %>$Category.Title<% if not $Last %>, <% end_if %><% end_loop %>
              </span>
        <% end_if %>
        <% if $Content %>
          <p>$Content.Summary(40)</p>
        <% end_if %>
        <a class="btn" href="$Link" title="$Title anzeigen">Details & Anmeldung</a>
      <% end_with %>
    <% else %>
      <h3>$Title</h3>
      <% if $Category %>
        <span class="calendar-entry__category">
              <i class="fa fa-tag"></i>$Category.Title
            </span>
      <% end_if %>
      <% if $Content %>
        <p>$Content</p>
      <% end_if %>
    <% end_if %>
  </div>
</div>