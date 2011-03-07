<?php

/**
 * This object is the bridge between members and their messages.
 * It holds the subject of the thread, and also manages the "Deleted"
 * and "IsRead" states of the thread for a given user
 *
 * @package Postale
 */
class Thread extends DataObject
{
	/**
	 * @var array The list of all the {@link Thread} IDs in the current result set.
	 * This is used to determine prev/next links based on the current ID.
	 */
	protected $itemList;
	
	/**
	 * @var int The current index of this {@link Thread} in the {@link $itemList}
	 */
	protected $currentIndex;


	static $db = array (
		'Subject' => 'Varchar'
	);
	
	static $has_many = array (
		'Messages' => 'Message'
	);
	
	static $belongs_many_many = array (
		'Members' => 'Member'
	);
	
	/**
	 * Gets the last message created in this thread
	 * @return Message
	 */
	public function LatestMessage() {
		return DataObject::get_one("Message","ThreadID = $this->ID", false, "Created DESC");
	}
		
	/**
	 * Provide a link to this thread, i.e. the detail view with all of its messages
	 * @return string
	 */
	public function Link() {
		return MessagesPage::Link('show', $this->ID);
	}
	
	/**
	 * Provide a link to delete this thread from the overview page
	 * @return string
	 */
	public function DeleteLink() {
		return MessagesPage::Link('delete', $this->ID);
	}
	
	/**
	 * Provide a link to mark this thread as read
	 * @return string
	 */
	public function MarkReadLink() {
		return MessagesPage::Link('markread', $this->ID);
	}

	/**
	 * Provide a link to mark this thread unread
	 * @return string
	 */	
	public function MarkUnreadLink() {
		return MessagesPage::Link('markunread', $this->ID);	
	}
	
	/**
	 * Provide a link to delete this thread from the detail page
	 * @return string
	 */	
	public function MarkDeletedLink() {
		return MessagesPage::Link('markdeleted', $this->ID);	
	}
	
	/**
	 * Provide a link to the next {@link Thread} in the result set
	 * @return string
	 */	
	public function NextLink() {
    	if(!$this->itemList) $this->loadList();
    	if(!$this->itemList || $this->currentIndex == sizeof($this->itemList)-1) return false;
		if($next = DataObject::get_by_id("Thread", $this->getNextID()))
			return $next->Link();
		return false;
	}
	
	/**
	 * Provide a link to the previous {@link Thread} in the result set
	 * @return string
	 */	
	public function PrevLink() {
    	if(!$this->itemList) $this->loadList();
    	if(!$this->itemList || $this->currentIndex == 0) return false;
		if($prev = DataObject::get_by_id("Thread", $this->getPrevID()))
			return $prev->Link();
		return false;
	}
	
	/**
	 * Check if we received a "CachedIsRead" field from the extended SQL query
	 * {@see MessagesPage::get_extended_query()}, and return that value if so.
	 * Otherwise, run a custom query to get the IsRead value of this thread
	 * for the current member
	 * @return boolean
	 */
	public function IsRead() {
		if($this->CacheIsRead) return $this->CacheIsRead;
		
		$member_id = Member::currentUserID();
		$result = DB::query("SELECT IsRead FROM `Member_Threads` WHERE MemberID = $member_id AND ThreadID = {$this->ID}");
		return $result->value();
	}
	
	/**
	 * Template accessor for {@link Thread::isDeletedUser}
	 */
	public function IsDeleted() {
		return $this->isDeletedForUser();
	}
	
	/**
	 * Returns a status of "read", "unread", or "deleted" to the template
	 * @return string
	 */
	public function Status() {
		if($this->IsDeleted())
			return _t('Postale.DELETED','Deleted');
		elseif($this->IsRead())
			return _t('Postale.READ','Read');
		return _t('Postale.UNREAD','Unread');
	}
	
	/**
	 * Provides a readable list to the template containing your name
	 * and all the others on this thread, e.g. "You, Bob, Cindy, and Kyle"
	 * @return string
	 */
	public function YouAndOthers() {
		$others = $this->Members("`Member`.ID != " . Member::currentUserID());
		$you = array(_t('Postale.YOU','You'));
		if($others) {
			$map = $others->column(MessagesPage::$member_short_label_field);
			$list = array_merge($you, $map);
			return DOMUtil::readable_list($list);
		}
	}	

	/**
	 * Check if we received a "CacheDeleted" field from the extended SQL query
	 * {@see MessagesPage::get_extended_query()}, and return that value if so.
	 * Otherwise, run a custom query to get the Deleted value of this thread
	 * for the current member
	 * @param int $id The user ID being checked. Defaults to current member id.
	 * @return boolean
	 */	
	public function isDeletedForUser($id = null) {
		if($this->CacheDeleted) return $this->CacheDeleted;
		
		$member_id = $id === null ? Member::currentUserID() : $id;
		$result = DB::query("SELECT Deleted FROM `Member_Threads` WHERE MemberID = $member_id AND ThreadID = {$this->ID}");
		return $result->value();	
	}
	
	/**
	 * Update the IsRead status for this thread for the current user
	 * @param boolean $bool The true/false value to set to the field
	 * @return SQLQuery
	 */
	public function updateIsReadForUser($bool) {
		$val = $bool ? 1 : 0;
		$member_id = Member::currentUserID();
		$result = DB::query("UPDATE `Member_Threads` SET IsRead = $val WHERE MemberID = $member_id AND ThreadID = {$this->ID}");
		return $result;
	}

	/**
	 * Update the IsRead status for this thread for all users on this thread
	 * @param boolean $bool The true/false value to set to the field
	 * @return SQLQuery
	 */	
	public function updateIsReadForAll($bool) {
		$val = $bool ? 1 : 0;
		$result = DB::query("UPDATE `Member_Threads` SET IsRead = $val WHERE ThreadID = {$this->ID}");
		return $result;	
	}
	
	/**
	 * Update the Deleted status for this thread for the current user
	 * @param boolean $bool The true/false value to set to the field
	 * @return SQLQuery
	 */	
	public function setDeleted($bool = true) {
		$val = $bool ? 1 : 0;
		$member_id = Member::currentUserID();
		$result = DB::query("UPDATE `Member_Threads` SET Deleted = $val WHERE MemberID = $member_id AND ThreadID = {$this->ID}");
		return $result;		
	}
		
	/**
	 * Loads the result set into {@link $itemList} and gets the current index
	 */
	protected function loadList() {
		if($messages = MessagesPage::get_messages_filtered()) {
			$this->itemList = $messages->column();
			$this->currentIndex = array_search($this->ID, $this->itemList);
		}
	}
	
	/** 
	 * Gets the ID before this thread in the result set
	 * @return int
	 */
	protected function getPrevID() {
	  return $this->itemList[$this->currentIndex - 1];
	}
	
	/**
	 * Gets the next ID in the result set
	 * @return int
	 */
	protected function getNextID() {
	  return $this->itemList[$this->currentIndex + 1];
	}
		
	
	/**
	 * Check to see if the user has authority to view this thread.
	 * @return boolean
	 */
	public function checkAuth() {
		if($member_ids = $this->Members()->column())
			return in_array(Member::currentUserID(), $member_ids);
		return false;
	}
	
		
}