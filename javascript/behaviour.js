(function($) {
$(function() {
	$('a[rel=fb]').click(function() {
		$t = $(this);
		$.facebox("<iframe src='"+$t.attr('href')+"' frameborder='0' width='640' height='350'></iframe>");
		return false;
	});
	
	if($('#Form_CreateMessageForm_To').length) {
		var url = $("body").metadata().url;
		$("#Form_CreateMessageForm_To").fcbkcomplete({
			json_url: url + "/autocompleterecipients",
	        cache: false,
	        filter_case: false,
	        filter_hide: true,
			firstselected: true,
	        filter_selected: true,
			maxitems: 10,
	        newel: false        		
		});
	}
	
	$('a[rel=select_all]').live("click",function() {
		$('#messages_table td.checkbox input').attr('checked', true).trigger('change');
		return false;
	});	

	$('a[rel=select_read]').live("click",function() {
		$('#messages_table tr.read td.checkbox input').attr('checked', true).trigger('change');
		return false;
	});	

	$('a[rel=select_unread]').live("click",function() {
		$('#messages_table tr.unread td.checkbox input').attr('checked', true).trigger('change');
		return false;
	});

	$('a[rel=select_none]').live("click",function() {
		$('#messages_table td.checkbox input').attr('checked', false).trigger('change');
		return false;
	});
	
	$('#messages_table td.delete a').click(function() {
		$t = $(this);
		$.post(
			$t.attr('href'), 
			function(data) {
				if(data == "deleted") {
					$t.parents('tr').fadeOut(function() {
						$(this).remove();
					});
				}
			}
		);
		return false;
	});
	
	$('#Form_CreateMessageForm_action_doCancel').click(function() {
		window.parent.jQuery.facebox.close();
		return false;
	});	
	
	$('input.required, textarea.required').livequery(function() {
		$(this).closest('form').validate();
	});
	
	$('#Form_CreateMessageForm').livequery(function() {
		$(this).ajaxForm({
			target : 'p.message',
			beforeSubmit : function(arr, $form, options) {
				if(!$('#Form_CreateMessageForm').valid())
					return false;
				return true;
			},			
			success : function(data) {
				$('p.message').fadeIn();
			}
		});
	});
	
	$('#Form_ReplyForm').livequery(function() {
		$(this).ajaxForm({
			target : '#messages_interface',
			beforeSubmit : function(arr, $form, options) {
				if(!$('#Form_ReplyForm').valid())
					return false;
				return true;
			},			
			success : function(data) {
				if($('p.message').is(':visible')) {
					setTimeout(function() {
						$('p.message').fadeOut(function() {$(this).remove()});
					}, 3500);
				}
			}
		});
	});	

	$('#Form_MessageForm').livequery(function() {
		$(this).ajaxForm({
			target : '#messages_interface',
			success : function(data) {
				if($('p.message').is(':visible')) {
					setTimeout(function() {
						$('p.message').fadeOut(function() {$(this).remove()});
					}, 3500);
				}
			}
		});
	});	
	
	$('#show_control a, .message_actions a').live("click", function() {
		$t = $(this);
		$('#messages_interface').load($t.attr('href'));
		return false;
	});

	$('#messages_table td.subject a').live("click", function() {
		$t = $(this);
		t = this;
		$('#messages_interface').load(
			$t.attr('href'),
			function() {
				if(t.hash)
					$.scrollTo(t.hash, 500);
			}
		);
		return false;
	});

	
	$('#messages_table td.checkbox :checkbox').live("change",function() {
		if($('#messages_table td.checkbox :checked').length)
			$('.message_actions button').removeClass('disabled').attr('disabled', false);
		else
			$('.message_actions button').addClass('disabled').attr('disabled', true);
	});

	$('#messages_table td.checkbox :checkbox').live("click",function() {
		$(this).trigger('change');
	});
	
	$('#messages_table td.checkbox :checkbox').livequery(function() {
		$(this).trigger('change');
	});
	
	if($('#Form_MessagesSearchForm_MessagesSearch').length) {
		var initial_search_val = $('#Form_MessagesSearchForm_MessagesSearch').val();
		$('#Form_MessagesSearchForm_MessagesSearch').focus(function() {
			if($(this).val() == initial_search_val)
				$(this).val('').addClass('focus');
		}).blur(function() {
			if($(this).val() == '')
				$(this).val(initial_search_val).removeClass('focus');
		});
	}
});
})(jQuery);