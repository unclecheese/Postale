<div id="Layout" class="typography">
	<h2><% _t('Postale.MESSAGES','Messages') %>: $CurrentMember.FullLabel</h2>
	<div class="messages">
		<div id="messages_util" class="clearfix">
			<div id="new_message">
				<a class="btn" rel="fb" href="$NewMessageLink">+ <% _t('Postale.NEWMESSAGE','New Message') %></a>
			</div>
			<div id="messages_search">
				<% include MessagesSearch %>
			</div>
		</div>
		<div id="messages_interface">
			<% include MessageDetail %>
		</div>
	</div>
</div>
