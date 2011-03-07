<?php
/**
 * The controller for the main interface. Handles sending, viewing,
 * deleting, marking, etc. Most of the actions are AJAX responses,
 * but the actions are designed to function over normal HTTP requests
 * as well
 *
 * @package Postale
 */
class MessagesPage extends Page_Controller {
	/**
	 * @var string The URL segment that will point to this controller
	 */
	public static $url_segment;
	
	/**
	 * @var string The origin address of emails
	 */
	public static $from_address = null;
	 
	/**
	  * @var string The from name (also appears in the closing line of emails)
	  */
	public static $from_name = null;
	
	/**
	 * @var string A filter clause for the members search. For more advanced functionality,
	 * use {@link getSearchableMembers()} in a decorator
	 */
	public static $members_filter = null;
	
	/**
	 * @var string The field to use as a label for members in this module (e.g. "Nickname" or "FirstName")
	 * This field can also be a custom getter.
	 */
	public static $member_full_label_field = "Name";

	/**
	 * @var string A single field used to represent a member (e.g. "Nickname" or FirstName")
	 */
	public static $member_short_label_field = "FirstName";
	
	/**
	 * @var int The number of messages per page
	 */
	public static $messages_per_page = 25;
	
	/**
	 * @var int The length of a message body when summarized
	 * (autocomplete search)
	 */
	public static $summary_length = 40;
	
	/**
	 * @var array The allowed actions for this controller
	 */
	public static $allowed_actions = array (
		'autocompleterecipients',
		'autocompletesearch',
		'CreateMessageForm',
		'MessageForm',
		'MessagesSearchForm',
		'add',
		'show',
		'ReplyForm',
		'all',
		'unread',
		'delete',
		'markdeleted',
		'markread',
		'markunread'
	);
	
	/**
	 * Set the url for this controller and register it with {@link Director}
	 * @param string $url The URL to use
	 * @param $priority The priority of the URL rule
	 */
	public static function set_url($url, $priority = 50) {
		self::$url_segment = $url;
		Director::addRules($priority,array(
			$url => 'MessagesPage'
		));	
	}
	
	/**
	 * Returns a pre-loaded {@link SQLQuery} object with all of the necessary
	 * joins and aliasing. This object is modified and enhanched by other methods
	 * @param string $filter A filter clause for the query
	 * @return SQLQuery
	 */
	public static function get_messages_extended_query($filter = null) {
		$query = Member::currentUser()->getManyManyComponentsQuery(
			'Threads', 
			$filter,
			null,
			"INNER JOIN `Message` ON `Message`.ThreadID = `Thread`.ID"
		);
		
		// Save these fields as unique aliases so we don't call them twice {@see Thread::IsRead()}
		$query->select[] = "`Member_Threads`.IsRead AS CacheIsRead";
		$query->select[] = "`Member_Threads`.Deleted AS CacheDeleted";

		$query->select[] = "MAX(`Message`.Created) AS LatestMessageDate";
		$query->orderby("LatestMessageDate DESC");
		return $query;	
	}

	/**
	 * Gets the main query object and parses the page limit. Passes back a {@link DataObjectSet}
	 * ready for the template
	 * @param string $filter A filter clause for the query
	 * @return DataObjectSet
	 */
	public static function get_messages_extended($filter = null) {
		$query = self::get_messages_extended_query($filter);
		if(!isset($_REQUEST['start'])) $_REQUEST['start'] = 0;
		$limit = $_REQUEST['start'].",".self::$messages_per_page;
		$query->limit($limit);
		$result = singleton("Thread")->buildDataObjectSet($query->execute(), 'DataObjectSet', $query, 'Thread');
		if($result)
			$result->parseQueryLimit($query);
		return $result;	
	}
	
	/**
	 * Gets all the undeleted messages
	 * @return DataObjectSet
	 */
	public static function get_all_messages() {
		return self::get_messages_extended("`Deleted` != 1");
	}
	
	/**
	 * Get all of the undeleted, unread messages
	 * @return DataObjectSet
	 */
	public static function get_unread_messages() {
		return self::get_messages_extended("`Deleted` != 1 AND `IsRead` != 1");
	}
	
	/**
	 * Get all of the messages based on the current filter (stored in {@link Session})
	 * @return DataObjectSet
	 */
	public static function get_messages_filtered() {
		if(Session::get('MessagesFilter') == "unread")
			return self::get_unread_messages();
		return self::get_all_messages();
	}
	
	/**
	 * Updates a set of {@link Thread} objects based on posted data.
	 * Returns the total number updated.
	 * @param array $data The array of post data
	 * @param string $field The field to update
	 * @param boolean $bool The true/false value to set to the field
	 * @return int|boolean
	 */
	protected static function bulk_update($data, $field, $bool) {
		if(isset($data['marked']) && is_array($data['marked'])) {
			foreach($data['marked'] as $id) {
				if($thread = DataObject::get_by_id("Thread", Convert::raw2sql($id))) {
					switch($field) {
						case "IsRead":
							$thread->updateIsReadForUser($bool);
						break;
						case "Deleted":
							$thread->setDeleted($bool);
						break;
					}
				}
			}
			return sizeof($data['marked']);
		}
		return false;		
	}
	
	/**
	 * Get a query to search the fields of Thread, Message, and Member objects
	 * @param string $keywords The keywords to search for
	 * @return SQLQuery
	 */
	public static function get_search_query($keywords) {
		$search = Convert::raw2sql($keywords);
		$query = singleton("Message")->extendedSQL();
		$query->select[] = "`Thread`.Subject AS Subject";
		foreach(singleton('Member')->searchableFields() as $field => $arr)
			$query->select[] = "`Member`.{$field}";

		$query->innerJoin("Thread", "`Message`.ThreadID = `Thread`.ID");
		$query->innerJoin("Member","`Member`.ID = `Message`.AuthorID");
		$filters = array();
		$filters[] = "`Message`.Body LIKE '%{$search}%'";
		$filters[] = "`Thread`.Subject LIKE '%{$search}%'";
		foreach(singleton('Member')->searchableFields() as $field => $arr)
			$filters[] = "`Member`.{$field} LIKE '%{$search}%'";
		$where = implode(' OR ', $filters);
		$query->where[] = $where;
		return $query;
	}
	
	
	/**
	 * Initialize the controller and include dependencies
	 */
	public function init() {
		parent::init();
		if(!Member::currentUser())
			return Security::permissionFailure($this, _t('Postale.LOGIN','Please log in to access your messages.'));
		Requirements::javascript(THIRDPARTY_DIR.'/jquery/jquery.js');
		Requirements::javascript(THIRDPARTY_DIR.'/jquery-livequery/jquery.livequery.js');
		Requirements::javascript(THIRDPARTY_DIR.'/jquery-metadata/jquery.metadata.js');
		Requirements::javascript('dataobject_manager/javascript/facebox.js');
		Requirements::javascript('postale/javascript/validation.js');
		Requirements::javascript('postale/javascript/validation_improvements.js');		
		Requirements::css('dataobject_manager/css/facebox.css');
		Requirements::javascript('postale/javascript/jquery.fcbkcomplete.js');
		Requirements::javascript('postale/javascript/jquery.form.js');
		Requirements::javascript('postale/javascript/jquery.scrollTo.js');
		Requirements::css('postale/css/jquery.fcbkcomplete.css');
		Requirements::javascript('postale/javascript/behaviour.js');
		Requirements::themedCSS('messages');
	}
	
	/**
	 * The action that will show a the detail (messages) in a {@link Thread}
	 * @return SSViewer
	 */
	public function show() {
		if($thread = $this->Thread()) {
			$thread->updateIsReadForUser(true);
			if(Director::is_ajax())
				return $this->renderWith('MessageDetail');
			return array();
		}
		if(!Director::is_ajax())
			return Director::redirectBack();	
	}
	
	/**
	 * This method is called when a user clicks the "delete" button in the 
	 * main Messages page. For the detail view delete, see {@link markdeleted()}
	 * @return SSViewer
	 */
	public function delete() {
		if($thread = $this->Thread())
			$thread->setDeleted();
		if(Director::is_ajax())
			return new SS_HTTPResponse('deleted',200);
		return Director::redirectBack();
	}
	
	/** 
	 * The default action sets the filter to "all" messages
	 * @return SSViewer
	 */
	public function index() {
		Session::set('MessagesFilter','all');
		$this->AllLink = "current";
		if(Director::is_ajax())
			return $this->renderWith('MessagesInterface');
		return array();
	}
	
	/**
	 * This action shows all of the unread messages, and updates
	 * the session variable
	 * @return SSViewer
	 */
	public function unread() {		
		Session::set('MessagesFilter','unread');
		$this->UnreadLink = "current";
		if(Director::is_ajax())
			return $this->renderWith('MessagesInterface');
		return array();
	}
	
	/**
	 * This action feeds the search box in the messages interface.
	 * Searches on several fields in {@link Thread}, {@link Message} and {@link Member}
	 * @return DataObjectSet
	 */
	public function autocompletesearch() {
		if(isset($_REQUEST['q']) && !empty($_REQUEST['q'])) {
			$query = self::get_search_query($_REQUEST['q']);
			$result = singleton("Message")->buildDataObjectSet($query->execute(), 'DataObjectSet', $query, 'Message');	
			return AutoCompleteField::render($result);
		}
	}
	
	/**
	 * Used by the detail view to mark the given {@link Thread} read
	 * @return SSViewer
	 */
	public function markread() {
		if($thread = $this->Thread()) {
			$thread->updateIsReadForUser(true);
			if(Director::is_ajax())
				return $this->renderWith('MessagesInterface');
			return Director::redirectBack();
		}
	}
	
	/**
	 * Used by the detail view to mark the given {@link Thread} read
	 * @return SSViewer
	 */
	public function markunread() {
		if($thread = $this->Thread()) {
			$thread->updateIsReadForUser(false);
			if(Director::is_ajax())
				return $this->renderWith('MessagesInterface');
			return Director::redirectBack();
		}	
	}
	
	/**
	 * Used by the detail view to mark the given {@link Thread} deleted
	 * @return SSViewer
	 */
	public function markdeleted() {
		if($thread = $this->Thread()) {
			$thread->setDeleted(true);
			if(Director::is_ajax())
				return $this->renderWith('MessagesInterface');
			return Director::redirectBack();
		}	
	}

	/**
	 * This action feeds the automatic population of the "To" field in the
	 * create message view
	 * @todo: Update to make more modular
	 * @return JSON
	 */
	public function autocompleterecipients() {
		if(isset($_REQUEST['tag']) && !empty($_REQUEST['tag'])) {
			if($this->hasMethod('getSearchableMembers'))
				$result = $this->getSearchableMembers();
			else
				$result = DataObject::get("Member", self::$members_filter);
			if($result) {
				$ret = array();
				foreach($result as $member) {
					$ret[] = array (
						'caption' => $member->FullLabel(),
						'value' => $member->ID
					);
				}
				return Convert::array2json($ret);
			}
				
		}
	}
	
	/**
	 * Provide a link to this controller
	 * @param string $action The action of the controller
	 * @param string $id The ID property
	 * @return string
	 */
	public function Link($action = null, $id = null) {
		return Controller::join_links(self::$url_segment, $action, $id);
	}
	
	/**
	 * Returns a set of {@link Thread} objects based on the current filter
	 * @return DataObjectSet
	 */
	public function Messages() {
		return self::get_messages_filtered();
	}
	
	/**
	 * Present the main interface as a form to support actions for threads that are checked off
	 * @return Form
	 */	
	public function MessageForm() {
		$f = new Form(
			$this,
			"MessageForm",
			new FieldSet(),
			new FieldSet(
				$a = new FormAction('doMarkRead',_t('Postale.MARKASREAD','Mark as read')),
				$b = new FormAction('doMarkUnread',_t('Postale.MARKASUNREAD','Mark as unread')),
				$c = new FormAction('doDelete',_t('Postale.DELETE','Delete'))			
			)
		);
		$a->useButtonTag = true;
		$b->useButtonTag = true;
		$c->useButtonTag = true;
		$f->disableSecurityToken();
		return $f;
	}
	
	/**
	 * Link to all messages (index action will set the Session var)
	 * @return string
	 */
	public function AllMessagesLink() {
		return $this->Link();
	}
	
	/**
	 * Link to the unread messages
	 * @return string
	 */
	public function UnreadMessagesLink() {
		return $this->Link('unread');
	}
	
	/**
	 * Link to add a new message (popup window)
	 * @return string
	 */
	public function NewMessageLink() {
		return $this->Link('add');
	}
	
	/**
	 * This links back to the index action. Just a placeholder in case it ever changes.
	 * @return string
	 */
	public function BackToMessagesLink() {
		return $this->Link();
	}
	
	
	/**
	 * This template accessor will get the current thread and check if the user
	 * can view it. Because the IDs in the URL are always for {@link Thread} objects,
	 * this is pretty handy.
	 * @return Thread
	 */
	public function Thread() {
		if(($thread = $this->getFromRequest("Thread")) && $thread->checkAuth())
			return $thread;
		return false;
	}
	
	/**
	 * Return the form used to create a message (appears in a popup)
	 * @return form
	 */
	public function CreateMessageForm() {
		return new Form(
			$this,
			"CreateMessageForm",
			new FieldSet(
				new DropdownField('To', _t('Postale.TO','To'),array()),
				new TextField('Subject', _t('Postale.SUBJECT','Subject')),
				new TextareaField('Body', _t('Postale.BODY','Body'))
			),
			new FieldSet (
				new FormAction('doCreate', _t('Postale.SEND','Send')),
				new FormAction('doCancel', _t('Postale.CANCEL','Cancel'))
			),
			new MessagesValidator("To","Subject","Body")
		);
	}
	
	/**
	 * Return the form used to reply to a thread
	 * @return Form
	 */
	public function ReplyForm() {
		if($thread = $this->Thread()) {
			$form = new Form (
				$this,
				"ReplyForm",
				new FieldSet(
					new TextareaField('Body',_t('Postale.REPLY','Reply')),
					new HiddenField('ID', '', $thread->ID)
				),
				new FieldSet(
					new FormAction('doReply', _t('Postale.REPLY','Reply'))
				),
				new MessagesValidator("Body")
			);
			return $form;
		}
		return false;
	}
		
	/**
	 * Returns the form used to search messages
	 * @return Form
	 */
	public function MessagesSearchForm() {
		if(class_exists('AutoCompleteField'))
			$field = new AutoCompleteField('MessagesSearch','','autocompletesearch',_t('Postale.SEARCHMESSAGES','Search messages...'));
		else
			$field = new Textfield('MessagesSearch','',_t('Postale.SEARCHMESSAGES','Search messages...'));
			
		$f = new Form(
			$this,
			"MessagesSearchForm",
			new FieldSet(
				$field
			),
			new FieldSet(
				$d = new FormAction('doSearch','Search')
			)
		);
		$f->disableSecurityToken();
		return $f;
	}
		
	/**
	 * Handle the action for marking a set of messages as read.
	 * @param array $data The form data that was passed (i.e a set of {@link Thread} IDs)
	 * @param Form $form The form that was used
	 * @return SSViewer
	 */
	public function doMarkRead($data, $form) {
		if($num = self::bulk_update($data, 'IsRead', true))
			$msg = sprintf(_t('MESSAGESMARKEDREAD','%d message(s) marked as read'),$num);
			$form->sessionMessage($msg,'good');
			if(Director::is_ajax())
				return $this->renderWith('MessagesInterface');
		return Director::redirectBack();
	}
	
	/**
	 * Handle the action for marking a set of messages as unread.
	 * @param array $data The form data that was passed (i.e a set of {@link Thread} IDs)
	 * @param Form $form The form that was used
	 * @return SSViewer
	 */
	public function doMarkUnread($data, $form) {
		if($num = self::bulk_update($data, 'IsRead', false))
			$msg = sprintf(_t('MESSAGESMARKEDUNREAD','%d message(s) marked as unread'),$num);
			$form->sessionMessage($msg,'good');
			if(Director::is_ajax())
				return $this->renderWith('MessagesInterface');
		return Director::redirectBack();
	}

	/**
	 * Handle the action for marking a set of messages as deleted.
	 * @param array $data The form data that was passed (i.e a set of {@link Thread} IDs)
	 * @param Form $form The form that was used
	 * @return SSViewer
	 */	
	public function doDelete($data, $form) {
		if($num = self::bulk_update($data, 'Deleted', true))
			$msg = sprintf(_t('MESSAGESMARKEDDELETED','%d message(s) deleted'),$num);
			$form->sessionMessage($msg,'good');
			if(Director::is_ajax())
				return $this->renderWith('MessagesInterface');
		return Director::redirectBack();	
	}

	/**
	 * Handle the action for replying to a {@link Thread}.
	 * @param array $data The form data that was passed
	 * @param Form $form The form that was used
	 * @return SSViewer
	 */	
	public function doReply($data, $form) {
		if($thread = $this->Thread()) {
			$message = new Message();
			$message->AuthorID = Member::currentUserID();
			$message->Body = $data['Body'];
			$message->ThreadID = $data['ID'];
			$message->write();
			if(Director::is_ajax())
				return $this->renderWith('MessageDetail');
			return Director::redirectBack();
		}
	}

	/**
	 * Handle the action for creating a {@link Thread}
	 * @param array $data The form data that was passed
	 * @param Form $form The form that was used
	 * @return SS_HTTPResponse
	 */		
	public function doCreate($data, $form) {
		if(is_array($data['To'])) {
			// Create the thread
			$thread = new Thread();
			$thread->Subject = $data['Subject'];
			$thread->write();
			foreach($data['To'] as $id) {
				if($member = DataObject::get_by_id("Member", Convert::raw2sql($id)))
					// associate this thread with all the recipients in "To"
					$member->Threads()->add($thread);
			}
			// Add the author, as well.
			Member::currentUser()->Threads()->add($thread);
			
			// Create the message
			$message = new Message();
			$message->Body = $data['Body'];
			$message->AuthorID = Member::currentUserID();
			$message->ThreadID = $thread->ID;
			$message->write();
			$label = MessagesPage::$member_short_label_field;
			$recipients = $thread->Members("`Member`.ID != " . Member::currentUserID())->column(MessagesPage::$member_short_label_field);
			$list = DOMUtil::readable_list($recipients);
			$msg = sprintf(_t('Postale.SENTSUCCESSFULLY','Your message was sent successfully to %s'),$list);
			if(Director::is_ajax())
				return new SS_HTTPResponse($msg, 200);
			$form->sessionMessage($msg,'good');
		}
		return Director::redirectBack();		
	}
	
	/**
	 * Execute the search for messages
	 * @param $data The form data that was posted
	 * @param $form The Form object that was used
	 * @return SSViewer
	 */
	public function doSearch($data, $form) {
		$query = self::get_search_query($data['MessagesSearch']);
		$result = singleton("Message")->buildDataObjectSet($query->execute(), 'DataObjectSet', $query, 'Message');	
		return $this->customise(array(
			'MessageSearchResults' => $result,
			'Query' => $data['MessagesSearch']
		))->renderWith(array('MessagesPage_results','Page'));
	}
	
	/**
	 * Gets an ID "cleanly". Checks the URL first, then the request. Very handy
	 * for forms that edit objects
	 *
	 * Note: There's a nasty IE bug that reads the hash as part of the URL. This function
	 * cleans that up.
	 *
	 * @return int|boolean
	 */
	protected function cleanID() {
		if($this->urlParams['ID']) {
			$hash = strpos($this->urlParams['ID'],"#");
			if($hash !== false) {
				$tag = substr($this->urlParams['ID'], $hash);
				$clean = str_replace($tag,"",$this->urlParams['ID']);
				return is_numeric($clean) ? $clean : false;
			}
			return $this->urlParams['ID'];
		}
		elseif(isset($_REQUEST['ID']) && is_numeric($_REQUEST['ID']))
			return $_REQUEST['ID'];
		return false;
	}
	
	/**
	 * Uses {@link cleanID()} to capture the ID and get a record from the database
	 * @param string $className The name of the object to fetch
	 * @return DataObject
	 */
	protected function getFromRequest($className) {
		if($id = $this->cleanID())
			return DataObject::get_by_id($className, $id);
		return false;
	}
}