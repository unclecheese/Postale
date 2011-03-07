<div id="Layout" class="typography">
	<h2><% _t('Postale.MESSAGES','Messages') %>: <% sprintf(_t('Postale.SEARCHFOR','Search for "%s"'),$Query) %></h2>
	<div class="messages">
		<div id="messages_util" class="clearfix">
			<div id="new_message">
				<a class="btn" rel="fb" href="$NewMessageLink">+ <% _t('Postale.NEWMESSAGE','New Message') %></a>
			</div>
			<div id="messages_search">
				<% include MessagesSearch %>
			</div>
		</div>
		<div id="messages_results">			
			<div id="message_actions">
				<a class="btn" href="$BackToMessagesLink">&laquo; <% _t('Postale.BACKTOMESSAGES','Back to messages') %></a>
			</div>
			<% if MessageSearchResults %>
				<% control MessageSearchResults %>
					<div class="message_wrap clearfix" id="message{$ID}">
						<div class="message_side">
							 <% control Author %>
							 	<% if Author.Link %>
							 		<a href="$Author.Link">$AvatarOrDefault</a>
							 	<% else %>
							 		$AvatarOrDefault
							 	<% end_if %>
							 <% end_control %>
						 </div>
						 <div class="message_main">
					 	 	<h4>
					 	 		<% if Author.Link %>
						 	 		<a href="$Author.Link">$Author.ShortLabel</a>
						 	 	<% else %>
						 	 		$Author.ShortLabel
						 	 	<% end_if %> 
						 	 	<span>$Created.Nice</span>
						 	</h4>
							<div>$Summary</div>
							<div><a href="$Link"><% _t('Postale.READMORE','more...') %></a></div>
						 </div>
					</div>
				<% end_control %>
			<% else %>
				<% _t('Postale.NORESULTS','There are no messages that match your search criteria.') %>
			<% end_if %>
		</div>
	</div>
</div>
