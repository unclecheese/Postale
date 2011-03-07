<% if Results %>
	<ul>
	<% control Results %>
		<li>
			<h5><a href="$Link">$Subject</a></h5>
			<div>$Summary</div>
		</li>
	<% end_control %>
	</ul>
<% end_if %>