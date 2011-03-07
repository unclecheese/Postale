<div class="message_actions">
	<a class="btn" href="$BackToMessagesLink">&laquo; <% _t('Postale.BACKTOMESSAGES','Back to messages') %></a>
	<% if Thread %>
		<% control Thread %>
			<a class="btn" href="$MarkDeletedLink"><% _t('Postale.DELETE','Delete') %></a>
			<% if IsRead %>
				<a class="btn" href="$MarkUnreadLink"><% _t('Postale.MARKASUNREAD','Mark as unread') %></a>
			<% else %>
				<a class="btn" href="$MarkReadLink"><% _t('Postale.MARKASREAD','Mark read') %></a>
			<% end_if %>
			<div class="next_prev">
				<% if PrevLink %>
					<a class="btn" href="$PrevLink" title="<% _t('Postale.PREV','Previous') %>">&laquo;</a>
				<% else %>
					<a class="btn disabled" href="javascript:void(0)" title="<% _t('Postale.PREV','Previous') %>">&laquo;</a>
				<% end_if %>
				<% if NextLink %>
					<a class="btn" href="$NextLink" title="<% _t('Postale.NEXT','Next') %>">&raquo;</a>
				<% else %>
					<a class="btn disabled" href="javascript:void(0)" title="<% _t('Postale.NEXT','Next') %>">&raquo;</a>
				<% end_if %>
			</div>
		<% end_control %>
	<% end_if %>
</div>
