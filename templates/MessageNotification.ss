<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html>
<head>
	<% base_tag %>
</head>
<body>

<p class="body">
	<% control Member %>
		<% sprintf(_t('Postale.SALUTATION','Hello, %s!'),$ShortLabel) %>
	<% end_control %>
</p>
<% control Message %>
	<p><% sprintf(_t('Postale.USERSENTYOUMESSAGE','%s sent you a message.'),$Author.Nickname) %></p>
	<p><strong><% _t('Postale.SUBJECT','Subject') %></strong>: $Thread.Subject</p>
	<p><strong><% _t('Postale.MESSAGE','Message') %></strong>: $Body</p>
	<p><% _t('Postale.TOREPLY','To reply to this message, follow the link below:') %></p>
	<p><a href="$Link">{$BaseHref}/$Link</a></p>
<% end_control %>
<% if FromName %>
	<p><% _t('Postale.CLOSING','Sincerely,') %><br />
	$FromName</p>
<% end_if %>

</body>
</html>						
