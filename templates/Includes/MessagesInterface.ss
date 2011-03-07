		<% control MessageForm %>
			<form $FormAttributes >
				<% if Message %><p id="{$FormName}_error" class="message">$Message</p><% end_if %>
			<div class="message_actions">
				<% control Actions %>
					$Field
				<% end_control %>
		<% end_control %>
				<div class="next_prev">
					<% if Messages.NotFirstPage %>
						<a class="btn" href="$Messages.PrevLink" title="<% _t('Postale.VIEWPREV','View the previous page') %>">&laquo;</a>
					<% else %>
						<a class="btn disabled" href="javascript:void(0);" title="<% _t('Postale.VIEWPREV','View the previous page') %>">&laquo;</a>								
					<% end_if %>
					<% if Messages.NotLastPage %>
						<a class="btn" href="$Messages.NextLink" title="<% _t('Postale.VIEWNEXT','View the next page') %>">&raquo;</a>
					<% else %>
						<a class="btn disabled" href="javascript:void(0);" title="<% _t('Postale.VIEWNEXT','View the next page') %>">&raquo;</a>								
					<% end_if %>
				</div>		
			</div>
		<div id="controls_wrap" class="clearfix">
			<div id="select_control"><% _t('Postale.SELECT','Select') %>: <a rel="select_all" href="javascript:void(0)"><% _t('Postale.ALL','All') %></a> | <a rel="select_read" href="javascript:void(0)"><% _t('Postale.READ','Read') %></a> | <a rel="select_unread" href="javascript:void(0)"><% _t('Postale.UNREAD','Unread') %></a> | <a rel="select_none" href="javascript:void(0)"><% _t('Postale.NONE','None') %></a></div>
			<div id="show_control"><% _t('Postale.SHOW','Show') %>: <a class="$AllLink" href="$AllMessagesLink"><% _t('Postale.ALL','All') %></a> | <a class="$UnreadLink" href="$UnreadMessagesLink"><% _t('Postale.UNREAD','Unread') %></a></div>
		</div>
		<% if Messages %>
		<table id="messages_table" cellpadding="0" cellspacing="0" border="0" width="100%">
			<% control Messages %>
				<tr class="<% if IsRead %>read<% else %>unread<% end_if %>">
					<td class="checkbox"><input type="checkbox" value="$ID" name="marked[]" /></td>
					<td class="avatar">
						<% control LatestMessage %>
							<% control Author %>$AvatarOrDefault
					</td>
					<td class="author">
						<% if Author.Link %>
							<a href="$Link">$ShortLabel</a>
						<% else %>
							$ShortLabel
						<% end_if %>
						<br />
						<% end_control %>
						$Created.Nice
						<% end_control %>
					</td>
					<td class="subject">
						<a href="$Link#message{$LatestMessage.ID}">$Subject</a>
					</td>
					<td class="delete">
						<a href="$DeleteLink"><% _t('Postale.DELETE','delete') %></a>
					</td>
				</tr>
			<% end_control %>
		</table>
		<% else %>
			<% _t('Postale.NOMESSAGES','No messages found') %>
		<% end_if %>
		</form>
