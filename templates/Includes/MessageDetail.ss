<% include DetailActions %>
<% if Thread %>
	<% control Thread %>
		<div><strong><% _t('Postale.SUBJECT','Subject') %></strong>: $Subject</div>
		<div><strong><% _t('Postale.BETWEEN','Between') %></strong>: $YouAndOthers</div>
		<% control Messages %>
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
					<div>$Body</div>
				 </div>
			 </div>
		<% end_control %>
	<% end_control %>
	$ReplyForm
	<% include DetailActions %>	
<% else %>
	<% _t('Postale.THREADNOTFOUND','That thread could not be found.') %>
<% end_if %>
