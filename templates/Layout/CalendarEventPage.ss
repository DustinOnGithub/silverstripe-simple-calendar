<div class="calendar-event">
  <% if $Content %>
    <div class="calendar-event__text">
      $Content
    </div>
    <div class="calendar-entry__dates">
      <div class="dates__headline">
        $Announcements.Count <% if $Announcements.Count > 1 %>mögliche Termine<% else %>möglicher Termin<% end_if %>
      </div>
      <% loop $Announcements %>
        <div class="dates__date">
          <% include CalendarAnnouncementDate %>
        </div>
      <% end_loop %>
    </div>
  <% end_if %>
  <% if $EnableSignUp %>
    $SignUp
  <% end_if %>
</div>